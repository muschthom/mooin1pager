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

namespace format_mooin1pager;

use core_external\external_api;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

/**
 * mooin1pager course format related unit tests.
 *
 * @package    format_mooin1pager
 * @copyright  2015 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_mooin1pager
 */
class format_mooin1pager_test extends \advanced_testcase {

    /**
     * Tests for format_mooin1pager::get_section_name method with default section names.
     *
     * @return void
     */
    public function test_get_section_name(): void {
        global $DB;
        $this->resetAfterTest(true);

        // Generate a course with 5 sections.
        $generator = $this->getDataGenerator();
        $numsections = 5;
        $course = $generator->create_course(['numsections' => $numsections, 'format' => 'mooin1pager'],
            ['createsections' => true]);

        // Get section names for course.
        $coursesections = $DB->get_records('course_sections', ['course' => $course->id]);

        // Test get_section_name with default section names.
        $courseformat = course_get_format($course);
        foreach ($coursesections as $section) {
            // Assert that with unmodified section names, get_section_name returns the same result as get_default_section_name.
            $this->assertEquals($courseformat->get_default_section_name($section), $courseformat->get_section_name($section));
        }
    }

    /**
     * Tests for format_mooin1pager::get_section_name method with modified section names.
     *
     * @return void
     */
    public function test_get_section_name_customised(): void {
        global $DB;
        $this->resetAfterTest(true);

        // Generate a course with 5 sections.
        $generator = $this->getDataGenerator();
        $numsections = 5;
        $course = $generator->create_course(['numsections' => $numsections, 'format' => 'mooin1pager'],
            ['createsections' => true]);

        // Get section names for course.
        $coursesections = $DB->get_records('course_sections', ['course' => $course->id]);

        // Modify section names.
        $customname = "Custom Section";
        foreach ($coursesections as $section) {
            $section->name = "$customname $section->section";
            $DB->update_record('course_sections', $section);
        }

        // Requery updated section names then test get_section_name.
        $coursesections = $DB->get_records('course_sections', ['course' => $course->id]);
        $courseformat = course_get_format($course);
        foreach ($coursesections as $section) {
            // Assert that with modified section names, get_section_name returns the modified section name.
            $this->assertEquals($section->name, $courseformat->get_section_name($section));
        }
    }

    /**
     * Tests for format_mooin1pager::get_default_section_name.
     *
     * @return void
     */
    public function test_get_default_section_name(): void {
        global $DB;
        $this->resetAfterTest(true);

        // Generate a course with 5 sections.
        $generator = $this->getDataGenerator();
        $numsections = 5;
        $course = $generator->create_course(['numsections' => $numsections, 'format' => 'mooin1pager'],
            ['createsections' => true]);

        // Get section names for course.
        $coursesections = $DB->get_records('course_sections', ['course' => $course->id]);

        // Test get_default_section_name with default section names.
        $courseformat = course_get_format($course);
        foreach ($coursesections as $section) {
            if ($section->section == 0) {
                $sectionname = get_string('section0name', 'format_mooin1pager');
                $this->assertEquals($sectionname, $courseformat->get_default_section_name($section));
            } else {
                $sectionname = get_string('newsection', 'format_mooin1pager');
                $this->assertEquals($sectionname, $courseformat->get_default_section_name($section));
            }
        }
    }

    /**
     * Test web service updating section name.
     *
     * @return void
     */
    public function test_update_inplace_editable(): void {
        global $CFG, $DB, $PAGE;
        require_once($CFG->dirroot . '/lib/external/externallib.php');

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $course = $this->getDataGenerator()->create_course(['numsections' => 5, 'format' => 'mooin1pager'],
            ['createsections' => true]);
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 2]);

        // Call webservice without necessary permissions.
        try {
            \core_external::update_inplace_editable('format_mooin1pager', 'sectionname', $section->id, 'New section name');
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertEquals('Course or activity not accessible. (Not enrolled)',
                    $e->getMessage());
        }

        // Change to teacher and make sure that section name can be updated using web service update_inplace_editable().
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $teacherrole->id);

        $res = \core_external::update_inplace_editable('format_mooin1pager', 'sectionname', $section->id, 'New section name');
        $res = external_api::clean_returnvalue(\core_external::update_inplace_editable_returns(), $res);
        $this->assertEquals('New section name', $res['value']);
        $this->assertEquals('New section name', $DB->get_field('course_sections', 'name', ['id' => $section->id]));
    }

    /**
     * Test callback updating section name.
     *
     * @return void
     */
    public function test_inplace_editable(): void {
        global $DB, $PAGE;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(['numsections' => 5, 'format' => 'mooin1pager'],
            ['createsections' => true]);
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $teacherrole->id);
        $this->setUser($user);

        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 2]);

        // Call callback format_mooin1pager_inplace_editable() directly.
        $tmpl = component_callback('format_mooin1pager', 'inplace_editable', ['sectionname', $section->id, 'Rename me again']);
        $this->assertInstanceOf('core\output\inplace_editable', $tmpl);
        $res = $tmpl->export_for_template($PAGE->get_renderer('core'));
        $this->assertEquals('Rename me again', $res['value']);
        $this->assertEquals('Rename me again', $DB->get_field('course_sections', 'name', ['id' => $section->id]));

        // Try updating using callback from mismatching course format.
        try {
            component_callback('format_weeks', 'inplace_editable', ['sectionname', $section->id, 'New name']);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertEquals(1, preg_match('/^Can\'t find data record in database/', $e->getMessage()));
        }
    }

    /**
     * Test get_default_course_enddate.
     *
     * @return void
     */
    public function test_default_course_enddate(): void {
        global $CFG, $DB;

        $this->resetAfterTest(true);

        require_once($CFG->dirroot . '/course/tests/fixtures/testable_course_edit_form.php');

        $this->setTimezone('UTC');

        $params = ['format' => 'mooin1pager', 'numsections' => 5, 'startdate' => 1445644800];
        $course = $this->getDataGenerator()->create_course($params);
        $category = $DB->get_record('course_categories', ['id' => $course->category]);

        $args = [
            'course' => $course,
            'category' => $category,
            'editoroptions' => [
                'context' => \context_course::instance($course->id),
                'subdirs' => 0
            ],
            'returnto' => new \moodle_url('/'),
            'returnurl' => new \moodle_url('/'),
        ];

        $courseform = new \testable_course_edit_form(null, $args);
        $courseform->definition_after_data();

        $enddate = $params['startdate'] + get_config('moodlecourse', 'courseduration');

        $weeksformat = course_get_format($course->id);
        $this->assertEquals($enddate, $weeksformat->get_default_course_enddate($courseform->get_quick_form()));

    }

    /**
     * Test for get_view_url().
     *
     * @covers ::get_view_url
     */
    public function test_get_view_url(): void {
        global $CFG;
        $this->resetAfterTest();

        // Generate a course with two sections (0 and 1) and two modules.
        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course(['format' => 'mooin1pager']);
        course_create_sections_if_missing($course1, [0, 1]);

        $data = (object)['id' => $course1->id];
        $format = course_get_format($course1);
        $format->update_course_format_options($data);

        // In page.
        $this->assertNotEmpty($format->get_view_url(null));
        $this->assertNotEmpty($format->get_view_url(0));
        $this->assertNotEmpty($format->get_view_url(1));

        // Navigation.
        $this->assertStringContainsString('course/view.php', $format->get_view_url(0));
        $this->assertStringContainsString('course/view.php', $format->get_view_url(1));
        $this->assertStringContainsString('course/section.php', $format->get_view_url(0, ['navigation' => 1]));
        $this->assertStringContainsString('course/section.php', $format->get_view_url(1, ['navigation' => 1]));
        // When sr parameter is defined, the section.php page should be returned.
        $this->assertStringContainsString('course/section.php', $format->get_view_url(0, ['sr' => 1]));
        $this->assertStringContainsString('course/section.php', $format->get_view_url(1, ['sr' => 1]));
        $this->assertStringContainsString('course/section.php', $format->get_view_url(0, ['sr' => 0]));
        $this->assertStringContainsString('course/section.php', $format->get_view_url(1, ['sr' => 0]));
    }

    /**
     * Test get_required_jsfiles().
     *
     * @covers ::get_required_jsfiles
     */
    public function test_get_required_jsfiles(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();

        $course = $generator->create_course(['format' => 'mooin1pager']);
        $format = course_get_format($course);
        $this->assertEmpty($format->get_required_jsfiles());
    }
}
