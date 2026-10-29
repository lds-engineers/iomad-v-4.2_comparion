<?php

require_once(__DIR__ . '/../config.php');
require_login();

global $DB, $USER, $CFG;

if (!is_siteadmin($USER)) {
    print_error('Access denied: Admins only');
}

$PAGE->set_url(new moodle_url('/courses/mycourse.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('My Course Admin Page');
echo $OUTPUT->header();

// === Get Search and Pagination Parameters ===
$search = trim(optional_param('search', '', PARAM_TEXT));
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 10, PARAM_INT);
$limit = ($perpage == -1) ? 99999 : $perpage;
$offset = $page * $limit;

// === CSS Styling ===
echo '
<style>
    .container {
        max-width: 1100px;
        margin: 0 auto;
        padding: 20px;
        font-family: "Roboto", Arial, sans-serif;
        background-color: #f9f9f9;
        border-radius: 8px;
    }

    .search-form {
        text-align: center;
        margin-bottom: 20px;
    }

    .search-form input[type="text"] {
        width: 60%;
        padding: 10px;
        font-size: 16px;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

    .search-form button {
        padding: 10px 20px;
        font-size: 16px;
        background-color: #0f47ad;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .search-form button:hover {
        background-color: #005f8d;
    }

    .category-box {
        background-color: #ffffff;
        border: 1px solid #ddd;
        border-radius: 8px;
        margin-bottom: 20px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .category-title {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 10px;
        color: #333;
        text-transform: uppercase;
        letter-spacing: 1px; 
    }

    .course-list {
        margin: 0;
        padding-left: 20px;
        list-style-type: none;
    }

    .course-list li {
        background-color: #f8f8f8;
        border: 1px solid #e0e0e0;
        border-radius: 5px;
        margin: 5px 0;
        padding: 10px 15px;
        font-size: 16px;
    }

    .course-list li:hover {
        background-color: #0f47ad;
        color: white;
        cursor: pointer;
    }

    .course-list li a {
        text-decoration: none;
        color: inherit;
    }

    .pagination {
        text-align: center;
        margin-top: 30px;
    }

    .page-link {
        display: inline-block;
        padding: 10px 15px;
        background-color: #f1f1f1;
        color: #0f47ad;
        text-decoration: none;
        margin: 0 5px;
        border-radius: 5px;
        font-weight: bold;
    }

    .page-link:hover {
        background-color: #0f47ad;
        color: white;
    }

    .page-link.active {
        background-color: #0f47ad;
        color: white;
    }

    .dots {
        display: inline-block;
        padding: 10px 15px;
        color: #0f47ad;
        font-weight: bold;
    }

     .search-form select {
        padding: 4px 8px;
        border-radius: 6px;
        border: 1px solid #ccc;
        background-color: #f9f9f9;
    }

    .search-form label {
        font-weight: bold;
        margin-right: 4px;
    }

    .search-form {
        margin-bottom: 20px;
    }
</style>
';

echo '<div class="container">';
echo '<form class="search-form" method="get" action="" style="display: flex; align-items: center; justify-content: space-around; gap: 10px;">
    <div>
    <input type="text" name="search" value="' . s($search) . '" placeholder="Search courses by name..." />
    <button type="submit">Search</button>
    </div>

    <div>
    <label>Show</label>
    <select name="perpage" onchange="this.form.submit()">
        <option value="10" '.($perpage == 10 ? 'selected' : '').'>10</option>
        <option value="25" '.($perpage == 25 ? 'selected' : '').'>25</option>
        <option value="50" '.($perpage == 50 ? 'selected' : '').'>50</option>
        <option value="-1" '.($perpage == -1 ? 'selected' : '').'>All</option>
    </select>
    </div>
</form>


';

if ($search !== '') {
    // === SEARCH MODE ===
    $like = $DB->sql_like('c.fullname', ':search', false);
    $params = ['search' => "%$search%"];
    $courses = $DB->get_records_sql("
        SELECT c.*, cc.name AS categoryname
        FROM {course} c
        LEFT JOIN {course_categories} cc ON cc.id = c.category
        WHERE $like
        ORDER BY c.fullname ASC
    ", $params);

    echo '<div class="">';
    echo '<div class="category-title">Search Results</div>';
    if ($courses) {
        echo '<ul class="course-list">';
        foreach ($courses as $course) {
            echo '<li><a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '">' . 
                format_string($course->fullname) . ' (Category: ' . format_string($course->categoryname) . ')</a></li>';
        }
        echo '</ul>';
    } else {
        echo '<div class="no-courses">No courses found.</div>';
    }
    echo '</div>';
} else {
    // === DEFAULT PAGINATION MODE ===

    // Get total number of courses for pagination
    $total_courses = $DB->count_records('course');
    $total_pages = ceil($total_courses / $limit);

    // Fetch 10 courses for this page, with category data
    $sql = "
        SELECT c.*, cc.name AS categoryname, cc.id AS categoryid
        FROM {course} c
        LEFT JOIN {course_categories} cc ON cc.id = c.category
        WHERE c.category != 0
        ORDER BY cc.sortorder ASC, c.sortorder ASC
        LIMIT $offset, $limit
    ";
    $records = $DB->get_records_sql($sql);

    // Group by category
    $grouped = [];
    foreach ($records as $record) {
        $catid = $record->categoryid;
        if (!isset($grouped[$catid])) {
            $grouped[$catid] = [
                'categoryname' => $record->categoryname,
                'categoryid' => $record->categoryid,
                'courses' => []
            ];
        }
        $grouped[$catid]['courses'][] = $record;
    }

    // Display grouped courses
    foreach ($grouped as $category) {
        $categoryidd = $category['categoryid'];
        // $companyname = $DB->get_record_sql("SELECT name FROM {company} where category= :categoryid", ['categoryid' =>$categoryidd]);
        $companyname = $DB->get_field_sql("
                SELECT c.name 
                FROM {company} c
                WHERE c.category = :categoryid
            ", ['categoryid' => $categoryidd]);
 
        echo '<div class="">';
        // echo '<div class="category-title"  value="'.$category['categoryid'] .'">' . format_string($category['categoryname']) . '</div>';
      echo '<div class="category-title" value="' . $category['categoryid'] .'">' . 
     format_string($category['categoryname']) . 
     ($companyname ? ' (' . format_string($companyname) . ')' : '') . 
     '</div>';


        echo '<ul class="course-list">';
        foreach ($category['courses'] as $course) {
 

            echo '<li><a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '">' . 
                format_string($course->fullname)  . '</a></li>';
        }
        echo '</ul>';
        echo '</div>';
    }

    // === Pagination Links ===

    if($perpage != 1){
    echo '<div class="pagination">';
    $page_range = 2;
    $start_page = max(0, $page - $page_range);
    $end_page = min($total_pages - 1, $page + $page_range);

    if ($start_page > 0) {
        echo '<a href="?page=0" class="page-link">1</a>';
        if ($start_page > 1) echo '<span class="dots">...</span>';
    }

    for ($i = $start_page; $i <= $end_page; $i++) {
        $active = ($i == $page) ? 'active' : '';
        echo '<a href="?page=' . $i . '" class="page-link ' . $active . '">' . ($i + 1) . '</a>';
    }

    if ($end_page < $total_pages - 1) {
        if ($end_page < $total_pages - 2) echo '<span class="dots">...</span>';
        echo '<a href="?page=' . ($total_pages - 1) . '" class="page-link">' . $total_pages . '</a>';
    }

    echo '</div>';

}
}

echo '</div>'; // .container
echo $OUTPUT->footer();