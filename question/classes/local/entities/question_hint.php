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

namespace core_question\local\entities;

use question_display_options;

/**
 * Class to represent a hint associated with a question.
 * Used by iteractive mode, etc. A question has an array of these.
 *
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_hint {
    /** @var integer The hint id. */
    public $id;
    /** @var string The feedback hint to be shown. */
    public $hint;
    /** @var integer The corresponding text FORMAT_... type. */
    public $hintformat;

    /**
     * Constructor.
     * @param int the hint id from the database.
     * @param string $hint The hint text
     * @param int the corresponding text FORMAT_... type.
     */
    public function __construct($id, $hint, $hintformat) {
        $this->id = $id;
        $this->hint = $hint;
        $this->hintformat = $hintformat;
    }

    /**
     * Create a basic hint from a row loaded from the question_hints table in the database.
     * @param object $row with $row->hint set.
     * @return question_hint
     */
    public static function load_from_record($row) {
        return new question_hint($row->id, $row->hint, $row->hintformat);
    }

    /**
     * Adjust this display options according to the hint settings.
     * @param question_display_options $options
     */
    public function adjust_display_options(question_display_options $options) {
        // Do nothing.
    }
}
