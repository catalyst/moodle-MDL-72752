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

namespace core_question\local\bank;

/**
 * Manager class to handle question import/export task and its associated operations.
 *
 * @package    core_question
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_import_export_manager {

    /**
     * Get list of available import or export formats
     * @param string $type 'import' if import list, otherwise export list assumed
     * @return array sorted list of import/export formats available
     */
    public static function get_import_export_formats($type): array {
        global $CFG;
        require_once($CFG->dirroot . '/question/format.php');

        $formatclasses = \core_component::get_plugin_list_with_class('qformat', '', 'format.php');

        foreach ($formatclasses as $component => $formatclass) {

            $format = new $formatclass();
            if ($type === 'import') {
                $provided = $format->provide_import();
            } else {
                $provided = $format->provide_export();
            }

            if ($provided) {
                list($notused, $fileformat) = explode('_', $component, 2);
                $fileformatnames[$fileformat] = get_string('pluginname', $component);
            }
        }

        \core_collator::asort($fileformatnames);
        return $fileformatnames;
    }


    /**
     * Create a reasonable default file name for exporting questions from a particular
     * category.
     * @param object $course the course the questions are in.
     * @param object $category the question category.
     * @return string the filename.
     */
    function question_default_export_filename($course, $category): string {
        // We build a string that is an appropriate name (questions) from the lang pack,
        // then the corse shortname, then the question category name, then a timestamp.

        $base = clean_filename(get_string('exportfilename', 'question'));

        $dateformat = str_replace(' ', '_', get_string('exportnameformat', 'question'));
        $timestamp = clean_filename(userdate(time(), $dateformat, 99, false));

        $shortname = clean_filename($course->shortname);
        if ($shortname ==='' || $shortname === '_' ) {
            $shortname = $course->id;
        }

        $categoryname = clean_filename(format_string($category->name));

        return "{$base}-{$shortname}-{$categoryname}-{$timestamp}";
    }

}
