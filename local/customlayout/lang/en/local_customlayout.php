<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.


/**
 * Add page to admin menu.
 *
 * @package   local_wptomoodlemigration
 * @author lds
 * @copyright  2024, Lds <kteamlds.international@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Wordpress to Moodle Migration';
$string['wp_base_url'] = 'Base-Url';
$string['wp_base_url_heading'] = 'Setup Client Base-Url';
$string['certificates'] = 'Certificates';
$string['courses'] = 'Courses';
$string['team_trainee_list'] = 'My Team - Trainee Listing';
$string['orders'] = 'Orders';
$string['wishlist'] = 'Wishlist';
$string['quizes'] = 'Quizes list';
$string['events'] = 'Events list';
$string['settings'] = 'Settings';
// Uday
$string['companylogo'] = 'Company Logo (Dark Mode)';
$string['mobiletoplogo'] = 'Sidebar/Mobile Logo (Light Mode)';
$string['companyfavicon'] = 'Favicon';
$string['savechanges'] = 'Save';

// edit course feedback section
$string['editreview'] = 'Edit Review';
$string['rating'] = 'Rating';
$string['review'] = 'Review';
$string['hasreview'] = 'Has Review';
$string['timecreated'] = 'Time Created';
$string['pending'] = 'Pending';
$string['approved'] = 'Approved';
$string['status'] = 'Status';
$string['invalidrating'] = 'Rating must be between 1 and 5.';
$string['reviewtoolong'] = 'Review is too long. Maximum 1000 characters allowed.';
$string['invalidstatus'] = 'Invalid status value.';
$string['invalidhasreview'] = 'Invalid value for Has Review.';
$string['changessaved'] = 'Feedback updated successfully.';

// Reply to user
$string['user'] = 'User';
$string['review'] = 'Review';
$string['reply'] = 'Reply';
$string['submit'] = 'Submit';
$string['required'] = 'This field is required.';


// $string['managedepartment'] = 'Manage Department';
$string['addnewdepartment'] = 'Add New Department';
$string['enterdepartmentname'] = 'Enter department name';
$string['add'] = 'Add';
$string['departments'] = 'Departments';
$string['manage_users'] = 'Manage Individual Users';
$string['edit'] = 'Edit';
$string['delete'] = 'Delete';
$string['dashboard'] = 'Dashboard';
$string['myprofile'] = 'My Profile';
$string['shopcourses'] = 'Shop Courses';
$string['mycourses'] = 'My Courses';
$string['messages'] = 'Messages';
$string['logout'] = 'Logout';
$string['managewhitelabeladmin'] = 'Manage White Label Admin';
$string['managebusinessusers'] = 'Manage Business Users';
$string['createuser'] = 'Create user';
$string['businessusers'] = 'Business Users';
$string['assignbusinessrole'] = 'Assign Business Role';
$string['managedepartments'] = 'Manage Departments';
$string['manageusers'] = 'Manage Users';
$string['users'] = 'Users';
$string['bulkupload'] = 'Bulk Upload';
$string['enrolledusers'] = 'Enrolled users';
$string['editusers'] = 'Edit users';
$string['uploadusers'] = 'Upload users';
$string['downloadsample'] = 'Download sample';
$string['manageeventcourses'] = 'Manage Event Courses';
$string['manageseat'] = 'Manage Seat';
$string['eventcourselist'] = 'Event Course List';
$string['coursefeedback'] = 'Course Feedback';
$string['search'] = 'Search';
$string['clear'] = 'Clear';
$string['managecourses'] = 'Manage Courses';
$string['gradereport'] = 'Grade Report';
$string['manageevents'] = 'Manage Events';
$string['coupon'] = 'Coupon';
$string['manageseats'] = 'Manage Seats';
$string['bulkuserupload'] = 'Bulk User Upload';
$string['uploadcsvfile'] = 'Upload CSV File';
$string['upload'] = 'Upload';
$string['assigndepartmentusers'] = 'Assign Department Users: {$a}';
$string['allindividualusers'] = 'All Individual Users';
$string['assignedusers'] = 'Assigned Users';
$string['remove'] = 'Remove';
$string['general'] = 'General';
$string['course'] = 'Course';
$string['star'] = 'Star';
$string['createddate'] = 'Created Date';
$string['givenby'] = 'Given By';
$string['action'] = 'Action';
$string['replyto'] = 'Reply To';
$string['edit'] = 'Edit';
$string['delete'] = 'Delete';
$string['approve'] = 'Approve';
$string['replytoreview'] = 'Reply to Review';
$string['firstname'] = 'First Name';
$string['lastname'] = 'Last Name';
$string['email'] = 'Email';
$string['uploadid'] = 'Upload ID';
$string['image'] = 'Image';
$string['usercourses'] = 'User Courses';
$string['editstudent'] = 'Edit Student';
$string['studentfirstname'] = 'Student First Name';
$string['studentlastname'] = 'Student Last Name';
$string['name'] = 'Name';
$string['company'] = 'Company';
$string['viewdetails'] = 'View Details';
$string['courserectriction'] = 'Course Rectriction';
$string['edituser'] = 'Edit User';
$string['viewtraineelist'] = 'View Trainee List'; 
$string['managecategory'] = 'Manage Category';
$string['selectbusinessusers'] = 'Select Business Users';
$string['username'] = 'User Name';
$string['companyname'] = 'Company Name'; 
$string['courseassignement'] = 'Course Assignement';
$string['assign'] = 'Assign';
$string['migratelearnpresscourse'] = 'Migrate Learnpress course';
$string['migratelearnpressfeedback'] = 'Migrate Learnpress Feedback';
$string['allcourse'] = 'All Course';
$string['managecompany'] = 'Manage Company';
$string['selectcompany'] = 'Select Company';
$string['managepayment'] = 'Manage Payment';
$string['productcategory'] = 'Product Category';
$string['scanfacecourse'] = 'Scanface Course';
$string['slno'] = 'Sl.No.';
$string['category'] = 'Category';
$string['coursename'] = 'Course Name';
$string['enabledisable'] = 'Enable/Disable';
$string['save'] = 'Save';
$string['emailtemplate'] = 'Email Templates'; 
$string['coursefeedback'] = 'Course Feedback';
$string['selectdepartment'] = 'Select Department'; 
$string['pleaseselectdepartment'] = 'Please Select Department';
