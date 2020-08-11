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
 * @copyright 2019-2020 LushOnline
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

/**
 * Main processing class for adding and updating single external content course.
 *
 * @package   tool_uploadexternalcontent
 * @copyright 2019-2020 LushOnline
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_uploadexternalcontent_importer {

    /**
     * @var array $error   Last error message.
     */
    public $error = array();

    /**
     * @var array $records   The records to process.
     */
    public $records = array();

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
     * Get the row of data from the CSV
     *
     * @param int $row
     * @param int $index
     * @return object
     */
    private function get_row_data($row, $index) {
        if ($index < 0) {
            return '';
        }
        return isset($row[$index]) ? $row[$index] : '';
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

        $this->importid = csv_import_reader::get_new_iid($type);
        $this->importer = new csv_import_reader($this->importid, $type);

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
     * @param integer $importid
     * @param object $mappingdata
     */
    public function __construct($text = null, $encoding = null, $delimiter = 'comma',
                                $category=1, $importid = 0, $mappingdata = null) {
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
            $this->importer = new csv_import_reader($this->importid, $type);
        }

        if (!$this->importer->init()) {
               $this->fail(get_string('invalidimportfile', 'tool_uploadexternalcontent'));
               $this->importer->cleanup();
               return;
        }

        $categorycheck = tool_uploadexternalcontent_helper::resolve_category_by_id_or_idnumber($category);
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

        // Retrieve the External Content defaults.
        $extcontdefaults = get_config('externalcontent');

        $record = null;
        $records = array();

        while ($row = $this->importer->next()) {
            $mapping = $this->read_mapping_data($mappingdata);

            $record = new \stdClass();
            $record->course_idnumber = $this->get_row_data($row, $mapping['course_idnumber']);
            $record->course_shortname = $this->get_row_data($row, $mapping['course_shortname']);
            $record->course_fullname = $this->get_row_data($row, $mapping['course_fullname']);
            $record->course_summary = $this->get_row_data($row, $mapping['course_summary']);
            $record->course_tags = $this->get_row_data($row, $mapping['course_tags']);
            $record->course_visible = clean_param(
                                    $this->get_row_data($row, $mapping['course_visible']),
                                    PARAM_BOOL);
            $record->course_thumbnail = $this->get_row_data($row, $mapping['course_thumbnail']);
            $record->course_categoryidnumber = $this->get_row_data($row, $mapping['course_categoryidnumber']);
            $record->course_categoryname = $this->get_row_data($row, $mapping['course_categoryname']);
            $record->external_name = $this->get_row_data($row, $mapping['external_name']);
            $record->external_intro = $this->get_row_data($row, $mapping['external_intro']);
            $record->external_content = $this->get_row_data($row, $mapping['external_content']);
            $record->external_markcompleteexternally = clean_param(
                                                    $this->get_row_data($row, $mapping['external_markcompleteexternally']),
                                                    PARAM_BOOL);
            $record->category = $category;

            $record->external_printheading = $extcontdefaults->printheading;
            $record->external_printintro = $extcontdefaults->printintro;
            $record->external_printlastmodified = $extcontdefaults->printlastmodified;

            array_push($records, $record);
        }

        $this->records = $records;
        $this->importer->close();

        if ($this->records == null) {
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
        global $DB, $CFG;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->libdir . '/phpunit/classes/util.php');
        require_once($CFG->dirroot . '/mod/externalcontent/lib.php');

        if ($this->processstarted) {
              throw new coding_exception('Process has already been started');
        }
        $this->processstarted = true;

        if (empty($tracker)) {
              $tracker = new tool_uploadexternalcontent_tracker(tool_uploadexternalcontent_tracker::NO_OUTPUT);
        }
        $tracker->start();

        $generator = phpunit_util::get_data_generator();

        $total = $created = $updated = $deleted = $nochange = $errors = 0;

        // We will most certainly need extra time and memory to process big files.
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_EXTRA);

        $coursecreatedmsg = get_string('statuscoursecreated', 'tool_uploadexternalcontent');
        $courseupdatedmsg = get_string('statuscourseupdated', 'tool_uploadexternalcontent');
        $coursenotupdatedmsg = get_string('statuscoursenotupdated', 'tool_uploadexternalcontent');
        $extcreatedmsg = get_string('statusextcreated', 'tool_uploadexternalcontent');
        $extupdatedmsg = get_string('statusextupdated', 'tool_uploadexternalcontent');
        $invalidrecordmsg = get_string('invalidimportrecord', 'tool_uploadexternalcontent');

        // Now actually do the work.
        foreach ($this->records as $record) {
            $status = array();
            $this->linenb++;
            $total++;

            if (tool_uploadexternalcontent_helper::validate_import_record($record)) {
                $course = tool_uploadexternalcontent_helper::create_course_from_imported($record);
                $activity = tool_uploadexternalcontent_helper::create_externalcontent_from_imported($record);

                if ($existing = tool_uploadexternalcontent_helper::get_course_by_idnumber($course->idnumber)) {
                    $updatecourse = true;
                    if (!$mergedcourse = tool_uploadexternalcontent_helper::update_course_with_imported($existing, $course)) {
                        $updatecourse = false;
                        $mergedcourse = $existing;
                    }

                    if ($record->course_thumbnail != '') {
                        $response = tool_uploadexternalcontent_helper::add_course_thumbnail($mergedcourse->id,
                                                                                            $record->course_thumbnail);
                        $status[] = $response->status;
                    }

                    // Now check the externalcontent.
                    $addactivity = $updateactivity = false;
                    $existingactivity = tool_uploadexternalcontent_helper::get_externalcontent_by_idnumber($mergedcourse->idnumber,
                                        $mergedcourse->id);

                    if ($existingactivity) {
                        $addactivity = false;
                        $updateactivity = true;

                        $mergedactivity = tool_uploadexternalcontent_helper::update_externalcontent_with_imported(
                                            $existingactivity, $activity);
                        if ( $mergedactivity === false) {
                            $updateactivity = false;
                            $addactivity = false;
                            $mergedactivity = $activity;
                        }
                    } else {
                        $activity->course = $existing->id;
                        $addactivity = true;
                        $updateactivity = false;
                        $mergedactivity = $activity;
                    }

                    if ($updatecourse === false && $addactivity === false && $updateactivity === false) {
                        // Course data not changed.
                        $nochange++;
                        $status[] = $coursenotupdatedmsg;
                        $tracker->output($this->linenb, true, $status, $mergedcourse);
                    } else {
                        // Course or external content differs so we need to update.
                        $updated++;

                        if ($updatecourse) {
                            update_course($mergedcourse);
                            $status[] = $courseupdatedmsg;
                        }

                        if ($addactivity) {
                            $activityresponse = $generator->create_module('externalcontent',  $mergedactivity);
                            $mergedactivity->id = $activityresponse->id;

                            $cm = get_coursemodule_from_instance('externalcontent',  $mergedactivity->id);
                            $cm->idnumber = $mergedcourse->idnumber;
                            $DB->update_record('course_modules', $cm);
                            $status[] = $courseupdatedmsg;
                            $status[] = $extcreatedmsg;
                            tool_uploadexternalcontent_helper::update_course_completion_criteria($mergedcourse, $cm);
                        }

                        if ($updateactivity) {
                            $DB->update_record('externalcontent',  $mergedactivity);
                            $cm = get_coursemodule_from_instance('externalcontent',  $mergedactivity->id);
                            $cm->idnumber = $course->idnumber;
                            $DB->update_record('course_modules', $cm);
                            $status[] = $courseupdatedmsg;
                            $status[] = $extupdatedmsg;
                            tool_uploadexternalcontent_helper::update_course_completion_criteria($mergedcourse, $cm);
                        }
                        $tracker->output($this->linenb, true, $status, $mergedcourse);
                    }
                } else {
                    $created++;
                    $status[] = $coursecreatedmsg;

                    $newcourse = create_course($course);
                    $activity->course = $newcourse->id;

                    if ($record->course_thumbnail != '') {
                        $response = tool_uploadexternalcontent_helper::add_course_thumbnail($newcourse->id,
                                                                                            $record->course_thumbnail);
                        if ($response->thumbnailfile) {
                            $newcourse->overviewfiles_filemanager = $response->thumbnailfile->get_itemid();
                        }
                        $status[] = $response->status;
                        update_course($newcourse);
                    }

                    // Now we need to add a External content.
                    $activityrecord = $generator->create_module('externalcontent', $activity);

                    $cm = get_coursemodule_from_instance('externalcontent', $activityrecord->id);
                    $cm->idnumber = $course->idnumber;
                    $DB->update_record('course_modules', $cm);

                    tool_uploadexternalcontent_helper::update_course_completion_criteria($newcourse, $cm);

                    $tracker->output($this->linenb, true, $status, $newcourse);
                }
            } else {
                $errors++;
                $status[] = $invalidrecordmsg;

                $tracker->output($this->linenb, false, $status, null);
            }
        }

        $tracker->finish();
        $tracker->results($total, $created, $updated, $deleted, $nochange, $errors);
        return $tracker->get_buffer();
    }
}
