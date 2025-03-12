<?php

namespace format_mooin1pager\output\courseformat\content\frontpage;

use renderable;
use format_mooin1pager\local\utils as utils;
use core_courseformat\base as course_format;
use format_mooin1pager;
use moodle_url;
use context_course;


/**
 * Base class to render the course frontpage header.
 *
 * @package   format_mooin1pager
 * @copyright 2023 ISy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class header implements renderable {

    /** @var course_format the course format class */
    private $format;

    /**
     * Constructor.
     *
     * @param course $course the course
     */
    public function __construct(course_format $format) {
        $this->format = $format;
        //$this->chapterlib = new chapterlib();
        //$this->continue_section = $this->get_continue_section();
    }

    public function export_for_template(\renderer_base $output) {
        $course = $this->format->get_course();

        
        

        $headerimageurl = utils::get_headerimage_url($course->id, false);
        $headerimageURLMobile =  utils::get_headerimage_url($course->id, true);

        $data = (object)[
            'headerimageURL' => $headerimageurl,
            'headerimageURLMobile' => $headerimageURLMobile,
            'is_course_started' => utils::is_course_started($course),
            'continue_section' => utils::get_continue_section($course),
            'continue_url' => utils::get_continue_url($course),
        ];
        $coursecontext = context_course::instance($course->id);
        if (has_capability('moodle/course:update', $coursecontext)) {
            $editheaderlink = new moodle_url('/course/format/mooin1pager/edit_header.php', array('course' => $course->id));
            $data->editheaderlink = $editheaderlink;
        }

        return $data;
    }
}
