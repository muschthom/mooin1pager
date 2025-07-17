<?php

namespace format_mooin1pager;

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/course/lib.php');


use format_mooin1pager\local\utils as utils;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_format_value;
use core_external\external_value;
use core_external\external_warnings;


class format_mooin1pager_external extends external_api {

    // Keine Parameter erwartet
    public static function execute_parameters() {
    return new external_function_parameters([
        'courseid' => new external_value(PARAM_INT, 'ID of the course'),
        'dummy' => new external_value(PARAM_RAW, 'Optional dummy param', VALUE_DEFAULT, null)
    ]);
}

    // Funktion ausführen
    public static function execute($courseid, $dummy = null) {
        error_log('Executing get_course_progress');
        global $USER;

        $userid = $USER->id; // 👤 Aktueller eingeloggter User
        // Kein required_param() hier nötig

        $courseprogress = utils::get_course_progress($courseid, $userid);

        return [
            'courseprogress' => $courseprogress
        ];
    }

    // Rückgabewert definieren
    public static function execute_returns() {
        return new external_single_structure([
            'courseprogress' => new external_value(PARAM_INT, 'Dummy progress value')
        ]);
    }
}