<?php
require_once('../../config.php');

    global $DB;
$companyid = 36;
    // Get company mapped categories.
    $cmappedctg = $DB->get_record(
        'company_category_mapping',
        ['company_id' => $companyid]
    );

    if (!$cmappedctg || empty($cmappedctg->course_category)) {
        return [];
    }

    // Convert mapped category IDs into an array.
    $mappedcategoryids = array_filter(
        array_map('intval', explode(',', $cmappedctg->course_category))
    );

    if (empty($mappedcategoryids)) {
        return [];
    }

    list($insql, $params) = $DB->get_in_or_equal(
        $mappedcategoryids,
        SQL_PARAMS_NAMED,
        'cat'
    );

    // Get all mapped categories.
    $categories = $DB->get_records_sql(
        "SELECT *
           FROM {course_categories}
          WHERE id $insql
          ORDER BY parent, sortorder",
        $params
    );

    if (empty($categories)) {
        return [];
    }



    $allcategories = $categories;

    foreach ($categories as $category) {

        $children = $DB->get_records_sql(
            "SELECT *
               FROM {course_categories}
              WHERE path LIKE :path
              ORDER BY parent, sortorder",
            [
                'path' => $category->path . '/%'
            ]
        );

        foreach ($children as $child) {
            $allcategories[$child->id] = $child;
        }
    }

    // Build category tree.
    $tree = [];

    foreach ($allcategories as $category) {

        if (!isset($tree[$category->parent])) {
            $tree[$category->parent] = [];
        }

        $tree[$category->parent][] = $category;
    }

    /**
     * Recursively build category + course structure.
     */
    $build_tree = function($parentid) use (&$build_tree, &$tree, $DB) {

        $result = [];

        if (empty($tree[$parentid])) {
            return $result;
        }

        foreach ($tree[$parentid] as $category) {

            // Get courses directly inside this category.
            $courses = $DB->get_records_sql(
                "SELECT c.id,
                        c.fullname,
                        c.shortname,
                        c.visible,
                        c.category
                   FROM {course} c
                  WHERE c.category = :categoryid
                    AND c.id > 1
                  ORDER BY c.sortorder, c.fullname",
                [
                    'categoryid' => $category->id
                ]
            );

            $result[] = [
                'category' => $category,
                'courses' => $courses,
                'children' => $build_tree($category->id)
            ];
        }

        return $result;
    };

    /*
     * Only start from the company's directly mapped categories.
     */
    $result = [];

    foreach ($mappedcategoryids as $categoryid) {

        if (!isset($allcategories[$categoryid])) {
            continue;
        }

        $category = $allcategories[$categoryid];

        $courses = $DB->get_records_sql(
            "SELECT c.id,
                    c.fullname,
                    c.shortname,
                    c.visible,
                    c.category
               FROM {course} c
              WHERE c.category = :categoryid
                AND c.id > 1
              ORDER BY c.sortorder, c.fullname",
            [
                'categoryid' => $categoryid
            ]
        );

        $result[] = [
            'category' => $category,
            'courses' => $courses,
            'children' => $build_tree($categoryid)
        ];
    }

echo "<pre>";
print_r($result);
echo "</pre>";