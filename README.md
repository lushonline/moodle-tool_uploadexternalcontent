# tool_uploadexternalcontent
[![Build Status](https://travis-ci.org/lushonline/moodle-tool_uploadexternalcontent.svg?branch=master)](https://travis-ci.org/lushonline/moodle-tool_uploadexternalcontent)

A tool to allow import of HTML as External content activities using a text delimited file.

This import creates a Moodle Course, consisting of a Single External content activity.

The External content activity EXTERNAL_CONTENT column of the import is the HTML snippet from the file.

The External content Activity and Course are setup to support Moodle Completion based on viewing of the Page,
and optional also a completion imported from an external source if EXTERNAL_MARKCOMPLETEEXTERNALLY column is true

There are two versions depending on the Moodle version used:

|BRANCH         |MOODLE VERSIONS|
|---------------|---------------|
|[moodle33](https://github.com/lushonline/moodle-tool_uploadexternalcontent/tree/moodle33)|v3.2 - v3.4|
|[master](https://github.com/lushonline/moodle-tool_uploadexternalcontent)|v3.5 - v3.9|

- [Installation](#installation)
- [Usage](#usage)

## Installation

---
1. Install the External content activity module:

   ```sh
   git clone https://github.com/lushonline/moodle-mod_externalcontent.git mod/externalcontent
   ```

   Or install via the Moodle plugin directory:

   https://moodle.org/plugins/mod_externalcontent


2. Install the plugin the same as any standard moodle plugin either via the
   Moodle plugin directory, or you can use git to clone it into your source:

   ```sh
   git clone https://github.com/lushonline/moodle-tool_uploadexternalcontent.git admin/tool/uploadexternalcontent
   ```

   Or install via the Moodle plugin directory:

   https://moodle.org/plugins/tool_uploadexternalcontent

3. Then run the Moodle upgrade

This plugin requires no configuration.

## Usage

For more information include file formats and how to run from the command line see the [Wiki Pages](https://github.com/lushonline/moodle-tool_uploadexternalcontent/wiki)

## License ##

2019-2020 LushOnline

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.

## Acknowledgements
This was inspired in part by the great work of Frédéric Massart and Piers harding on the core [admin\tool\uploadcourse](https://github.com/moodle/moodle/tree/master/admin/tool/uploadcourse)
