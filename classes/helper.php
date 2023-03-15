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
 * Class containing a set of helpers, based on admin\tool\uploadcourse by 2013 Frédéric Massart.
 *
 * @package    tool_uploadexternalcontent
 * @copyright  2019-2023 LushOnline
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_uploadexternalcontent;

use mod_externalcontent\instance;
use mod_externalcontent\importableinstance;
use mod_externalcontent\importrecord;

/**
 * Class containing a set of helpers.
 *
 * @package   tool_uploadexternalcontent
 * @copyright 2019-2023 LushOnline
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * format_moodle_tags to Moodle standard
     * Max length of 50 and trimmed.
     *
     * @param  string $tag
     * @return string
     */
    private static function format_moodle_tags($tag) {
        $result = substr($tag, 0, 50);
        return trim($result);
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
        global $CFG, $DB;

        // Handle null id by selecting the first non zero category id.
        if (is_null($id)) {
            if (method_exists('\core_course_category', 'create')) {
                $id = \core_course_category::get_default()->id;
                return $id;
            } else {
                require_once($CFG->libdir . '/coursecatlib.php');
                $id = \coursecat::get_default()->id;
                return $id;
            }
            return null;
        }

        // Handle numeric id by confirming it exists.
        $params = array('id' => $id);
        if (is_numeric($id)) {
            if ($DB->record_exists('course_categories', $params)) {
                return $id;
            }
            return null;
        }

        // Handle any other id format by treating as a string idnumber value.
        $params = array('idnumber' => $id);
        if ($id = $DB->get_field_select('course_categories', 'id', 'idnumber = :idnumber', $params, MUST_EXIST)) {
            return $id;
        }
        return null;
    }


    /**
     * Return the category id, creating the category if necessary.
     *
     * @param int $parentid Parent id
     * @param string $categoryname The category name
     * @param string $categoryidnumber The category idnumber
     * @return int The category id, or $parentid if empty
     */
    public static function get_or_create_category(int $parentid,
                                                  ?string $categoryname = null,
                                                  ?string $categoryidnumber = null): int {
        global $CFG;
        $categoryid = $parentid;

        if (!empty($categoryidnumber)) {
            if (!$categoryid = self::resolve_category_by_idnumber($categoryidnumber)) {
                if (!empty($categoryname)) {
                    // Category not found and we have a name so we need to create.
                    $category = new \stdClass();
                    $category->parent = $parentid;
                    $category->name = $categoryname;
                    $category->idnumber = $categoryidnumber;

                    if (method_exists('\core_course_category', 'create')) {
                        $createdcategory = \core_course_category::create($category);
                    } else {
                        require_once($CFG->libdir . '/coursecatlib.php');
                        $createdcategory = \coursecat::create($category);
                    }
                    $categoryid = $createdcategory->id;
                }
            }
        }
        return $categoryid;
    }

    /**
     * Convert the row tags to a delimited string of tags.
     * Tags can be no longer than 50 characters in Moodle
     *
     * @param object $row The row we imported from the csv
     * @param string $tagdelimiter The value to use to split the delimited course_tags string we imported
     * @return string The row converted to a pipe delimited list of tags for External Content
     */
    private static function get_tags($row, $tagdelimiter="|") {
        $tagsarray = array();
        $tagsarray = explode($tagdelimiter, $row->course_tags);

        // Format for Moodle.
        $tagsarray = array_map('self::format_moodle_tags', $tagsarray);
        // Normalize the tags.
        return \core_tag_tag::normalize($tagsarray, false);
    }


    /**
     * sanitizeUrl
     *
     * @param  mixed $url
     * @return void
     */
    private static function sanitizeurl($url) {
        $parts = parse_url($url);

        // Optional but we only sanitize URLs with scheme and host defined.
        if ($parts === false || empty($parts["scheme"]) || empty($parts["host"])) {
            return $url;
        }

        $sanitizedpath = null;
        if (!empty($parts["path"])) {
            $pathparts = explode("/", $parts["path"]);
            foreach ($pathparts as $pathpart) {
                if (empty($pathpart)) {
                    continue;
                }
                // The Path part might already be urlencoded.
                $sanitizedpath .= "/" . rawurlencode(rawurldecode($pathpart));
            }
        }

        // Build the url.
        $targeturl = $parts["scheme"] . "://" .
            ((!empty($parts["user"]) && !empty($parts["pass"])) ? $parts["user"] . ":" . $parts["pass"] . "@" : "") .
            $parts["host"] .
            (!empty($parts["port"]) ? ":" . $parts["port"] : "") .
            (!empty($sanitizedpath) ? $sanitizedpath : "") .
            (!empty($parts["query"]) ? "?" . $parts["query"] : "") .
            (!empty($parts["fragment"]) ? "#" . $parts["fragment"] : "");

        return $targeturl;
    }

    /**
     * Convert the row to an External Content importrecord.
     *
     * @param object $row The row we imported from the csv
     * @param string|int $parentcategory The parentcategory name or id
     * @param bool $thumbnail If true, then the thumbnail for the course will be processed.
     * @return mod_externalcontent\importrecord|null The row converted to an importrecord, or null if not valid
     */
    public static function row_to_importrecord($row, $parentcategory = null, $thumbnail = true) : ?importrecord {

        // Create/Retrieve categoryid.
        $parentcategoryid = self::resolve_category_by_id_or_idnumber($parentcategory);
        $categoryid = self::get_or_create_category($parentcategoryid,
                                                   $row->course_categoryname,
                                                   $row->course_categoryidnumber);
        // Create courseimport class.
        $courseimport = new \stdClass();
        $courseimport->idnumber = $row->course_idnumber;
        $courseimport->shortname = $row->course_shortname;
        $courseimport->fullname = $row->course_fullname;
        $courseimport->summary = $row->course_summary;
        $courseimport->tags = self::get_tags($row);
        $courseimport->visible = $row->course_visible;
        $courseimport->thumbnail = self::sanitizeurl($row->course_thumbnail);
        $courseimport->category = $categoryid;

        // Create moduleimport class.
        $moduleimport = new \stdClass();
        $moduleimport->name = $row->external_name;
        $moduleimport->intro = $row->external_intro;
        $moduleimport->content = $row->external_content;
        $moduleimport->completionexternally = $row->external_markcompleteexternally;

        // Create options class.
        $options = new \stdClass();
        $options->downloadthumbnail = $thumbnail;

        // Get our importrecord.
        $importrecord = new importrecord($courseimport, $moduleimport, $options);
        return $importrecord->validate() ? $importrecord : null;
    }


    /**
     * Import the row of data, creating or updating as needed.
     *
     * @param object $row The row we imported from the csv
     * @param string $parentcategory The parentcategory name or id
     * @param bool $thumbnail If true, then the thumbnail for the course will be processed.
     * @return object Processing information for the row
     */
    public static function import_row($row, $parentcategory = null, $thumbnail = true) {
        $result = new \stdClass();
        $result->success = false;
        $result->courseid = null;
        $result->coursefullname = null;
        $result->moduleid = null;
        $result->message = null;
        $result->importrecord = null;

        if ($importrecord = self::row_to_importrecord($row, $parentcategory, $thumbnail)) {
            $instance = importableinstance::get_from_importrecord($importrecord);
            $result->success = true;
            $result->courseid = $instance->get_course_id();
            $result->coursefullname = $instance->get_course_var('fullname');
            $result->moduleid = $instance->get_module_id();
            $result->message = implode(", ", $instance->get_messages());
            $result->importrecord = $importrecord;
        } else {
            $result->success = false;
            $result->message = get_string('invalidimportrecord', 'tool_uploadexternalcontent');

        };

        return $result;
    }
}
