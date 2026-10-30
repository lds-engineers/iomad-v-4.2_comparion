<?php

require_once(__DIR__ . '/../config.php');
require_login();

global $DB, $USER, $CFG;

if (!is_siteadmin($USER)) {
    print_error('Access denied: Admins only');
}

$PAGE->set_url(new moodle_url('/courses/mycoursetwo.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('My Course Admin Page');
echo $OUTPUT->header();

// === Get Search and Pagination Parameters ===
$search = trim(optional_param('search', '', PARAM_TEXT));
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 3, PARAM_INT);
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
    margin-bottom: 20px;
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 10px;
    flex-wrap:wrap;
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
        font-size: 23px;
        font-weight: bold;
        margin-bottom: 10px;
        color: #333;
        text-transform: uppercase;
        letter-spacing: 1px; 
    }

    .course-list {
        margin: 0;
        padding-left: 0px;
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

    .no-courses {
        color: #999;
        font-style: italic;
        margin-left: 20px;
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
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
</style>
';

echo '<div class="container">';
echo '<form class="search-form" method="get" action="">
    <input type="text" name="search" value="' . s($search) . '" placeholder="Search courses by name..." />
    <button type="submit">Search</button>

    <label for="perpage">Show</label>
    <select name="perpage" id="perpage" onchange="this.form.submit()">
        <option value="3" '.($perpage == 3 ? 'selected' : '').'>3</option>
        <option value="5" '.($perpage == 5 ? 'selected' : '').'>5</option>
        <option value="10" '.($perpage == 10 ? 'selected' : '').'>10</option>
        <option value="-1" '.($perpage == -1 ? 'selected' : '').'>All</option>
    </select>
</form>';

// === Display courses ===

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
    // === DEFAULT CATEGORY VIEW (with empty categories too) ===

    // Get all categories
    $all_categories = $DB->get_records('course_categories', null, 'sortorder ASC');
    $total_categories = count($all_categories);
    $all_categories = array_slice($all_categories, $offset, $limit); // Apply pagination

    // Get all courses grouped by category
    $courses = $DB->get_records_sql("
        SELECT c.*, cc.name AS categoryname, cc.id AS categoryid
        FROM {course} c
        LEFT JOIN {course_categories} cc ON cc.id = c.category
        WHERE c.category != 0
        ORDER BY cc.sortorder ASC, c.sortorder ASC
    ");

    $grouped_courses = [];
    foreach ($courses as $course) {
        $grouped_courses[$course->categoryid][] = $course;
    }

    // Display each category (even if empty)
    foreach ($all_categories as $category) {
        $categoryid = $category->id;

        $companyname = $DB->get_field_sql("
            SELECT name FROM {company} WHERE category = :categoryid
        ", ['categoryid' => $categoryid]);

        echo '<div class="category-box">';
        echo '<div class="category-title" value="' . $categoryid . '">' . 
            format_string($category->name) .
            ($companyname ? ' (' . format_string($companyname) . ')' : '') .
            '</div>';

        if (!empty($grouped_courses[$categoryid])) {
            echo '<ul class="course-list">';
            foreach ($grouped_courses[$categoryid] as $course) {
                echo '<li><a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '">' .
                     format_string($course->fullname) . '</a></li>';
            }
            echo '</ul>';
        } else {
            echo '<div class="no-courses">No courses in this category.</div>';
        }

        echo '</div>';
    }

    // === Pagination Links ===
    $total_pages = ceil($total_categories / $limit);

    if ($perpage != -1 && $total_pages > 1) {
        echo '<div class="pagination">';
        $page_range = 2;
        $start_page = max(0, $page - $page_range);
        $end_page = min($total_pages - 1, $page + $page_range);

        // First page link
        if ($start_page > 0) {
            echo '<a href="?page=0&search=' . urlencode($search) . '&perpage=' . $perpage . '" class="page-link">1</a>';
            if ($start_page > 1) echo '<span class="dots">...</span>';
        }

        for ($i = $start_page; $i <= $end_page; $i++) {
            $active = ($i == $page) ? 'active' : '';
            echo '<a href="?page=' . $i . '&search=' . urlencode($search) . '&perpage=' . $perpage . '" class="page-link ' . $active . '">' . ($i + 1) . '</a>';
        }

        // Last page link
        if ($end_page < $total_pages - 1) {
            if ($end_page < $total_pages - 2) echo '<span class="dots">...</span>';
            echo '<a href="?page=' . ($total_pages - 1) . '&search=' . urlencode($search) . '&perpage=' . $perpage . '" class="page-link">' . $total_pages . '</a>';
        }

        echo '</div>';
    }
}

echo '</div>'; // .container
echo $OUTPUT->footer();
