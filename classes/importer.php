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
 * This file contains the procesing for the add/update of a single external content course.
 *
 * @package   tool_uploadexternalcontent
 * @copyright 2019-2023 LushOnline
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_uploadexternalcontent;

use \tool_uploadexternalcontent\helper;
use \tool_uploadexternalcontent\tracker;

/**
 * Main processing class for adding and updating single external content course.
 *
 * @package   tool_uploadexternalcontent
 * @copyright 2019-2023 LushOnline
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class importer {

    /**
     * @var array $error   Last error message.
     */
    public $error = array();

    /**
     * @var array $importedrows   The rows of imported data to process.
     */
    public $importedrows = array();

    /**
     * @var int $importid   The import id.
     */
    public $importid = 0;

    /**
     * @var object $importer   The importer object.
     */
    public $importer = null;

    /**
     * @var array $foundheaders   The headers found in the import file.
     */
    public $foundheaders = array();

    /**
     * @var object $generator   The generator used for creating the courses and activities.
     */
    public $generator = null;

    /**
     * @var array $errors   The array of all errors identified.
     */
    public $errors = array();

    /**
     * @var int $error   The current line number we are processing.
     */
    public $linenb = 0;

    /**
     * @var bool $processstarted   Indicates if we have started processing.
     */
    public $processstarted = false;

    /**
     * Return a Failure
     *
     * @param string $msg
     */
    public function fail($msg) {
        array_push($this->error, $msg);
    }

    /**
     * Get the importid
     *
     * @return string the import id
     */
    public function get_importid() {
        return $this->importid;
    }

    /**
     * Return the list of required headers for the import
     *
     * @return array contains the column headers
     */
    public static function list_required_headers() {
        return array(
        'COURSE_IDNUMBER',
        'COURSE_SHORTNAME',
        'COURSE_FULLNAME',
        'COURSE_SUMMARY',
        'COURSE_TAGS',
        'COURSE_VISIBLE',
        'COURSE_THUMBNAIL',
        'COURSE_CATEGORYIDNUMBER',
        'COURSE_CATEGORYNAME',
        'EXTERNAL_NAME',
        'EXTERNAL_INTRO',
        'EXTERNAL_CONTENT',
        'EXTERNAL_MARKCOMPLETEEXTERNALLY',
        );
    }

    /**
     * Retunr the list of headers found in the CSV
     *
     * @return array contains the column headers
     */
    public function list_found_headers() {
        return $this->foundheaders;
    }

    /**
     * Get the mapping array of file column position to our object values
     *
     * @param object $data
     * @return array the object key to column
     */
    private function read_mapping_data($data) {
        if ($data) {
            return array(
            'course_idnumber' => $data->header0,
            'course_shortname' => $data->header1,
            'course_fullname' => $data->header2,
            'course_summary' => $data->header3,
            'course_tags' => $data->header4,
            'course_visible' => $data->header5,
            'course_thumbnail' => $data->header6,
            'course_categoryidnumber' => $data->header7,
            'course_categoryname' => $data->header8,
            'external_name' => $data->header9,
            'external_intro' => $data->header10,
            'external_content' => $data->header11,
            'external_markcompleteexternally' => $data->header12,
            );
        } else {
            return array(
            'course_idnumber' => 0,
            'course_shortname' => 1,
            'course_fullname' => 2,
            'course_summary' => 3,
            'course_tags' => 4,
            'course_visible' => 5,
            'course_thumbnail' => 6,
            'course_categoryidnumber' => 7,
            'course_categoryname' => 8,
            'external_name' => 9,
            'external_intro' => 10,
            'external_content' => 11,
            'external_markcompleteexternally' => 12,
            );
        }
    }

    /**
     * Get the specific column data from the CSV row
     *
     * @param int $csvrow
     * @param int $columnindex
     * @return object
     */
    private function get_csvrow_data($csvrow, $columnindex) {
        if ($columnindex < 0) {
            return '';
        }
        return isset($csvrow[$columnindex]) ? $csvrow[$columnindex] : '';
    }

    /**
     *
     * Validate as a minimum the CSV contains the same number of columns as we require
     *
     * @return bool
     */
    private function validateheaders() {

        $foundcount = count($this->list_found_headers());
        $requiredcount = count($this->list_required_headers());

        if ($foundcount < $requiredcount) {
            return false;
        }
        return true;
    }

    /**
     *
     * Start a new CSV importer, and return true if successful
     *
     * @param string $text
     * @param string $encoding
     * @param string $delimiter
     * @param string $type
     * @return bool
     */
    private function startcsvimporter(
                                    $text = null,
                                    $encoding = null,
                                    $delimiter = 'comma',
                                    $type = 'csvimport' ) {
        if ($text === null) {
            return false;
        }

        $this->importid = \csv_import_reader::get_new_iid($type);
        $this->importer = new \csv_import_reader($this->importid, $type);

        if (!$this->importer->load_csv_content($text, $encoding, $delimiter)) {
            $this->importer->cleanup();
            return false;
        }

        return true;
    }


    /**
     * Constructor
     *
     * @param string $text
     * @param string $encoding
     * @param string $delimiter
     * @param integer $category
     * @param bool $downloadthumbnail
     * @param integer $importid
     * @param object $mappingdata
     */
    public function __construct($text = null, $encoding = null, $delimiter = 'comma',
                                $category = null, $downloadthumbnail = 0, $importid = 0, $mappingdata = null) {
        global $CFG;
        require_once($CFG->libdir . '/csvlib.class.php');

        $type = 'externalcontentcourse';
        $this->importid = $importid;

        if (!$this->importid) {
            if (!$this->startcsvimporter($text, $encoding, $delimiter, $type)) {
                $this->fail(get_string('invalidimportfile', 'tool_uploadexternalcontent'));
                return;
            }
        } else {
            $this->importer = new \csv_import_reader($this->importid, $type);
        }

        if (!$this->importer->init()) {
               $this->fail(get_string('invalidimportfile', 'tool_uploadexternalcontent'));
               $this->importer->cleanup();
               return;
        }

        $categorycheck = \tool_uploadexternalcontent\helper::resolve_category_by_id_or_idnumber($category);
        if ($categorycheck == null) {
            $this->fail(get_string('invalidparentcategoryid', 'tool_uploadexternalcontent'));
            $this->importer->cleanup();
            return;
        } else {
            $category = $categorycheck;
        }

        $this->foundheaders = $this->importer->get_columns();
        if (!$this->validateheaders()) {
            $this->fail(get_string('invalidimportfileheaders', 'tool_uploadexternalcontent'));
            $this->importer->cleanup();
            return;
        }

        $normalizedrow = null;
        $this->importedrows = array();

        $mapping = $this->read_mapping_data($mappingdata);
        while ($csvrow = $this->importer->next()) {
            $normalizedrow = new \stdClass();
            $normalizedrow->course_idnumber = $this->get_csvrow_data($csvrow, $mapping['course_idnumber']);
            $normalizedrow->course_shortname = $this->get_csvrow_data($csvrow, $mapping['course_shortname']);
            $normalizedrow->course_fullname = $this->get_csvrow_data($csvrow, $mapping['course_fullname']);
            $normalizedrow->course_summary = $this->get_csvrow_data($csvrow, $mapping['course_summary']);
            $normalizedrow->course_tags = $this->get_csvrow_data($csvrow, $mapping['course_tags']);
            $normalizedrow->course_visible = clean_param(
                                    $this->get_csvrow_data($csvrow, $mapping['course_visible']),
                                    PARAM_BOOL);
            $normalizedrow->course_thumbnail = $this->get_csvrow_data($csvrow, $mapping['course_thumbnail']);
            $normalizedrow->course_categoryidnumber = $this->get_csvrow_data($csvrow, $mapping['course_categoryidnumber']);
            $normalizedrow->course_categoryname = $this->get_csvrow_data($csvrow, $mapping['course_categoryname']);
            $normalizedrow->external_name = $this->get_csvrow_data($csvrow, $mapping['external_name']);
            $normalizedrow->external_intro = $this->get_csvrow_data($csvrow, $mapping['external_intro']);
            $normalizedrow->external_content = $this->get_csvrow_data($csvrow, $mapping['external_content']);
            $normalizedrow->external_markcompleteexternally = clean_param(
                                                    $this->get_csvrow_data($csvrow, $mapping['external_markcompleteexternally']),
                                                    PARAM_BOOL);
            $normalizedrow->category = $category;

            $normalizedrow->downloadthumbnail = $downloadthumbnail;
            array_push($this->importedrows, $normalizedrow);
        }

        $this->importer->close();

        if ($this->importedrows == null) {
               $this->fail(get_string('invalidimportfilenorecords', 'tool_uploadexternalcontent'));
               return;
        }
    }

    /**
     * Get the error information
     *
     * @return string the last error
     */
    public function haserrors() {
        return count($this->error) > 0;
    }


    /**
     * Get the error information array
     *
     * @return array the error messages
     */
    public function geterrors() {
        return $this->error;
    }

    /**
     * Execute the process.
     *
     * @param object $tracker the output tracker to use.
     * @return void
     */
    public function execute($tracker = null) {
        global $CFG;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->libdir . '/phpunit/classes/util.php');
        require_once($CFG->dirroot . '/mod/externalcontent/lib.php');

        if ($this->processstarted) {
              throw new \coding_exception('Process has already been started');
        }
        $this->processstarted = true;

        if (empty($tracker)) {
              $tracker = new \tool_uploadexternalcontent\tracker(\tool_uploadexternalcontent\tracker::NO_OUTPUT);
        }
        $tracker->start();

        $total = $success = $failed = 0;

        // We will most certainly need extra time and memory to process big files.
        \core_php_time_limit::raise();
        raise_memory_limit(MEMORY_EXTRA);

        // Now actually do the work.
        foreach ($this->importedrows as $importedrow) {
            $this->linenb += 1;
            $total += 1;

            $importresult = \tool_uploadexternalcontent\helper::import_row($importedrow,
                                                        $importedrow->category,
                                                        $importedrow->downloadthumbnail);
            if ($importresult->success) {
                $tracker->output($this->linenb,
                                 true,
                                 $importresult->courseid,
                                 $importresult->coursefullname,
                                 $importresult->message
                                );
                $success += 1;
            }

            if (!$importresult->success) {
                $tracker->output($this->linenb,
                                 false,
                                 $importresult->courseid,
                                 $importresult->coursefullname,
                                 $importresult->message
                                );
                $failed += 1;
            }

        }
        $tracker->finish();
        $tracker->results($total, $success, $failed);
        return $tracker->get_buffer();
    }
}
