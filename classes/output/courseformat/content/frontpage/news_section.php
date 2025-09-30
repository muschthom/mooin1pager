<?php

namespace format_mooin1pager\output\courseformat\content\frontpage;

use renderable;
use core_courseformat\base as course_format;
use moodle_url;
use format_mooin1pager\local\utils as utils;
use context_course;

/**
 * Base class to render the course news section.
 *
 * @package   format_mooin1pager
 * @copyright 2023 ISy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class news_section implements renderable {

    /** @var course_format the course format class */
    private $format;


    public function __construct(course_format $format) {
        $this->format = $format;
    }

    public function export_for_template(\renderer_base $output) {
        global $DB;

        $course = $this->format->get_course();

        // Default to null to avoid undefined variable notices when no news forum exists.
        $newsforumUrl = null;

        if ($forum = $DB->get_record('forum', array('course' => $course->id, 'type' => 'news'))) {
            if ($module = $DB->get_record('modules', array('name' => 'forum'))) {
                if($cm = $DB->get_record('course_modules', array('module' => $module->id, 'instance'=>$forum->id))){
                   $newsforumUrl = new moodle_url('/mod/forum/view.php', array('id' => $cm->id));

                }
            }
        }

        $last_post = utils::get_last_news($course->id, 'news');

        $unread = 0;
        if (is_array($last_post) && array_key_exists('unread_news_number', $last_post)) {
            $unread = (int)$last_post['unread_news_number'];
        }

        $data = (object)[
            'newsforumUrl' => $newsforumUrl,
            'previewPost' => $last_post,
            'unreadNewsNumber' => $unread,
        ];

        

        if ($unread == 0) {
            $data->no_new_news = true;
        } else if ($unread === 1) {
            $data->one_new_news = true;
        }

        $coursecontext = context_course::instance($course->id);
        if (has_capability('moodle/course:update', $coursecontext)) {
            $data->showGear = true;
        }

        return $data;
    }
}
