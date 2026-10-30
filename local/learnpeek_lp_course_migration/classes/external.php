<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This is the external API for this tool.
 *
 * @package    local_learnpeek_lp_course_migration
 */
namespace local_learnpeek_lp_course_migration;

use core_external\external_api;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_function_parameters;

/**
 * This is the external API for this tool.
 *
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {

    /**
     * Returns the save_element() parameters.
     *
     * @return external_function_parameters
     */
    public static function get_user_key_parameters() {
        return new external_function_parameters(
            [
                'email' => new external_value(PARAM_TEXT, 'The email id'),
                'userid' => new external_value(PARAM_INT, 'The userid id'),
                'is_verified' => new external_value(PARAM_INT, 'The is_verified id', VALUE_OPTIONAL),
            ]
        );
    }

    /**
     * Handles saving element data.
     *
     * @param int $templateid The template id.
     * @param int $elementid The element id.
     * @param array $values The values to save
     * @return array
     */
    public static function get_user_key($email, $userid,$is_verified=0) {
        global $DB,$CFG;
        require_once($CFG->dirroot."/local/learnpeek_lp_course_migration/locallib.php");
        $params = [
            'userid' => $userid,
            'email' => $email,
            'is_verified' => $is_verified,
        ];
      
        $params = self::validate_parameters(self::get_user_key_parameters(), $params);
        $current_host = $_SERVER['HTTP_HOST'];
        $getcompany = $DB->get_record_sql("SELECT id,hostname FROM {company} WHERE hostname LIKE ?", array($current_host));
        $company_id = $getcompany->id;
        $userdata = $DB->get_record_sql("SELECT u.* FROM {user} u INNER JOIN {company_users} cu on cu.userid = u.id WHERE cu.companyid = :companyid and u.email = :email and u.deleted = 0 LIMIT 1",["email"=>$params['email'], "companyid"=>$company_id]);
        $status = false;
        $coursesdata = [];

        if ($userdata) {
            $userid = $userdata->id;
            if ($is_verified == 1) {
                $status = true;
                $message = "Account existing SSO verification done by Leanpeek";
                $coursesdata = lms_getuser_enrolled_course_data($userid);
            } else {
                $checkssovalidation = $DB->get_record_sql("SELECT * FROM {sso_validation} WHERE user_id = ?",array($userid));
                if ($checkssovalidation) {
                    $status = true;
                    $message = "Account existing SSO verification done by PDH Star";
                    $coursesdata = lms_getuser_enrolled_course_data($userid);
                } else {
                    $status = false;
                    $message = "SSO verification pending";
                }
            }
        } else {
            $status = false;
            $message = "User Account not found in LearnPress";
        }
       
        $return = [
            'status' => $status ? 1 : 0,
            'message' => $message,
            'coursesdata' => json_encode($coursesdata),  // convert to string
            'userid' => $userid,
        ];

        return $return;
    }

    /**
     * Returns the save_element result value.
     *
     * @return external_value
     */
    public static function get_user_key_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'status'),
                'message' => new external_value(PARAM_TEXT, 'The message'),
                'coursesdata' => new external_value(PARAM_RAW, 'Courses JSON'),
                'userid' => new external_value(PARAM_TEXT, 'The userid'),
            )
        );
    }

    //==============new API ===================//

    // Define the expected parameters for the user migration
    public static function migrate_user_parameters() {
        return new external_function_parameters(
            array(
                'user_data' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'firstname'    => new external_value(PARAM_TEXT, 'First name of the user'),
                            'lastname'     => new external_value(PARAM_TEXT, 'Last name of the user'),
                            'email'        => new external_value(PARAM_TEXT, 'Email address of the user'),
                            'userid'       => new external_value(PARAM_INT, 'WordPress User ID'),
                            'wp_siteurl' => new external_value(PARAM_TEXT, 'The wp_siteurl'),
                            'userrole'     => new external_value(PARAM_TEXT, 'Role of the user (business/individual)'),
                            'company_name' => new external_value(PARAM_TEXT, 'Company name', VALUE_OPTIONAL),
                            'company_host'     => new external_value(PARAM_TEXT, 'company host'),
                        )
                    )
                )
            )
        );
    }

    // The method that handles creating the user in Moodle
    public static function migrate_user($user_data) {
        global $DB, $CFG;
        require_once($CFG->dirroot."/local/user_registration/locallib.php");
        self::validate_parameters(self::migrate_user_parameters(), array('user_data' => $user_data));
        $response = []; 
        foreach ($user_data as $user) {
            $getcompany = $DB->get_record_sql("SELECT id,hostname FROM {company} WHERE hostname LIKE ?", array($user['company_host']));
            $company_id = $getcompany->id;

            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*_";
            $pwd = substr( str_shuffle( $chars ), 0, 12 );
            $password=md5($pwd);
            $userinfo = $DB->get_record_sql("SELECT u.* FROM {user} u INNER JOIN {company_users} cu on cu.userid = u.id WHERE cu.companyid = :companyid and u.email = :email and u.deleted = 0 LIMIT 1",["email"=>$user['email'], "companyid"=>$company_id]);
            $status = false;
            $migrated_user = [];
            $not_migrated_user = [];
            $message = "";
            if(!empty($userinfo)){
                $response[] = [
                    'email' => $user['email'],
                    'userid' => $user['userid'],
                    'lms_userid' => $userinfo->id,  // User aleary migrated
                    'status' => 1 , // sso verifyed
                    'message' => "Existing account users have been migrated",
                ];
                // $sso_validation = $DB->get_record_sql("SELECT * FROM {sso_validation} WHERE user_id = ?",array($userinfo->id));
                // if($sso_validation){
                //     $response[] = [
                //         'email' => $user['email'],
                //         'userid' => $user['userid'],
                //         'lms_userid' => $userinfo->id,  // User aleary migrated
                //         'status' => 1 , // sso verifyed
                //         'message' => "Account aleary migrated",
                //     ];
                // }else{
                //     $response[] = [
                //         'email' => $user['email'],
                //         'userid' => $user['userid'],
                //         'lms_userid' => $userinfo->id,
                //         'status' => 0,
                //         'message' => "Exiting Account need to verification",
                //     ];

                // } 
            } else {
                $fname = $user['firstname'];
                $randamn = rand(100,1000);
                $username = generateusername($fname,$randamn); 
                $userdata  = new \stdClass();
                $userdata->username             = $username;
                $userdata->firstname            = $user['firstname'];
                $userdata->lastname             = $user['lastname'];
                $userdata->email                = strtolower($user['email']);
                $userdata->company_id           = $company_id;
                $userdata->user_company_name    = $user['company_name'];
                $userdata->password             = $password;
                if($user['userrole'] =="business"){
                    $data = create_compnay_business_user($userdata);
                }else{
                    $data = create_individual_user($userdata);
                }
                if($data){
                    $storeinfor = new \stdClass();
                    $storeinfor->userid     = $data;
                    $storeinfor->wp_userid = $user['userid'];
                    $storeinfor->wp_siteurl = $user['wp_siteurl'];
                    $storeinfor->wp_email = $user['email'];
                    $storeinfor->company_id = $company_id;
                    $storeinfor->created_date = time();
                    $storeinfor->updated_date = time();
                    $createstoreinfor = $DB->insert_record('remote_user_info', $storeinfor);

                    $newrecord = new \stdClass();
                    $newrecord->user_id = $data;
                    $newrecord->required_validation = 1;
                    $newrecord->created_time = time();
                    $insertid = $DB->insert_record('sso_validation', $newrecord);

                    //migatration status
                    $response[] = [
                        'email' => $user['email'],
                        'userid' => $user['userid'],
                        'lms_userid' => $data,  // Moodle user ID
                        'status' => 1,
                        'message' => "User migrated successfully",
                    ];
                }else{
                    $response[] = [
                        'email' => $user['email'],
                        'userid' => $user['userid'],
                        'lms_userid' => null,
                        'status' => 2,
                        'message' => "Account are not migrated try again",
                    ];
                }
            }

        }
        return $response;

    }

    // Define the return type (true if the migration was successful)
    public static function migrate_user_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'email' => new external_value(PARAM_TEXT, 'User email'), // Email of the user
                    'userid' => new external_value(PARAM_INT, 'WordPress User ID'), // WordPress user ID
                    'lms_userid' => new external_value(PARAM_INT, 'Moodle User ID', VALUE_OPTIONAL), // Moodle user ID (optional)
                    'status' => new external_value(PARAM_INT, 'Status: 1 = migrated, 0 = not migrated'), // Migration status (1 for success, 0 for failure)
                    'message' => new external_value(PARAM_TEXT, 'message'),
                )
            )
        );
    }
}


