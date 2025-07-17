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
 * Contains the default section controls output class.
 *
 * @package   format_mooin1pager
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_mooin1pager\output\courseformat\content\section;

use core_courseformat\output\local\content\section\header as header_base;
use renderer_base;
use stdClass;

class header extends header_base {
    /** @var course_format the course format class */
    protected $format;

    /** @var section_info the course section class */
    protected $section;
    public function export_for_template(\renderer_base $output): stdClass {


        // Basis-Daten abrufen
        $data = parent::export_for_template($output);

        // Titel überschreiben
        $section = $this->section;
        $course = $this->format->get_course();

        $data->title = $output->section_title_without_link($section, $course);

        return $data;
    }
}
