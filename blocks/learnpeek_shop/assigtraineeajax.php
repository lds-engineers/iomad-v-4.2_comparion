<?php
if ($_POST['action'] == 'assign_trainee_license') {
	require('../../config.php');
	require_once($CFG->dirroot . '/local/learnpeek_email_template/lib.php');
	require_login();
	global $DB, $PAGE, $CFG, $OUTPUT;
	$userid = $_POST['userid'];
	$licenseid = $_POST['licenseid'];
	$force = $_POST['force'];
	// $licensedata = $DB->get_record_sql("SELECT cl.*, count( distinct clu.userid) as usedlicence 
	// 									FROM {companylicense} cl 
	// 									LEFT JOIN {companylicense_users} clu on clu.licenseid = cl.id 
	// 									WHERE clu.licenseid = ?",[$licenseid]);

	$licensedata = $DB->get_record_sql("SELECT * FROM {companylicense} WHERE id = ?",[$licenseid]);

	if (!$licensedata) {
	    echo json_encode(['status' => 'error', 'message' => 'Invalid license']);
	    exit;
	}

	//****  Seat availability check ***//'
	if ($licensedata->allocation <= $licensedata->used) {
	    echo json_encode(['status' => 'error', 'message' => 'No seats available']);
	    exit;
	}

	//**** duplicate assignment ***//
	// $exists = $DB->record_exists('companylicense_users', [
	//     'licenseid' => $licenseid,
	//     'userid'    => $userid
	// ]);

	// if ($exists) {
	//     echo json_encode(['status' => 'error', 'message' => 'User already assigned']);
	//     exit;
	// }


	//**** Get mapped courses***//
	$mappedcourses = $DB->get_records_sql("SELECT * FROM {companylicense_courses} WHERE licenseid = ?",[$licenseid]);


	if (!$mappedcourses) {
	    echo json_encode(['status' => 'error', 'message' => 'No courses mapped']);
	    exit;
	}

	//**** Check course completion ***//

	if ($force == "false") {
	    $notcompletedcourses = [];
	    foreach ($mappedcourses as $course) {
	        $courseid = $course->courseid;
	        // Check enrollment
	        $isenrolled = is_enrolled(
	            context_course::instance($courseid),
	            $userid
	        );

	        if (!$isenrolled) {
	            continue; // Skip if not enrolled
	        }

	        // Check course completion record
	        $completion = $DB->get_record('course_completions', [
	            'course' => $courseid,
	            'userid' => $userid
	        ], 'id, timecompleted');

	        // Enrolled BUT not completed
	        if ($completion && empty($completion->timecompleted)) {
	            $notcompletedcourses[] = $courseid;
	        }
	    }

	    if (!empty($notcompletedcourses)) {
	        echo json_encode([
	            'status'  => 'warning',
	            'message' => 'User is already enrolled and has not completed the course(s). Progress will be reset if enrolled again.',
	            'courses' => $notcompletedcourses
	        ]);
	        exit;
	    }
	}

	// echo json_encode(['status' => 'success', 'message' => 'License assigned successfully']);
    // // exit;
	// die();
	foreach ($mappedcourses as $course) {
		$courseid = $course->courseid;

		$userlicense_enroll = $DB->get_records_sql("SELECT * FROM {companylicense_users} WHERE licensecourseid = ? AND userid = ?",array($courseid,$userid));
		if($userlicense_enroll){
			// uday

	            // Get course context.
	            $context = \context_course::instance($courseid);

	            // Check if user is already enrolled.
	            if (is_enrolled($context, $userid)) {

	                $enrolinstances = enrol_get_instances($courseid, true);
	                // $manualplugin = enrol_get_plugin('manual');

	                foreach ($enrolinstances as $instance) {
	                    $manualplugin = enrol_get_plugin($instance->enrol);
	                    if ($manualplugin) {
	                        // Pass the correct single instance, not the whole array
	                        $manualplugin->unenrol_user($instance, $userid);
	                    }
	                }

					delete_user_data_in_course($userid, $courseid);
	            }
	            // end
			foreach ($userlicense_enroll as $license_enroll) {
				 
				// $DB->delete_records('companylicense_users', ['id' => $license_enroll->id]);
				if(empty($license_enroll->timecompleted)){
					$license_enroll->timecompleted = time();
					$DB->update_record('companylicense_users', $license_enroll);

				}

			}
			// $DB->execute(
			//     "UPDATE {companylicense} SET used = used + 1 WHERE id = ?",
			//     [$licenseid]
			// );
		}
		$recordarray = array('licensecourseid' => $courseid,
                                                     'userid' => $userid,
                                                     'timecompleted' => null);
		if (!$DB->get_record('companylicense_users', $recordarray)) {

			$recordarray['licenseid'] = $licenseid;
            $recordarray['issuedate'] = time();
            $recordarray['isusing'] = 0;
            $recordarray['id'] = $DB->insert_record('companylicense_users', $recordarray);
            // $recordarray['id'] = 145;

             $eventother = array('licenseid' => $licenseid,
                                'issuedate' => $recordarray['issuedate'],
                                'duedate' => $licensedata->expirydate);

              // echo "#courseid {$courseid} / #objectid {$recordarray['id']} / #userid {$userid} / #other $eventother <br>";
              // print_r($eventother);
             $event = \block_iomad_company_admin\event\user_license_assigned::create(array('context' => \context_course::instance($courseid),
             																					'objectid' => $recordarray['id'],
             																					'courseid' => $courseid,
             																					'userid' => $userid,
             																					'other' => $eventother));
            $event->trigger();
            // $companylicenseinfo = $DB->get_record_sql(
			//     "SELECT ci.*, ii.groupid
			//        FROM {companylicense_info} ci
			//        INNER JOIN {invoiceitem} ii ON ii.id = ci.invoiceitem_id
			//       WHERE ci.companylicense_id = ?
			//         AND ii.groupid IS NOT NULL
			//         AND ii.groupid > 0",
			//     [$licenseid]
			// );

			// if (!empty($companylicenseinfo->groupid)) {
			//     groups_add_member($companylicenseinfo->groupid, $userid);
			// }
            // echo "<pre>";
            // print_r($event);
            // echo "</pre>";
            // die();
		}

	}
	echo json_encode(['status' => 'success', 'message' => 'License assigned successfully']);
    exit;
}
