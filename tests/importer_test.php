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
 *
 * Tests for the importer
 *
 * @package    tool_uploadexternalcontent
 * @copyright  2019-2020 LushOnline
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_uploadexternalcontent;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/csvlib.class.php');

/**
 *
 * Test for the importer
 *
 * @package    tool_uploadexternalcontent
 * @copyright  2019-2020 LushOnline
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \tool_uploadexternalcontent_importer
 */
class importer_test extends \advanced_testcase {

    /**
     * Confirms that a single course and single activity can be created
     *
     * @return void
     * @covers \tool_uploadexternalcontent_importer
     */
    public function test_create_comma() {
        global $DB;

        $idnumber = 'C1b49aa30-e719-11e6-9835-f723b46a2688';

        $this->resetAfterTest(true);
        $this->assertFalse($DB->record_exists('course', array('idnumber' => $idnumber)));

        $source = __DIR__.'/fixtures/onecourse.csv';
        $content = file_get_contents($source);

        $importer = new \tool_uploadexternalcontent_importer($content, null, null);
        $importid = $importer->get_importid();

        $importer = new \tool_uploadexternalcontent_importer(null, null, null, null, $importid, null);
        $importer->execute();

        $course = null;
        $externalcontent = null;
        $cm = null;

        $params = array('idnumber' => $idnumber);
        if ($course = $DB->get_record('course', $params)) {
            $params = array('idnumber' => $idnumber, 'course' => $course->id);
            $cm = $DB->get_record('course_modules', $params);
            $params = array('id' => $cm->instance, 'course' => $course->id);
            $externalcontent = $DB->get_record('externalcontent', $params);
        };

        // Course exists.
        $this->assertTrue($course->idnumber == $idnumber);

        // Course Module.
        $this->assertTrue($cm->idnumber == $idnumber);

        // External content exists.
        $this->assertTrue($externalcontent->id == $cm->instance);
    }

    /**
     * Confirms an error text is returned if an invalid category id is used as parent
     *
     * @return void
     * @covers \tool_uploadexternalcontent_importer
     */
    public function test_invalid_parentcategory() {
        global $DB;
        $this->resetAfterTest(true);

        $idnumber = 'C1b49aa30-e719-11e6-9835-f723b46a2688';

        $noneexistentid = 99999;
        $currentcategories = $DB->get_records('course_categories', null, 'id desc', '*', 0, 1);
        if (count($currentcategories) != 0) {
            $lastcategory = array_pop($currentcategories);
            $noneexistentid = $lastcategory->id + $noneexistentid;;
        }

        $source = __DIR__.'/fixtures/onecourse.csv';
        $content = file_get_contents($source);

        $importer = new \tool_uploadexternalcontent_importer($content, null, null);
        $importid = $importer->get_importid();

        $importer = new \tool_uploadexternalcontent_importer(null, null, null, $noneexistentid , $importid, null);
        $importer->execute();

        $this->assertFalse($DB->record_exists('course', array('idnumber' => $idnumber)));
        $this->assertTrue($importer->haserrors(), 'Error Messages: '.implode(PHP_EOL, $importer->geterrors()));
    }


    /**
     * Confirms an error text is returned if empty CSV file
     *
     * @return void
     * @covers \tool_uploadexternalcontent_importer
     */
    public function test_empty_csv() {
        $this->resetAfterTest(true);

        $source = __DIR__.'/fixtures/empty.csv';
        $content = file_get_contents($source);

        $importer = new \tool_uploadexternalcontent_importer($content, null, null);

        $this->assertTrue($importer->haserrors(), 'Error Messages: '.implode(PHP_EOL, $importer->geterrors()));
    }

    /**
     * Confirms an error text is returned if not enough columns in CSV file
     *
     * @return void
     * @covers \tool_uploadexternalcontent_importer
     */
    public function test_not_enough_columns() {
        $this->resetAfterTest(true);

        $source = __DIR__.'/fixtures/notenoughcolumns.csv';
        $content = file_get_contents($source);

        $importer = new \tool_uploadexternalcontent_importer($content, null, null);

        $this->assertTrue($importer->haserrors(), 'Error Messages: '.implode(PHP_EOL, $importer->geterrors()));
    }
}
