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
 * @package    local_user_registration
 */
namespace local_user_registration;

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
                'username' => new external_value(PARAM_TEXT, 'The username'),
                'firstname' => new external_value(PARAM_TEXT, 'The firstname'),
                'lastname' => new external_value(PARAM_TEXT, 'The lastname id'),
                'email' => new external_value(PARAM_TEXT, 'The email id'),
                'wp_siteurl' => new external_value(PARAM_TEXT, 'The wp_siteurl'),
                'userid' => new external_value(PARAM_INT, 'The userid id'),
                'childParentId' => new external_value(PARAM_INT, 'The parent userid id'),
                'userrole' => new external_value(PARAM_TEXT, 'The userrole'),
                'company_name' => new external_value(PARAM_TEXT, 'The company name', VALUE_OPTIONAL),
                'courseid' => new external_value(PARAM_INT, 'The courseid id', VALUE_OPTIONAL),
                'is_verified' => new external_value(PARAM_INT, 'The is_verified id', VALUE_OPTIONAL),
                'trainee_ids' => new external_value(PARAM_TEXT, 'The company trainee name', VALUE_OPTIONAL),
                'wp_user_password' => new external_value(PARAM_TEXT, 'The user password', VALUE_OPTIONAL),
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
    public static function get_user_key($username, $firstname, $lastname, $email, $wp_siteurl, $userid, $childParentId, $userrole,$company_name = null, $courseid=0, $is_verified=0, $trainee_ids='',$wp_user_password='') {
        global $DB,$CFG;
        require_once($CFG->dirroot."/local/user_registration/locallib.php");
        $params = [
            'userid' => $userid,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'username' => $username,
            'email' => $email,
            'wp_siteurl' => $wp_siteurl,
            'userrole' => $userrole,
            'company_name' => $company_name,
            'childParentId' => $childParentId,
            'courseid' => $courseid,
            'is_verified' => $is_verified,
            'trainee_ids' => $trainee_ids,
            'wp_user_password' => $wp_user_password,
        ];

        $params = self::validate_parameters(self::get_user_key_parameters(), $params);
        $current_host = $_SERVER['HTTP_HOST'];
        $getcompany = $DB->get_record_sql("SELECT id,hostname FROM {company} WHERE hostname LIKE ?", array($current_host));
        // print_r($getcompany); die;
        $company_id = $getcompany->id;
        // $company_id = 40;

        if(empty($params['wp_user_password'])){
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*_";
            $pwd = substr( str_shuffle( $chars ), 0, 12 );
            $password=md5($pwd);
        }else{
            $password = $params['wp_user_password'];
        }

        $userinfo = $DB->get_record_sql("SELECT u.* FROM {user} u INNER JOIN {company_users} cu on cu.userid = u.id WHERE cu.companyid = :companyid and u.email = :email and u.deleted = 0 LIMIT 1",["email"=>$params['email'], "companyid"=>$company_id]);

        if(!empty($userinfo)){
            $update = false;
            if(!$userinfo->confirmed){
                $userinfo->confirmed = 1;
                $update = true;
            }
            // if($userinfo->suspended){
            //     $userinfo->suspended = 0;
            //     $update = true;
            // }
            if (trim($userinfo->firstname) !== trim($params['firstname'])) {
                $userinfo->firstname = trim($params['firstname']);
                $update = true;
            }
            if (trim($userinfo->lastname) !== trim($params['lastname'])) {
                $userinfo->lastname = trim($params['lastname']);
                $update = true;
            }
            if(!empty($params['wp_user_password'])){
                $userinfo->password = $params['wp_user_password'];
            }


            if($params['userrole'] ==="business"){
                $param1 = (object)[
                    "userid"=> $userinfo->id,
                    "company_id"=> $company_id,
                    "user_company_name"=> $company_name
                ];
                convert_individual_to_business_user_bysso($param1);
                if($params['trainee_ids']){

                    $getalltrainee_lmsids = $DB->get_records_sql("SELECT * FROM {remote_user_info} WHERE wp_userid in(".$params['trainee_ids'].")");

                    $alltrainee_lmsids = array_column($getalltrainee_lmsids, 'userid');

                    if($alltrainee_lmsids){
                        foreach ($alltrainee_lmsids as $traineeid) {
                            $checktrainee = $DB->get_record_sql("SELECT * FROM {business_user_child} WHERE company_id = ? AND parent_id = ? AND child_id = ?",array($company_id, $userinfo->id,$traineeid));

                            if(empty($checktrainee)){
                                $businesusrchild = new \stdclass();
                                $businesusrchild->company_id = $company_id;
                                $businesusrchild->parent_id = $userinfo->id;
                                $businesusrchild->child_id = $traineeid;
                                $businesusrchild->department_id = 0;
                                $businesusrchild->created_date = time();
                                $businesusrchild->created_by = $userinfo->id;
                                $busness_trainee = $DB->insert_record('business_user_child', $businesusrchild);

                            }
                        }
                    }
                }
            }

            if($params['is_verified']){
                $checkvarification = $DB->get_record("sso_validation", array("user_id"=>$userinfo->id));
                if(empty($checkvarification)){
                    $newrecord = new \stdClass();
                    $newrecord->user_id = $userinfo->id;
                    $newrecord->required_validation = 1;
                    $newrecord->created_time = time();
                    $insertid = $DB->insert_record('sso_validation', $newrecord);
                }
                
            }
            $checkverifieduser = $DB->get_record('sso_validation', array('user_id' => $userinfo->id, 'required_validation' => 1));
            if($checkverifieduser){
                if($update){
                    $DB->update_record("user", $userinfo);
                }
            }
            
        } else {
             // Check the username is already exist or not
            if ($DB->record_exists('user', ['username' => $username, 'deleted' => 0])) {
                $fname = $params['firstname'];
                $randamn = rand(100,1000);
                $username = generateusername($fname,$randamn); 
            } else {
                $username = $username;
            }

            $userdata  = new \stdClass();
            $userdata->username             = $username;
            $userdata->firstname            = $params['firstname'];
            $userdata->lastname             = $params['lastname'];
            $userdata->email                = strtolower($params['email']);
            $userdata->company_id           = $company_id;
            $userdata->user_company_name    = $params['company_name'];
            $userdata->password             = $password;
            if($params['userrole'] =="business"){
                $data = create_compnay_business_user_bysso($userdata);
                if(!empty($params['trainee_ids'])){
                    $getalltrainee_lmsids = $DB->get_records_sql("SELECT * FROM {remote_user_info} WHERE wp_userid in(".$params['trainee_ids'].")");
                    $alltrainee_lmsids = array_column($getalltrainee_lmsids, 'userid');
                    if($alltrainee_lmsids){
                        foreach ($alltrainee_lmsids as $traineeid) {
                            $businesusrchild = new \stdclass();
                            $businesusrchild->company_id = $company_id;
                            $businesusrchild->parent_id = $data;
                            $businesusrchild->child_id = $traineeid;
                            $businesusrchild->department_id = 0;
                            $businesusrchild->created_date = time();
                            $businesusrchild->created_by = $data;
                            $busness_trainee = $DB->insert_record('business_user_child', $businesusrchild);
                        }
                    }
                }
            }else{
                $data = create_individual_user_bysso($userdata);
            }
            if($data){
                $storeinfor = new \stdClass();
                $storeinfor->userid     = $data;
                $storeinfor->wp_userid = $params['userid'];
                $storeinfor->wp_siteurl = $params['wp_siteurl'];
                $storeinfor->wp_email = $params['email'];
                $storeinfor->company_id = $company_id;
                $storeinfor->created_date = time();
                $storeinfor->updated_date = time();
                $createstoreinfor = $DB->insert_record('remote_user_info', $storeinfor);

                $newrecord = new \stdClass();
                $newrecord->user_id = $data;
                $newrecord->required_validation = 1;
                $newrecord->created_time = time();
                $insertid = $DB->insert_record('sso_validation', $newrecord);
            }
            $userinfo = $DB->get_record("user", ['id' => $data]); 
        }
        // if(!empty($params['trainee_ids'])){
        //     $getalltrainee_lmsids = $DB->get_records_sql("SELECT * FROM {remote_user_info} WHERE wp_userid in(".$params['trainee_ids'].")");

        // }
        $checkremoteuser = $DB->get_records_sql(
            "SELECT * FROM {remote_user_info} 
                WHERE wp_userid = ?
                AND userid = ?
                AND company_id = ?",
                [$userid, $userinfo->id,$company_id]
            );
        if(empty($checkremoteuser)){
            $remoteinfor = new \stdClass();
            $remoteinfor->userid        = $userinfo->id;
            $remoteinfor->wp_userid     = $userid;
            $remoteinfor->wp_siteurl    = $params['wp_siteurl'];
            $remoteinfor->wp_email      = $params['email'];
            $remoteinfor->company_id    = $company_id;
            $remoteinfor->created_date  = time();
            $remoteinfor->updated_date  = time();
            $createremoteinfor = $DB->insert_record('remote_user_info', $remoteinfor);

        }

        // If user is child then map to their parent
        if (!empty($params['childParentId'])) {
            global $DB;

            // Get the parent's Moodle user ID from the remote_user_info table.
            $userInfo = $DB->get_record(
                'remote_user_info',
                ['wp_userid' => $params['childParentId']],
                'userid'
            );

            $parentMdlId = $userInfo ? (int) $userInfo->userid : 0;

            // Proceed only if parent Moodle ID exists.
            if ($parentMdlId > 0) {

                $businesusrchild = new \stdClass();
                $businesusrchild->company_id = $company_id;
                $businesusrchild->parent_id = $parentMdlId;
                $businesusrchild->child_id = $userinfo->id;
                $businesusrchild->department_id = 0;
                $businesusrchild->created_date = time();
                $businesusrchild->created_by = $userinfo->id;

                $DB->insert_record('business_user_child', $businesusrchild);
            }
        }
        
        $userkey = get_user_key('sso_lms_learnpeek', $userinfo->id);
        $usertokenkey = create_user_key('sso_lms_learnpeek', $userinfo->id);
        $return = [
            'id' => $userinfo->id,
            'firstname' => $userinfo->firstname,
            'lastname' => $userinfo->lastname,
            'email' => $userinfo->email,
            'wp_siteurl' => $params['wp_siteurl'],
            'userrole' => $params['userrole'],
            'userkey' => $usertokenkey,
            'is_verified' => $is_verified,
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
                'id' => new external_value(PARAM_INT, 'ID'),
                'firstname' => new external_value(PARAM_TEXT, 'The firstname'),
                'lastname' => new external_value(PARAM_TEXT, 'The lastname'),
                'email' => new external_value(PARAM_TEXT, 'The email'),
                'wp_siteurl' => new external_value(PARAM_TEXT, 'The wp_siteurl'),
                'userrole' => new external_value(PARAM_TEXT, 'The userrole'),
                'userkey' => new external_value(PARAM_TEXT, 'userkey')
            )
        );
    }
}
