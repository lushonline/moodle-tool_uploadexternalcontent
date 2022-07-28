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
 * Strings for component 'tool_uploadexternalcontent', language 'en'
 *
 * @package    tool_uploadexternalcontent
 * @copyright  2019-2020 LushOnline
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Upload external content courses';
$string['importfile'] = 'CSV file';
$string['import'] = 'Import';
$string['coursescreated'] = 'Courses created/updated successfully';

$string['cachedef_helper'] = 'Upload page helper caching';

$string['confirm'] = 'Confirm';
$string['confirmcolumnmappings'] = 'Confirm the columns mappings';
$string['csvdelimiter'] = 'CSV delimiter';
$string['csvdelimiter_help'] = 'CSV delimiter of the CSV file.';
$string['encoding'] = 'Encoding';
$string['encoding_help'] = 'Encoding of the CSV file.';

$string['importvaluesheader'] = 'Import settings';
$string['columnsheader'] = 'Columns';

// Tracker.
$string['csvline'] = 'Line';
$string['id'] = 'ID';
$string['result'] = 'Result';
$string['uploadexternalcontentresult'] = 'Upload results';
$string['coursestotal'] = 'Courses total: {$a}';
$string['coursescreated'] = 'Courses created: {$a}';
$string['coursesupdated'] = 'Courses updated: {$a}';
$string['coursesdeleted'] = 'Courses deleted: {$a}';
$string['coursesnotupdated'] = 'Courses not updated: {$a}';
$string['courseserrors'] = 'Courses errors: {$a}';

// CLI.
$string['invalidcsvfile'] = 'File format is invalid.';
$string['invalidencoding'] = 'Invalid encoding specified';

// Helper.
$string['thumbnailinvalidext'] = 'Thumbnail is an invalid type. Extension: {$a}.';
$string['thumbnailinvalidtype'] = 'Thumbnail is an invalid type.';
$string['thumbnailsamesource'] = 'Thumbnail is the same source as current thumbnail, not updated.';
$string['thumbnaildownloaded'] = 'Thumbnail downloaded and added.';
$string['thumbnaildownloaderror'] = 'Thumbnail could not be retrieved. {$a}.';

// Importer.
$string['invalidfileexception'] = 'File format is invalid. {$a}';
$string['invalidimportfile'] = 'File format is invalid.';
$string['invalidparentcategoryid'] = 'Parent category is invalid.';
$string['invalidimportfileheaders'] = 'File headers are invalid. Not enough columns, please verify the delimiter setting.';
$string['invalidimportfilenorecords'] = 'No records in import file.';
$string['invalidimportrecord'] = 'Invalid Import Record.';
$string['statuscoursecreated'] = 'Course Created.';
$string['statuscourseupdated'] = 'Course Updated.';
$string['statuscoursenotupdated'] = 'Course Not Updated.';
$string['statusextcreated'] = 'External content Created.';
$string['statusextupdated'] = 'External content Updated.';
