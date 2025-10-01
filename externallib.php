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

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function setgrade_parameters() {
        return new external_function_parameters(array(
            'contentid' => new external_value(PARAM_INT, 'H5P content id'),
            'score' => new external_value(PARAM_FLOAT, 'H5P score'),
            'maxscore' => new external_value(PARAM_FLOAT, 'H5P max score')
        ));
    }

    /**
     * Returns status
     * @return array user data
     */
    public static function setgrade($contentid, $score, $maxscore) {
        global $SESSION, $DB, $CFG;
        require_once($CFG->dirroot . '/mod/hvp/lib.php');
        $cm = get_coursemodule_from_instance('hvp', $contentid);


        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(
            self::setgrade_parameters(),
            array(
                'contentid' => $contentid,
                'score' => $score,
                'maxscore' => $maxscore
            )
        );

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = \context_system::instance();
        self::validate_context($context);

        $courseid = $cm->course;
        //$course_already_completed = utils::is_course_completed($courseid);

        $section_id = $cm->section;
        $section = $DB->get_record('course_sections', array('id' => $section_id));
        //$parent_chapter = utils::get_parent_chapter($section);
        //$info = utils::get_chapter_info($parent_chapter);
        /*
        $chapter_already_completed = false;
        if ($info['completed']) {
            $chapter_already_completed = true;
        }
*/
        // if ($info['completed']) {
        //   $chapter_already_completed = true;
        // }

        $progress = utils::setgrade($contentid, $score, $maxscore);
        //$section = $DB->get_record('course_sections', array('id' => $progress['sectionid']));
        //$courseid = $section->course;
        //$course_already_completed = is_course_completed($courseid);


        return array(
            'sectionid' => $progress['sectionid'],
            'percentage' => $progress['percentage'],
            'courseid' => $courseid,
            'sectionid' => $section_id,
            'cmid' => $cm->id,
        );
    }

    /**
     * Returns description of method result value
     * @return external_single_structure
     */
    public static function setgrade_returns() {
        return new \external_single_structure(array(
            'sectionid' => new external_value(PARAM_INT, 'Section ID'),
            'percentage' => new external_value(PARAM_FLOAT, 'Percentage of section progress'),
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'cmid' => new external_value(PARAM_INT, 'Course Module ID'),
        ), 'Section progress');
    }
}
