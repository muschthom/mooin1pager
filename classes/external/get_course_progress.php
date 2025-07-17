<?php
require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/course/lib.php');

use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\context_system;

use format_mooin1pager\local\utils as utils;

class format_mooin1pager_external extends external_api {
    public static function execute_parameters() {
        error_log('Executing get_course_progress parameters');
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID')
        ]);
    }

    public static function execute($courseid) {
        error_log('Executing get_course_progress for course ID: ' . $courseid);
        return 100; 
        /*
        global $USER;

        $progress = utils::get_course_progress($USER->id, $courseid);

        return [
            'progress' => $progress
        ];
        */
    }

    public static function execute_returns() {
        error_log('Defining return structure for get_course_progress');
        return new external_single_structure([
            'progress' => new external_value(PARAM_FLOAT, 'Progress (0.0 - 1.0)')
        ]);
    }
}
