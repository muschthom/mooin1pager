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

namespace format_mooin1pager\output\courseformat\state;

use core_courseformat\output\local\state\section as section_base;
use core_availability\info_section;
use core_courseformat\base as course_format;
use section_info;
use renderable;
use stdClass;
use context_course;
use renderer_base;
use format_mooin4\local\utils as utils;
use moodle_url;

/**
 * Contains the ajax update section structure.
 *
 * @package   core_course
 * @copyright 2021 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends section_base {

    protected $continuesection;

    /**
     * Constructor.
     *
     * @param course_format $format the course format
     * @param section_info $section the section info
     */
    // public function __construct(course_format $format, section_info $section) {
    //     global $USER;
    //     parent::__construct($format, $section);
    //     $course = $format->get_course();
    //     $sectionnumber = optional_param('section', 0, PARAM_INT);
    //     if ($sectionnumber > 0) {
    //         set_user_preference('format_mooin4_last_section_in_course_' . $course->id, $sectionnumber, $USER->id);
    //     }
    // }

    //protected $containsActiveSection = false;

    /**
     * Export this data so it can be used as state object in the course editor.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {
        $data = parent::export_for_template($output);
        $course = $this->format->get_course();

        $courseurl = new moodle_url('/course/view.php', ['id' => $course->id]);
        $data->courseurl = $courseurl->out(false);
        return $data;
    }
}
