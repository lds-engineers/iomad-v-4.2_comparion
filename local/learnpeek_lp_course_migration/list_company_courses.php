<?php
require_once('../../config.php');
global $DB;
header('Content-Type: application/json');

$company_host = optional_param('company_host','',PARAM_RAW_TRIMMED);

if (empty($company_host)) {

    echo json_encode([
        'success'  => false,
        'message'  => 'Company host is required.',
        'courses'  => [],
    ]);

    exit;
}


$company = $DB->get_record(
    'company',
    [
        'hostname' => $company_host,
    ]
);

if (!$company) {
    echo json_encode([
        'success'  => false,
        'message'  => 'Company not found.',
        'courses'  => [],
    ]);

    exit;
}

$companyid = $company->id;
// print_r($companyid);
// die();


// Get company mapped categories.
    $cmappedctg = $DB->get_record(
        'company_category_mapping',
        ['company_id' => $companyid]
    );

if (!$cmappedctg || empty($cmappedctg->course_category)) {

    echo json_encode([
        'success'    => true,
        'company_id' => $companyid,
        'company'    => $company->name,
        'message'    => 'No mapped categories found.',
        'categories' => [],
    ]);

    exit;
}


// Convert mapped category IDs into an array.
$mappedcategoryids = array_filter(
    array_map(
        'intval',
        explode(',', $cmappedctg->course_category)
    )
);

if (empty($mappedcategoryids)) {

    echo json_encode([
        'success'    => true,
        'company_id' => $companyid,
        'company'    => $company->name,
        'message'    => 'No mapped categories found.',
        'categories' => [],
    ]);

    exit;
}


list($insql, $params) = $DB->get_in_or_equal(
    $mappedcategoryids,
    SQL_PARAMS_NAMED,
    'cat'
);

$categories = $DB->get_records_sql(
    "SELECT
            id,
            name,
            parent,
            path,
            depth,
            sortorder
       FROM {course_categories}
      WHERE id $insql
      ORDER BY parent, sortorder",
    $params
);

if (empty($categories)) {

    echo json_encode([
        'success'    => true,
        'company_id' => $companyid,
        'company'    => $company->name,
        'message'    => 'Mapped categories not found.',
        'categories' => [],
    ]);

    exit;
}


$allcategories = $categories;

foreach ($categories as $category) {

    $children = $DB->get_records_sql(
        "SELECT
                id,
                name,
                parent,
                path,
                depth,
                sortorder
           FROM {course_categories}
          WHERE path LIKE :path
          ORDER BY depth, parent, sortorder",
        [
            'path' => $category->path . '/%',
        ]
    );

    foreach ($children as $child) {

        $allcategories[$child->id] = $child;
    }
}


/**
 * ---------------------------------------------------------
 * Build category tree
 * ---------------------------------------------------------
 */
$tree = [];

foreach ($allcategories as $category) {

    if (!isset($tree[$category->parent])) {
        $tree[$category->parent] = [];
    }

    $tree[$category->parent][] = $category;
}


/**
 * ---------------------------------------------------------
 * Recursive category/course function
 * ---------------------------------------------------------
 */
$build_category_tree = function($categoryid) use (
    &$build_category_tree,
    &$tree,
    $DB
) {

    $result = [];

    if (empty($tree[$categoryid])) {
        return $result;
    }

    foreach ($tree[$categoryid] as $category) {

        /**
         * Get courses directly inside this category.
         */
        $courses = $DB->get_records_sql(
            "SELECT
                    id,
                    fullname,
                    shortname,
                    visible,
                    category
               FROM {course}
              WHERE category = :categoryid
                AND id > 1
              ORDER BY sortorder, fullname",
            [
                'categoryid' => $category->id,
            ]
        );


        /**
         * Format courses.
         */
        $course_list = [];

        foreach ($courses as $course) {

            $course_list[] = [
                'id'        => (int) $course->id,
                'fullname'  => $course->fullname,
                'shortname' => $course->shortname,
                'visible'   => (int) $course->visible,
                'category'  => (int) $course->category,
            ];
        }


        /**
         * Get child categories.
         */
        $children = $build_category_tree($category->id);


        /**
         * Add category.
         */
        $result[] = [
            'category_id'   => (int) $category->id,
            'category_name' => $category->name,
            'parent_id'     => (int) $category->parent,
            'courses'       => $course_list,
            'children'      => $children,
        ];
    }

    return $result;
};


/**
 * ---------------------------------------------------------
 * Build final result
 *
 * Start ONLY from directly mapped categories.
 * ---------------------------------------------------------
 */
$result = [];

foreach ($mappedcategoryids as $categoryid) {

    if (!isset($allcategories[$categoryid])) {
        continue;
    }

    $category = $allcategories[$categoryid];


    /**
     * Courses directly inside mapped category.
     */
    $courses = $DB->get_records_sql(
        "SELECT
                id,
                fullname,
                shortname,
                visible,
                category
           FROM {course}
          WHERE category = :categoryid
            AND id > 1
          ORDER BY sortorder, fullname",
        [
            'categoryid' => $categoryid,
        ]
    );


    $course_list = [];

    foreach ($courses as $course) {

        $course_list[] = [
            'id'        => (int) $course->id,
            'fullname'  => $course->fullname,
            'shortname' => $course->shortname,
            'visible'   => (int) $course->visible,
            'category'  => (int) $course->category,
        ];
    }


    /**
     * Get subcategories.
     */
    $children = $build_category_tree($categoryid);


    /**
     * Add mapped category.
     */
    $result[] = [
        'category_id'   => (int) $category->id,
        'category_name' => $category->name,
        'parent_id'     => (int) $category->parent,
        'courses'       => $course_list,
        'children'      => $children,
    ];
}


/**
 * ---------------------------------------------------------
 * JSON response
 * ---------------------------------------------------------
 */
echo json_encode(
    [
        'success'    => true,
        'company_id' => (int) $companyid,
        'company'    => $company->name,
        'host'       => $company_host,
        'categories' => $result,
    ],
    JSON_PRETTY_PRINT
);

exit;