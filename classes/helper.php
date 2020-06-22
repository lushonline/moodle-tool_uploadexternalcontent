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
 * Links and settings
 *
 * Class containing a set of helpers, based on admin\tool\uploadcourse by 2013 Frédéric Massart.
 *
 * @package    tool_uploadexternalcontent
 * @copyright  2019-2020 LushOnline
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');

/**
 * Class containing a set of helpers.
 *
 * @package   tool_uploadexternalcontent
 * @copyright 2019-2020 LushOnline
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_uploadexternalcontent_helper {

    /**
     * Validate we have the minimum info to create/update course
     *
     * @param object $record The record we imported
     * @return bool true if validated
     */
    public static function validate_import_record($record) {
        // As a minimum we need.
        // course idnumber.
        // course shortname.
        // course longname.

        if (empty($record->course_idnumber)) {
            return false;
        }

        if (empty($record->course_shortname)) {
            return false;
        }

        if (empty($record->course_fullname)) {
            return false;
        }
        return true;
    }

    /**
     * Resolve a category by IDnumber.
     *
     * @param string $idnumber category IDnumber.
     * @return int category ID.
     */
    public static function resolve_category_by_idnumber($idnumber) {
          global $DB;

        $params = array('idnumber' => $idnumber);
        $id = $DB->get_field_select('course_categories', 'id', 'idnumber = :idnumber', $params, IGNORE_MISSING);
        return $id;
    }

    /**
     * Resolve a category by ID
     *
     * @param string $id category ID.
     * @return int category ID.
     */
    public static function resolve_category_by_id_or_idnumber($id) {
        global $DB;
        $params = array('id' => $id);
        if (is_numeric($id)) {
            if ($DB->record_exists('course_categories', $params)) {
                return $id;
            } else {
                return null;
            }
        } else {
            $params = array('idnumber' => $id);
            try {
                $id = $DB->get_field_select('course_categories', 'id', 'idnumber = :idnumber', $params, MUST_EXIST);
                return $id;
            } catch (Exception $e) {
                return null;
            }
        }
    }

    /**
     * Return the category id, creating the category if necessary from the import record.
     *
     * @param object $record Validated Imported Record
     * @return int The category id
     */
    public static function get_or_create_category_from_import_record($record) {
        global $CFG;
        $categoryid = $record->category;

        if (!empty($record->course_categoryidnumber)) {
            $categoryid = self::resolve_category_by_idnumber($record->course_categoryidnumber);
            if ($categoryid === false) {

                if (!empty($record->course_categoryname)) {
                    // Category not found and we have a name so we need to create.
                    $category = new \stdClass();
                    $category->parent = $record->category;
                    $category->name = $record->course_categoryname;
                    $category->idnumber = $record->course_categoryidnumber;

                    if (method_exists('\core_course_category', 'create')) {
                        $createdcategory = core_course_category::create($category);
                    } else {
                        require_once($CFG->libdir . '/coursecatlib.php');
                        $createdcategory = coursecat::create($category);
                    }
                    $categoryid = $createdcategory->id;
                }
            }
        }
        return $categoryid;
    }

    /**
     * Retrieve a course by its idnumber.
     *
     * @param string $courseidnumber course idnumber
     * @return object course or null
     */
    public static function get_course_by_idnumber($courseidnumber) {
        global $DB;

        $params = array('idnumber' => $courseidnumber);
        $courses = $DB->get_records('course', $params);

        if (count($courses) == 1) {
            $course = array_pop($courses);
            $tags = core_tag_tag::get_item_tags_array('core', 'course', $course->id,
                                        core_tag_tag::BOTH_STANDARD_AND_NOT, 0, false);

            $course->tags = array();

            foreach ($tags as $key => $value) {
                array_push($course->tags, $value);
            }

            return $course;
        } else {
            return null;
        }
    }

    /**
     * Create a course from the import record.
     *
     * @param object $record Validated Imported Record
     * @param string $tagdelimiter The value to use to split the delimited $record->course_tags string
     * @return object course or null
     */
    public static function create_course_from_imported($record, $tagdelimiter="|") {
        $course = new \stdClass();
        $course->idnumber = $record->course_idnumber;
        $course->shortname = $record->course_shortname;
        $course->fullname = $record->course_fullname;
        $course->summary = $record->course_summary;
        $course->summaryformat = 1; // FORMAT_HTML.
        $course->visible = $record->course_visible;

        // Split the tag string into an array.
        if (!empty($record->course_tags)) {
            $course->tags = explode($tagdelimiter, $record->course_tags);
        } else {
            $course->tags = array();
        }

        // Fixed default values.
        $course->format = "singleactivity";
        $course->numsections = 0;
        $course->newsitems = 0;
        $course->showgrades = 0;
        $course->showreports = 0;
        $course->startdate = time();
        $course->activitytype = "externalcontent";

        $course->category = self::get_or_create_category_from_import_record($record);

        // Add completion flags.
        $course->enablecompletion = 1;

        return $course;
    }

    /**
     * Entity decode value passed for use by array_walk
     *
     * @param  mixed $value
     * @param  mixed $key
     * @return void
     */
    public static function entity_decode_values(&$value, $key) {
        $value = html_entity_decode($value);
    }

    /**
     * Merge changes from $importedcourse into $existingcourse
     *
     * @param object $existingcourse Course Record for existing course
     * @param object $importedcourse  Course Record for imported course
     * @return object course or FALSE if no changes
     */
    public static function update_course_with_imported($existingcourse, $importedcourse) {
        $updateneeded = false;
        $result = $existingcourse;
        $updates = array();

        if ($existingcourse->fullname !== $importedcourse->fullname) {
            array_push($updates, "fullname is different");
            $result->fullname = $importedcourse->fullname;
            $updateneeded = true;
        }

        if ($existingcourse->shortname !== $importedcourse->shortname) {
            array_push($updates, "shortname is different");
            $result->shortname = $importedcourse->shortname;
            $updateneeded = true;
        }

        if ($existingcourse->idnumber !== $importedcourse->idnumber) {
            array_push($updates, "idnumber is different");
            $result->idnumber = $importedcourse->idnumber;
            $updateneeded = true;
        }

        // We need to apply Moodle FORMAT_HTML conversion as this is how summary would have been stored.
        $options = array();
        $options['filter'] = false;
        $formatted = format_text($importedcourse->summary, FORMAT_HTML, $options);

        if ($existingcourse->summary !== $formatted) {
            array_push($updates, "summary is different");
            $result->summary = $importedcourse->summary;
            $updateneeded = true;
        }

        $existingvisible = clean_param($existingcourse->visible, PARAM_BOOL);
        $importedvisible = clean_param($importedcourse->visible, PARAM_BOOL);

        if ($existingvisible !== $importedvisible) {
            array_push($updates, "visible is different");
            $result->visible = $importedcourse->visible;
            $updateneeded = true;
        }

        // Sort the arrays and then compare.
        array_walk($existingcourse->tags, 'self::entity_decode_values');
        $existingtags = $existingcourse->tags;
        $importedtags = $importedcourse->tags;

        if (sort($existingtags) !== sort($importedtags)) {
            array_push($updates, "tags are different");
            $result->tags = $importedcourse->tags;
            $updateneeded = true;
        }

        if ($existingcourse->category !== $importedcourse->category) {
            array_push($updates, "category is different");
            $result->category = $importedcourse->category;
            $updateneeded = true;
        }

        if ($updateneeded) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Retrieve a externalcontent by its name.
     *
     * @param string $name externalcontent name
     * @param string $courseid course identifier
     * @return object externalcontent.
     */
    public static function get_externalcontent_by_name($name, $courseid) {
        global $DB;

        $params = array('name' => $name, 'course' => $courseid);
        $externalcontents = $DB->get_records('externalcontent', $params);

        if (count($externalcontents) != 0) {
             return array_pop($externalcontents);
        } else {
             return null;
        }
    }

    /**
     * Retrieve a externalcontent by its idnumber.
     *
     * @param string $idnumber externalcontent name
     * @param string $courseid course identifier
     * @return object externalcontent.
     */
    public static function get_externalcontent_by_idnumber($idnumber, $courseid) {
        global $DB;

        $params = array('idnumber' => $idnumber, 'course' => $courseid);
        $cm = $DB->get_record('course_modules', $params);

        if (!$cm) {
            return null;
        }

        $params = array('id' => $cm->instance, 'course' => $courseid);
        $externalcontents = $DB->get_records('externalcontent', $params);

        if (count($externalcontents) != 0) {
             return array_pop($externalcontents);
        } else {
             return null;
        }
    }

    /**
     * Create a externalcontent from the import record.
     *
     * @param object $record Validated Imported Record
     * @return object course or null
     */
    public static function create_externalcontent_from_imported($record) {
        // All data provided by the data generator.
        $externalcontent = new \stdClass();
        $externalcontent->name = $record->external_name;
        $externalcontent->printintro = 0;
        $externalcontent->printheading = 1;
        $externalcontent->intro = $record->external_intro;
        $externalcontent->introformat = 1; // FORMAT_HTML.
        $externalcontent->content = $record->external_content;
        $externalcontent->contentformat = 1; // FORMAT_HTML.

        $externalcontent->completion = 2;
        $externalcontent->completionview = 1;
        $externalcontent->completionexternally = $record->external_markcompleteexternally;

        return $externalcontent;
    }

    /**
     * Merge changes from $imported into $existing
     *
     * @param object $existing Page Record for existing page
     * @param object $imported  page Record for imported page
     * @return object page or FALSE if no changes
     */
    public static function update_externalcontent_with_imported($existing, $imported) {
        $updateneeded = false;
        $result = $existing;
        $updates = array();

        if ($existing->name !== $imported->name) {
            array_push($updates, "activity name is different");
            $result->name = $imported->name;
            $updateneeded = true;
        }

        // We need to apply Moodle FORMAT_HTML conversion as this is how summary would have been stored.
        $options = array();
        $formattedintro = format_text($imported->intro, FORMAT_HTML, $options);

        if ($existing->intro !== $formattedintro) {
            array_push($updates, "activity intro is different");
            $result->intro = $imported->intro;
            $updateneeded = true;
        }

        // We need to apply Moodle FORMAT_HTML conversion as this is how summary would have been stored.
        $options = array('noclean' => 1);
        $formattedcontent = format_text($imported->content, FORMAT_HTML, $options);

        if ($existing->content !== $formattedcontent) {
            array_push($updates, "activity content is different");
            $result->content = $imported->content;
            $updateneeded = true;
        }

        $existingcompletion = clean_param($existing->completionexternally, PARAM_BOOL);
        $importedcompletion = clean_param($imported->completionexternally, PARAM_BOOL);

        if ( $existingcompletion !== $importedcompletion) {
            array_push($updates, "activity completionexternally is different");
            $result->completionexternally = $imported->completionexternally;
            $updateneeded = true;
        }

        if ($updateneeded) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Update the course completion criteria to use the Activity Completion
     *
     * @param object $course Course Object
     * @param object $cm Course Module Object for the Single Page
     * @return void
     */
    public static function update_course_completion_criteria($course, $cm) {
        $criterion = new completion_criteria_activity();

        $params = array('id' => $course->id, 'criteria_activity' => array($cm->id => 1));
        if ($currentcriteria = $criterion->fetch($params)) {
            return;
        }

        // Criteria for course.
        $criteriadata = new \stdClass();
        $criteriadata->id = $course->id;
        $criteriadata->criteria_activity = array($cm->id => 1);
        $criterion->update_config($criteriadata);

        // Handle overall aggregation.
        $aggdata = array(
            'course'        => $course->id,
            'criteriatype'  => null
        );
        $aggregation = new completion_aggregation($aggdata);
        $aggregation->setMethod(COMPLETION_AGGREGATION_ALL);
        $aggregation->save();

        $aggdata['criteriatype'] = COMPLETION_CRITERIA_TYPE_ACTIVITY;
        $aggregation = new completion_aggregation($aggdata);
        $aggregation->setMethod(COMPLETION_AGGREGATION_ALL);
        $aggregation->save();

        $aggdata['criteriatype'] = COMPLETION_CRITERIA_TYPE_COURSE;
        $aggregation = new completion_aggregation($aggdata);
        $aggregation->setMethod(COMPLETION_AGGREGATION_ALL);
        $aggregation->save();

        $aggdata['criteriatype'] = COMPLETION_CRITERIA_TYPE_ROLE;
        $aggregation = new completion_aggregation($aggdata);
        $aggregation->setMethod(COMPLETION_AGGREGATION_ALL);
        $aggregation->save();
    }


    /**
     * Add_course_thumbnail
     *
     * @param  object $courseid
     * @param  string $url
     * @return object Object containing a status string and a stored_file object or null
     */
    public static function add_course_thumbnail($courseid, $url) {
        global $CFG;

        $response = new \stdClass();
        $response->status = null;
        $response->thumbnailfile = null;

        require_once($CFG->libdir . '/filelib.php');
        $fs = get_file_storage();

        $overviewfilesoptions = course_overviewfiles_options($courseid);
        $filetypesutil = new \core_form\filetypes_util();
        $whitelist = $filetypesutil->normalize_file_types($overviewfilesoptions['accepted_types']);

        $parsedurl = new moodle_url($url);

        $ext = pathinfo($parsedurl->get_path(), PATHINFO_EXTENSION);
        $filename = 'thumbnail.'.$ext;

        // Check the extension is valid.
        if (!$filetypesutil->is_allowed_file_type($filename, $whitelist)) {
            $response->status = "Thumbnail is an invalid type. Extension: ".$ext;
            return $response;
        }

        $coursecontext = \context_course::instance($courseid);

        // Get the file if it already exists.
        $response->thumbnailfile = $fs->get_file($coursecontext->id, 'course', 'overviewfiles', 0, '/', $filename);

        if ($response->thumbnailfile) {
            // Check the file is from same source as url.
            $source = $response->thumbnailfile->get_source();
            if ($source == $url) {
                // It is the same so return this file.
                $response->status = "Thumbnail is the same source as current thumbnail, not updated";
                return $response;
            } else {
                // Delete files and continue with download.
                $fs->delete_area_files($coursecontext->id, 'course', 'overviewfiles');
                $response->thumbnailfile = null;
            }
        }

        $thumbnailfilerecord = array('contextid' => $coursecontext->id,
            'component' => 'course',
            'filearea' => 'overviewfiles',
            'itemid' => '0',
            'filepath' => '/',
            'filename' => $filename,
        );

        $urlparams = array(
            'calctimeout' => false,
            'timeout' => 5,
            'skipcertverify' => true,
            'connecttimeout' => 5,
        );

        try {
            $response->thumbnailfile = $fs->create_file_from_url($thumbnailfilerecord, $url, $urlparams);
            // Check if Moodle recognises as a valid image file.
            if (!$response->thumbnailfile->is_valid_image()) {
                $fs->delete_area_files($coursecontext->id, 'course', 'overviewfiles');
                $response->thumbnailfile = null;
                $response->status = "Thumbnail specified is not a valid image.";
            } else {
                $response->status = "Thumbnail downloaded and added.";
            }
            return $response;
        } catch (\file_exception $e) {
            $fs->delete_area_files($coursecontext->id, 'course', 'overviewfiles');
            $response->thumbnailfile = null;
            $response->status = "Thumbnail could not be retrieved. ".$e->getMessage();
            return $response;
        }
    }
}