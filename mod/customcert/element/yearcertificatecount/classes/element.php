<?php
// This file is part of the customcert module for Moodle - http://moodle.org/
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
 * This file contains the customcert element yearcertificatecount's core interaction API.
 *
 * @package    customcertelement_yearcertificatecount
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace customcertelement_yearcertificatecount;

/**
 * The customcert element yearcertificatecount's core interaction API.
 *
 * @package    customcertelement_yearcertificatecount
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element extends \mod_customcert\element {

    /**
     * Handles rendering the element on the pdf.
     *
     * @param \pdf $pdf the pdf object
     * @param bool $preview true if it is a preview, false otherwise
     * @param \stdClass $user the user we are rendering this for
     */
    public function render($pdf, $preview, $user) {
        \mod_customcert\element_helper::render_content($pdf, $this, $this->getCertificateNo($user));
    }

    /**
     * Render the element in html.
     *
     * This function is used to render the element when we are using the
     * drag and drop interface to position it.
     *
     * @return string the html
     */
    public function render_html() {
    	global $USER;
        return $this->getCertificateNo($USER);
            //return \mod_customcert\element_helper::render_html_content($this, fullname($USER));
    }
    private function getCertificateNo($user){
        global $DB;
        $page = $DB->get_record('customcert_pages', ['id' => $this->get_pageid()], '*', MUST_EXIST);
        $templateid = $page->templateid;
        $customcert = $DB->get_record('modules', array("name"=>"customcert")); 
        $crt_elementtid = $this->get_id();
        $courseid = \mod_customcert\element_helper::get_courseid($crt_elementtid);
        $context = \mod_customcert\element_helper::get_context($crt_elementtid);
        $cmid = $context->instanceid;

        $year = date("y");
        // $finalno = "{$year}-{$courseid}-{$user->id}-{$cmid}";
        $getassignyrcount = $DB->get_record_sql("SELECT * FROM {certificate_yearcounter} WHERE year = ? AND course_id = ?",array($year,$courseid));
        if(empty($getassignyrcount)){
            $userdatabycourse = $DB->get_record("certificate_yearcounter",array("cmid"=>$cmid,"user_id"=>$user->id));

            if($userdatabycourse){
                $finalno = "{$userdatabycourse->year}-" . sprintf("%05d", $userdatabycourse->sequence);
            }else{
                $certificatecount = new \stdClass();
                $certificatecount->year         = $year;
                $certificatecount->sequence     = 1;
                $certificatecount->course_id    = $courseid;
                $certificatecount->cmid         = $cmid;
                $certificatecount->user_id      = $user->id;
                $certificatecount->created_date = time();
                $insert_certificatecount = $DB->insert_record("certificate_yearcounter",$certificatecount);
                $finalno = "{$certificatecount->year}-" . sprintf("%05d", $certificatecount->sequence);
            }
        }else{
            $userassigncount = $DB->get_record_sql("SELECT * FROM {certificate_yearcounter} WHERE year = ? AND cmid = ? AND user_id = ?", array($year,$cmid,$user->id));
            if($userassigncount){
                $finalno = "{$userassigncount->year}-" . sprintf("%05d", $userassigncount->sequence);
            }else{
                $checkusercount = $DB->get_record_sql("SELECT * FROM {certificate_yearcounter} WHERE cmid = ? AND user_id = ?", array($cmid,$user->id));
                if($checkusercount){
                    $finalno = "{$checkusercount->year}-" . sprintf("%05d", $checkusercount->sequence);
                }else{
                    $gethightestcount = $DB->get_record_sql("SELECT MAX(sequence) as sequencenumber  FROM {certificate_yearcounter} WHERE year = ? AND course_id = ?",array($year,$courseid));
                    $newsequencenumber = $gethightestcount->sequencenumber + 1;
                    $certificatecount = new \stdClass();
                    $certificatecount->year         = $year;
                    $certificatecount->sequence     = $newsequencenumber;
                    $certificatecount->course_id    = $courseid;
                    $certificatecount->cmid         = $cmid;
                    $certificatecount->user_id      = $user->id;
                    $certificatecount->created_date = time();
                    $insert_certificatecount = $DB->insert_record("certificate_yearcounter",$certificatecount);
                    $finalno = "{$certificatecount->year}-" . sprintf("%05d", $newsequencenumber);
                }
            }
        }
        return $finalno; 
    }
} 
