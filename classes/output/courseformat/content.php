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
 * Contains the default content output class.
 *
 * @package   format_mooin1pager
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_mooin1pager\output\courseformat;

use core_courseformat\output\local\content as content_base;
use core_courseformat\base as course_format;
use format_mooin1pager\output\courseformat\content\coursefrontpage as coursefrontpage;
use renderer_base;

/**
 * Base class to render a course content.
 *
 * @package   format_mooin1pager
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends content_base {

    /** @var coursefrontpage the course frontpage class */
    protected $coursefrontpage;

    /**
     * @var bool Topic format has also add section after each topic.
     */
    protected $hasaddsection = true;

    public function __construct(course_format $format) {
        parent::__construct($format);
        $this->coursefrontpage = new coursefrontpage($format);
    }


    public function get_template_name(\renderer_base $renderer): string {
        return 'format_mooin1pager/local/content';
    }


    /**
     * Export this data so it can be used as the context for a mustache template (core/inplace_editable).
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE;

        $format = $this->format;
        $coursefrontpage = $this->coursefrontpage;

        $data = (object)[
            'title' => $format->page_title(),
            'format' => $format->get_format(),
            'frontpage' => $coursefrontpage->export_for_template($output),
        ];


        // Standard Topics Datenstruktur abrufen.
        $parentdata = parent::export_for_template($output);

        // WICHTIG: Explizit die Sections aus dem Parentdata übernehmen.
        if (!empty($parentdata->sections)) {
            $data->sections = $parentdata->sections;
        }

        /*
        echo "<br>----------Parentdata-------------<br/>";
        echo json_encode($parentdata);
        echo "<br>----------data-------------<br/>";
        echo json_encode($data);
*/
        $PAGE->requires->js_call_amd('format_mooin1pager/mutations', 'init');
        $PAGE->requires->js_call_amd('format_mooin1pager/section', 'init');

        return $data; 
    }
}
