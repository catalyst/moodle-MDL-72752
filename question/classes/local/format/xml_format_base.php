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
 * Defines the base class for question import and export formats.
 *
 * @package    moodlecore
 * @subpackage questionbank
 * @copyright  1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_question\local\format;

use core_text;
/**
 * Base class for question import and export formats.
 *
 * @copyright  1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class xml_format_base extends format_default {

    /**
     * A lot of imported files contain unwanted entities.
     * This method tries to clean up all known problems.
     * @param string str string to correct
     * @return string the corrected string
     */
    public function cleaninput($str) {

        $html_code_list = array(
            "&#039;" => "'",
            "&#8217;" => "'",
            "&#8220;" => "\"",
            "&#8221;" => "\"",
            "&#8211;" => "-",
            "&#8212;" => "-",
        );
        $str = strtr($str, $html_code_list);
        // Use core_text entities_to_utf8 function to convert only numerical entities.
        $str = core_text::entities_to_utf8($str, false);
        return $str;
    }

    /**
     * Return the array moodle is expecting
     * for an HTML text. No processing is done on $text.
     * qformat classes that want to process $text
     * for instance to import external images files
     * and recode urls in $text must overwrite this method.
     * @param array $text some HTML text string
     * @return array with keys text, format and files.
     */
    public function text_field($text) {
        return array(
            'text' => trim($text),
            'format' => FORMAT_HTML,
            'files' => array(),
        );
    }

    /**
     * Return the value of a node, given a path to the node
     * if it doesn't exist return the default value.
     * @param array xml data to read
     * @param array path path to node expressed as array
     * @param mixed default
     * @param bool istext process as text
     * @param string error if set value must exist, return false and issue message if not
     * @return mixed value
     */
    public function getpath($xml, $path, $default, $istext=false, $error='') {
        foreach ($path as $index) {
            if (!isset($xml[$index])) {
                if (!empty($error)) {
                    $this->error($error);
                    return false;
                } else {
                    return $default;
                }
            }

            $xml = $xml[$index];
        }

        if ($istext) {
            if (!is_string($xml)) {
                $this->error(get_string('invalidxml', 'qformat_xml'));
            }
            $xml = trim($xml);
        }

        return $xml;
    }
}
