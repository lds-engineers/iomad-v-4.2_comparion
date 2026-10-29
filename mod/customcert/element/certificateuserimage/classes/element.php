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
 * This file contains the customcert element certificateuserimage's core interaction API.
 *
 * @package    customcertelement_certificateuserimage
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace customcertelement_certificateuserimage;

/**
 * The customcert element certificateuserimage's core interaction API.
 *
 * @package    customcertelement_certificateuserimage
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
        $usercontext = \context_user::instance($user->id);
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
        $usercontext = \context_user::instance($USER->id);
        return $this->getCertificateNo($USER);
            //return \mod_customcert\element_helper::render_html_content($this, fullname($USER));
    }
    private function getCertificateNo($user){
        global $DB,$CFG;
        // $usercontext = context_user::instance($user->id); // User context
        $usercontext = \context_user::instance($user->id);
        $page = $DB->get_record('customcert_pages', ['id' => $this->get_pageid()], '*', MUST_EXIST);
        $templateid = $page->templateid;
        $customcert = $DB->get_record('modules', array("name"=>"customcert")); 
        $cm = $DB->get_record('course_modules', array("course"=>$courseid , "module"=>$customcert->id, "deletioninprogress"=>0)); 
        $cmid = $cm->id;

        $cmid = $this->get_id();
        $courseid = \mod_customcert\element_helper::get_courseid($cmid);
        $course = get_course($courseid);
        $year = date("y");


         $usercertimage = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid= ".$usercontext->id." AND component= ? AND itemid= 0 AND filesize > 0 AND filearea = ?", array('profilefield_file','files_5'));
    
            $usercertimageurl = $CFG->wwwroot.'/blocks/custom_mycourse/file.php?id='.$usercertimage->pathnamehash;
            $finaluser_image =  '<img src="'.$usercertimageurl.'"  style="width: 30mm; height: 30mm">';
       






    	 //$finalno = "{$year}-{$courseid}-{$user->id}-{$cmid}";
    	
    	return $finaluser_image; 
    }
} 
