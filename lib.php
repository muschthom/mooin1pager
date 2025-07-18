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
 * This file contains main class for mooin1pager course format.
 *
 * @since     Moodle 2.0
 * @package   format_mooin1pager
 * @copyright 2009 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/format/lib.php');

use core\output\inplace_editable;

/**
 * Main class for the mooin1pager course format.
 *
 * @package    format_mooin1pager
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_mooin1pager extends core_courseformat\base {

    /**
     * Returns true if this course format uses sections.
     *
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    public function uses_course_index() {
        $course = $this->get_course();
        $courseid = $course->id;
        if (get_toggle_mooin1pgr_courseindex_visibility($courseid) === 1){
            return true;
        } else {
            return false;
        }
    }

    public function uses_indentation(): bool {
        return (get_config('format_mooin1pager', 'indentation')) ? true : false;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * Use section name is specified by user. Otherwise use default ("Topic #").
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string(
                $section->name,
                true,
                ['context' => context_course::instance($this->courseid)]
            );
        } else {
            return $this->get_default_section_name($section);
        }
    }

    /**
     * Get the course display value for the current course.
     *
     * Formats extending topics or weeks will use coursedisplay as this setting name
     * so they don't need to override the method. However, if the format uses a different
     * display logic it must override this method to ensure the core renderers know
     * if a COURSE_DISPLAY_MULTIPAGE or COURSE_DISPLAY_SINGLEPAGE is being used.
     *
     * @return int The current value (COURSE_DISPLAY_MULTIPAGE or COURSE_DISPLAY_SINGLEPAGE)
     */
    public function get_course_display(): int {
        return COURSE_DISPLAY_SINGLEPAGE;
    }

    /**
     * Returns the default section name for the mooin1pager course format.
     *
     * If the section number is 0, it will use the string with key = section0name from the course format's lang file.
     * If the section number is not 0, it will consistently return the name 'newsection', disregarding the specific section number.
     *
     * @param int|stdClass $section Section object from database or just field course_sections section
     * @return string The default value for the section name.
     */
    public function get_default_section_name($section) {
        $section = $this->get_section($section);
        if ($section->sectionnum == 0) {
            return get_string('section0name', 'format_mooin1pager');
        }

        return get_string('newsection', 'format_mooin1pager');
    }

    /**
     * Generate the title for this section page.
     *
     * @return string the page title
     */
    public function page_title(): string {
        return get_string('sectionoutline');
    }

    /**
     * The URL to use for the specified course (with section).
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if omitted the course view page is returned
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section not empty, the function returns section page; otherwise, it returns course page.
     *     'sr' (int) used by course formats to specify to which section to return
     * @return null|moodle_url
     */
    public function get_view_url($section, $options = []) {
        $course = $this->get_course();
        if (array_key_exists('sr', $options) && !is_null($options['sr'])) {
            $sectionno = $options['sr'];
        } else if (is_object($section)) {
            $sectionno = $section->section;
        } else {
            $sectionno = $section;
        }
        if ((!empty($options['navigation']) || array_key_exists('sr', $options)) && $sectionno !== null) {
            // Display section on separate page.
            $sectioninfo = $this->get_section($sectionno);
            return new moodle_url('/course/section.php', ['id' => $sectioninfo->id]);
        }

        return new moodle_url('/course/view.php', ['id' => $course->id]);
    }

    /**
     * Returns the information about the ajax support in the given source format.
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    public function supports_components() {
        return true;
    }

    /**
     * Loads all of the course sections into the navigation.
     *
     * @param global_navigation $navigation
     * @param navigation_node $node The course node within the navigation
     * @return void
     */
    public function extend_course_navigation($navigation, navigation_node $node) {
        global $PAGE;
        // If section is specified in course/view.php, make sure it is expanded in navigation.
        if ($navigation->includesectionnum === false) {
            $selectedsection = optional_param('section', null, PARAM_INT);
            if (
                $selectedsection !== null && (!defined('AJAX_SCRIPT') || AJAX_SCRIPT == '0') &&
                $PAGE->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)
            ) {
                $navigation->includesectionnum = $selectedsection;
            }
        }

        // Check if there are callbacks to extend course navigation.
        parent::extend_course_navigation($navigation, $node);

        // We want to remove the general section if it is empty.
        $modinfo = get_fast_modinfo($this->get_course());
        $sections = $modinfo->get_sections();
        if (!isset($sections[0])) {
            // The general section is empty to find the navigation node for it we need to get its ID.
            $section = $modinfo->get_section_info(0);
            $generalsection = $node->get($section->id, navigation_node::TYPE_SECTION);
            if ($generalsection) {
                // We found the node - now remove it.
                $generalsection->remove();
            }
        }
        $PAGE->requires->js_call_amd('format_mooin1pager/custom', 'init');
    }

    /**
     * Custom action after section has been moved in AJAX mode.
     *
     * Used in course/rest.php
     *
     * @return array This will be passed in ajax respose
     */
    public function ajax_section_move() {
        global $PAGE;
        $titles = [];
        $course = $this->get_course();
        $modinfo = get_fast_modinfo($course);
        $renderer = $this->get_renderer($PAGE);
        if ($renderer && ($sections = $modinfo->get_section_info_all())) {
            foreach ($sections as $number => $section) {
                $titles[$number] = $renderer->section_title($section, $course);
            }
        }
        return ['sectiontitles' => $titles, 'action' => 'move'];
    }

    /**
     * Returns the list of blocks to be automatically added for the newly created course.
     *
     * @return array of default blocks, must contain two keys BLOCK_POS_LEFT and BLOCK_POS_RIGHT
     *     each of values is an array of block names (for left and right side columns)
     */
    public function get_default_blocks() {
        return [
            BLOCK_POS_LEFT => [],
            BLOCK_POS_RIGHT => [],
        ];
    }

    /**
     * Definitions of the additional options that this course format uses for course.
     *
     * mooin1pager format uses the following options:
     * - coursedisplay
     * - hiddensections
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');

            $courseformatoptions = [
                'hiddensections' => [
                    'default' => $courseconfig->hiddensections,
                    'type' => PARAM_INT,
                ],
                'toggle_mooin1pgr_welcome_message' => [
                    'default' => 1,  // Standardwert (0 = nicht ausgewählt)
                    'type' => PARAM_BOOL,  // Boolean-Wert (Checkbox)
                ],
                'toggle_mooin1pgr_courseindex_visibility' => [
                    'default' => 1,  // Standardwert (0 = nicht ausgewählt)
                    'type' => PARAM_BOOL,  // Boolean-Wert (Checkbox)
                ],
                'toggle_mooin1pgr_newssection_visibility' => [
                    'default' => 1,  // Standardwert (0 = nicht ausgewählt)
                    'type' => PARAM_BOOL,  // Boolean-Wert (Checkbox)
                ],
                'toggle_mooin1pgr_progressbar_visibility' => [
                    'default' => 1,  // Standardwert (0 = nicht ausgewählt)
                    'type' => PARAM_BOOL,  // Boolean-Wert (Checkbox)
                ],
                'toggle_mooin1pgr_badge_visibility' => [
                    'default' => 1,  // Standardwert (0 = nicht ausgewählt)
                    'type' => PARAM_BOOL,  // Boolean-Wert (Checkbox)
                ],
                'toggle_mooin1pgr_certificate_visibility' => [
                    'default' => 1,  // Standardwert (0 = nicht ausgewählt)
                    'type' => PARAM_BOOL,  // Boolean-Wert (Checkbox)
                ],
                'toggle_mooin1pgr_discussion_visibility' => [
                    'default' => 1,  // Standardwert (0 = nicht ausgewählt)
                    'type' => PARAM_BOOL,  // Boolean-Wert (Checkbox)
                ],
                'toggle_mooin1pgr_userlist_visibility' => [
                    'default' => 1,  // Standardwert (0 = nicht ausgewählt)
                    'type' => PARAM_BOOL,  // Boolean-Wert (Checkbox)
                ],
            ];
        }

        if ($foreditform) {
            $courseformatoptionsedit = [
                'hiddensections' => [
                    'label' => new lang_string('hiddensections'),
                    'help' => 'hiddensections',
                    'help_component' => 'moodle',
                    'element_type' => 'select',
                    'element_attributes' => [[
                        0 => new lang_string('hiddensectionscollapsed'),
                        1 => new lang_string('hiddensectionsinvisible')
                    ]],
                ],
                'toggle_mooin1pgr_welcome_message' => [
                    'label' => new lang_string('toggle_mooin1pgr_welcome_message', 'format_mooin1pager'),
                    'element_type' => 'advcheckbox',  // Checkbox-Typ für das Bearbeitungsformular
                    'help' => 'toggle_mooin1pgr_welcome_message',
                    'help_component' => 'format_mooin1pager',
                ],
                'toggle_mooin1pgr_courseindex_visibility' => [
                    'label' => new lang_string('toggle_mooin1pgr_courseindex_visibility', 'format_mooin1pager'),
                    'element_type' => 'advcheckbox',  // Checkbox-Typ für das Bearbeitungsformular
                    'help' => 'toggle_mooin1pgr_courseindex_visibility',
                    'help_component' => 'format_mooin1pager',
                ],
                'toggle_mooin1pgr_newssection_visibility' => [
                    'label' => new lang_string('toggle_mooin1pgr_newssection_visibility', 'format_mooin1pager'),
                    'element_type' => 'advcheckbox',  // Checkbox-Typ für das Bearbeitungsformular
                    'help' => 'toggle_mooin1pgr_newssection_visibility',
                    'help_component' => 'format_mooin1pager',
                ],
                'toggle_mooin1pgr_progressbar_visibility' => [
                    'label' => new lang_string('toggle_mooin1pgr_progressbar_visibility', 'format_mooin1pager'),
                    'element_type' => 'advcheckbox',  // Checkbox-Typ für das Bearbeitungsformular
                    'help' => 'toggle_mooin1pgr_progressbar_visibility',
                    'help_component' => 'format_mooin1pager',
                ],
                'toggle_mooin1pgr_badge_visibility' => [
                    'label' => new lang_string('toggle_mooin1pgr_badge_visibility', 'format_mooin1pager'),
                    'element_type' => 'advcheckbox',  // Checkbox-Typ für das Bearbeitungsformular
                    'help' => 'toggle_mooin1pgr_badge_visibility',
                    'help_component' => 'format_mooin1pager',
                ],
                'toggle_mooin1pgr_certificate_visibility' => [
                    'label' => new lang_string('toggle_mooin1pgr_certificate_visibility', 'format_mooin1pager'),
                    'element_type' => 'advcheckbox',  // Checkbox-Typ für das Bearbeitungsformular
                    'help' => 'toggle_mooin1pgr_certificate_visibility',
                    'help_component' => 'format_mooin1pager',
                ],
                'toggle_mooin1pgr_discussion_visibility' => [
                    'label' => new lang_string('toggle_mooin1pgr_discussion_visibility', 'format_mooin1pager'),
                    'element_type' => 'advcheckbox',  // Checkbox-Typ für das Bearbeitungsformular
                    'help' => 'toggle_mooin1pgr_discussion_visibility',
                    'help_component' => 'format_mooin1pager',
                ],
                'toggle_mooin1pgr_userlist_visibility' => [
                    'label' => new lang_string('toggle_mooin1pgr_userlist_visibility', 'format_mooin1pager'),
                    'element_type' => 'advcheckbox',  // Checkbox-Typ für das Bearbeitungsformular
                    'help' => 'toggle_mooin1pgr_userlist_visibility',
                    'help_component' => 'format_mooin1pager',
                ],
            ];

            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }
        return $courseformatoptions;
    }

    /**
     * Adds format options elements to the course/section edit form.
     *
     * This function is called from {@link course_edit_form::definition_after_data()}.
     *
     * @param MoodleQuickForm $mform form the elements are added to.
     * @param bool $forsection 'true' if this is a section edit form, 'false' if this is course edit form.
     * @return array array of references to the added form elements.
     */
    public function create_edit_form_elements(&$mform, $forsection = false) {
        global $COURSE;
        $elements = parent::create_edit_form_elements($mform, $forsection);

        if (!$forsection && (empty($COURSE->id) || $COURSE->id == SITEID)) {
            // Add "numsections" element to the create course form - it will force new course to be prepopulated
            // with empty sections.
            // The "Number of sections" option is no longer available when editing course, instead teachers should
            // delete and add sections when needed.
            $courseconfig = get_config('moodlecourse');
            $max = (int)$courseconfig->maxsections;
            $element = $mform->addElement('select', 'numsections', get_string('numberweeks'), range(0, $max ?: 52));
            $mform->setType('numsections', PARAM_INT);
            if (is_null($mform->getElementValue('numsections'))) {
                $mform->setDefault('numsections', $courseconfig->numsections);
            }
            array_unshift($elements, $element);
        }

        return $elements;
    }

    /**
     * Updates format options for a course.
     *
     * In case if course format was changed to 'mooin1pager', we try to copy options
     * 'coursedisplay' and 'hiddensections' from the previous format.
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data
     * @param stdClass $oldcourse if this function is called from {@link update_course()}
     *     this object contains information about the course before update
     * @return bool whether there were any changes to the options values
     */
    public function update_course_format_options($data, $oldcourse = null) {
        $data = (array)$data;
        if ($oldcourse !== null) {
            $oldcourse = (array)$oldcourse;
            $options = $this->course_format_options();
            foreach ($options as $key => $unused) {
                if (!array_key_exists($key, $data)) {
                    if (array_key_exists($key, $oldcourse)) {
                        $data[$key] = $oldcourse[$key];
                    }
                }
            }
        }
        return $this->update_format_options($data);
    }

    /**
     * Whether this format allows to delete sections.
     *
     * Do not call this function directly, instead use {@link course_can_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    /**
     * Indicates whether the course format supports the creation of a news forum.
     *
     * @return bool
     */
    public function supports_news() {
        return true;
    }

    /**
     * Returns whether this course format allows the activity to
     * have "triple visibility state" - visible always, hidden on course page but available, hidden.
     *
     * @param stdClass|cm_info $cm course module (may be null if we are displaying a form for adding a module)
     * @param stdClass|section_info $section section where this module is located or will be added to
     * @return bool
     */
    public function allow_stealth_module_visibility($cm, $section) {
        // Allow the third visibility state inside visible sections or in section 0.
        return !$section->section || $section->visible;
    }

    /**
     * Callback used in WS core_course_edit_section when teacher performs an AJAX action on a section (show/hide).
     *
     * Access to the course is already validated in the WS but the callback has to make sure
     * that particular action is allowed by checking capabilities
     *
     * Course formats should register.
     *
     * @param section_info|stdClass $section
     * @param string $action
     * @param int $sr
     * @return null|array any data for the Javascript post-processor (must be json-encodeable)
     */
    public function section_action($section, $action, $sr) {
        global $PAGE;

        if ($section->section && ($action === 'setmarker' || $action === 'removemarker')) {
            // Format 'mooin1pager' allows to set and remove markers in addition to common section actions.
            require_capability('moodle/course:setcurrentsection', context_course::instance($this->courseid));
            course_set_marker($this->courseid, ($action === 'setmarker') ? $section->section : 0);
            return null;
        }

        // For show/hide actions call the parent method and return the new content for .section_availability element.
        $rv = parent::section_action($section, $action, $sr);
        $renderer = $PAGE->get_renderer('format_mooin1pager');

        if (!($section instanceof section_info)) {
            $modinfo = course_modinfo::instance($this->courseid);
            $section = $modinfo->get_section_info($section->section);
        }
        $elementclass = $this->get_output_classname('content\\section\\availability');
        $availability = new $elementclass($this, $section);

        $rv['section_availability'] = $renderer->render($availability);
        return $rv;
    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of configuration settings
     * @since Moodle 3.5
     */
    public function get_config_for_external() {
        // Return everything (nothing to hide).
        $formatoptions = $this->get_format_options();
        $formatoptions['indentation'] = get_config('format_mooin1pager', 'indentation');
        return $formatoptions;
    }

    /**
     * Get the required javascript files for the course format.
     *
     * @return array The list of javascript files required by the course format.
     */
    public function get_required_jsfiles(): array {
        return [];
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place.
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return inplace_editable
 */
function format_mooin1pager_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            [$itemid, 'mooin1pager'],
            MUST_EXIST
        );
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}

function format_mooin1pager_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    require_login($course, true);

    if ($filearea != 'headerimagemobile' and $filearea != 'headerimagedesktop') {
        return false;
    }

    $itemid = (int)array_shift($args); // The first item in the $args array.

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // Array $args is empty => the path is '/'.
    } else {
        $filepath = '/' . implode('/', $args) . '/'; // Array $args contains elements of the filepath.
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'format_mooin1pager', $filearea, $itemid, '/', $filename);
    if (!$file) {
        return false; // The file does not exist.
    }

    // Finally send the file - in this case with a cache lifetime of 0 seconds and no filtering.
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

function get_toggle_mooin1pgr_courseindex_visibility($courseid) {
    $format = course_get_format($courseid); // Holt das Format für den aktuellen Kurs
    $formatoptions = $format->get_format_options(); // Holt alle Kursformatoptionen
    // Überprüfen, ob die benutzerdefinierte Option gesetzt ist
    if (isset($formatoptions['toggle_mooin1pgr_courseindex_visibility'])) {
        return $formatoptions['toggle_mooin1pgr_courseindex_visibility'];
    } else {
        $courseformatoptions = $format->course_format_options(false); // Standardoptionen holen
        return $courseformatoptions['toggle_mooin1pgr_courseindex_visibility']['default'];
    }
}

function get_toggle_mooin1pgr_newssection_visibility($courseid) {
    $format = course_get_format($courseid); // Holt das Format für den aktuellen Kurs
    $formatoptions = $format->get_format_options(); // Holt alle Kursformatoptionen
    // Überprüfen, ob die benutzerdefinierte Option gesetzt ist
    if (isset($formatoptions['toggle_mooin1pgr_newssection_visibility'])) {
        return $formatoptions['toggle_mooin1pgr_newssection_visibility'];
    } else {
        $courseformatoptions = $format->course_format_options(false); // Standardoptionen holen
        return $courseformatoptions['toggle_mooin1pgr_newssection_visibility']['default'];
    }
}

function get_toggle_mooin1pgr_progressbar_visibility($courseid) {
    $format = course_get_format($courseid); // Holt das Format für den aktuellen Kurs
    $formatoptions = $format->get_format_options(); // Holt alle Kursformatoptionen
    // Überprüfen, ob die benutzerdefinierte Option gesetzt ist
    if (isset($formatoptions['toggle_mooin1pgr_progressbar_visibility'])) {
        return $formatoptions['toggle_mooin1pgr_progressbar_visibility'];
    } else {
        $courseformatoptions = $format->course_format_options(false); // Standardoptionen holen
        return $courseformatoptions['toggle_mooin1pgr_progressbar_visibility']['default'];
    }
}

function get_toggle_mooin1pgr_discussion_visibility($courseid) {
    $format = course_get_format($courseid); // Holt das Format für den aktuellen Kurs
    $formatoptions = $format->get_format_options(); // Holt alle Kursformatoptionen
    // Überprüfen, ob die benutzerdefinierte Option gesetzt ist
    if (isset($formatoptions['toggle_mooin1pgr_discussion_visibility'])) {
        return $formatoptions['toggle_mooin1pgr_discussion_visibility'];
    } else {
        $courseformatoptions = $format->course_format_options(false); // Standardoptionen holen
        return $courseformatoptions['toggle_mooin1pgr_discussion_visibility']['default'];
    }
}

function get_toggle_mooin1pgr_userlist_visibility($courseid) {
    $format = course_get_format($courseid); // Holt das Format für den aktuellen Kurs
    $formatoptions = $format->get_format_options(); // Holt alle Kursformatoptionen
    // Überprüfen, ob die benutzerdefinierte Option gesetzt ist
    if (isset($formatoptions['toggle_mooin1pgr_userlist_visibility'])) {
        return $formatoptions['toggle_mooin1pgr_userlist_visibility'];
    } else {
        $courseformatoptions = $format->course_format_options(false); // Standardoptionen holen
        return $courseformatoptions['toggle_mooin1pgr_userlist_visibility']['default'];
    }
}

function get_toggle_mooin1pgr_badge_visibility($courseid) {
    $format = course_get_format($courseid); // Holt das Format für den aktuellen Kurs
    $formatoptions = $format->get_format_options(); // Holt alle Kursformatoptionen
    // Überprüfen, ob die benutzerdefinierte Option gesetzt ist
    if (isset($formatoptions['toggle_mooin1pgr_badge_visibility'])) {
        return $formatoptions['toggle_mooin1pgr_badge_visibility'];
    } else {
        $courseformatoptions = $format->course_format_options(false); // Standardoptionen holen
        return $courseformatoptions['toggle_mooin1pgr_badge_visibility']['default'];
    }
}

function get_toggle_mooin1pgr_certificate_visibility($courseid) {
    $format = course_get_format($courseid); // Holt das Format für den aktuellen Kurs
    $formatoptions = $format->get_format_options(); // Holt alle Kursformatoptionen
    // Überprüfen, ob die benutzerdefinierte Option gesetzt ist
    if (isset($formatoptions['toggle_mooin1pgr_certificate_visibility'])) {
        return $formatoptions['toggle_mooin1pgr_certificate_visibility'];
    } else {
        $courseformatoptions = $format->course_format_options(false); // Standardoptionen holen
        return $courseformatoptions['toggle_mooin1pgr_certificate_visibility']['default'];
    }
}

function get_toggle_mooin1pgr_welcome_message($courseid) {
    $format = course_get_format($courseid); // Holt das Format für den aktuellen Kurs
    $formatoptions = $format->get_format_options(); // Holt alle Kursformatoptionen
    // Überprüfen, ob die benutzerdefinierte Option gesetzt ist
    if (isset($formatoptions['toggle_mooin1pgr_welcome_message'])) {
        return $formatoptions['toggle_mooin1pgr_welcome_message'];
    } else {
        $courseformatoptions = $format->course_format_options(false); // Standardoptionen holen
        return $courseformatoptions['toggle_mooin1pgr_welcome_message']['default'];
    }
}