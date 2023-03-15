# tool_uploadexternalcontent
![Moodle Plugin CI](https://github.com/lushonline/moodle-tool_uploadexternalcontent/workflows/Moodle%20Plugin%20CI/badge.svg?branch=master)

A tool to allow import of HTML as External content activities using a text delimited file.

This import creates a Moodle Course, consisting of a Single External content activity.

The External content activity EXTERNAL_CONTENT column of the import is the HTML snippet from the file.

The External content Activity and Course are setup to support Moodle Completion based on viewing of the Page,
and optional also a completion imported from an external source if EXTERNAL_MARKCOMPLETEEXTERNALLY column is true

- [Installation](#installation)
- [Usage](#usage)

## Installation

---
1. Install the External content activity module v2023031400 or later:

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

For more information see the [Wiki Pages](https://github.com/lushonline/moodle-tool_uploadexternalcontent/wiki)

## Acknowledgements
This was inspired in part by the great work of Frédéric Massart and Piers harding on the core [admin\tool\uploadcourse](https://github.com/moodle/moodle/tree/master/admin/tool/uploadcourse)
