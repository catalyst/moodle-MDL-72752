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
 * An extension of {@link question_hint} for questions like match and multiple
 * choice with multile answers, where there are options for whether to show the
 * number of parts right at each stage, and to reset the wrong parts.
 *
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_hint_with_parts extends question_hint {
    /** @var boolean option to show the number of sub-parts of the question that were right. */
    public $shownumcorrect;

    /** @var boolean option to clear the parts of the question that were wrong on retry. */
    public $clearwrong;

    /**
     * Constructor.
     * @param int the hint id from the database.
     * @param string $hint The hint text
     * @param int the corresponding text FORMAT_... type.
     * @param bool $shownumcorrect whether the number of right parts should be shown
     * @param bool $clearwrong whether the wrong parts should be reset.
     */
    public function __construct($id, $hint, $hintformat, $shownumcorrect, $clearwrong) {
        parent::__construct($id, $hint, $hintformat);
        $this->shownumcorrect = $shownumcorrect;
        $this->clearwrong = $clearwrong;
    }

    /**
     * Create a basic hint from a row loaded from the question_hints table in the database.
     * @param object $row with $row->hint, ->shownumcorrect and ->clearwrong set.
     * @return question_hint_with_parts
     */
    public static function load_from_record($row) {
        return new question_hint_with_parts($row->id, $row->hint, $row->hintformat,
            $row->shownumcorrect, $row->clearwrong);
    }

    public function adjust_display_options(question_display_options $options) {
        parent::adjust_display_options($options);
        if ($this->clearwrong) {
            $options->clearwrong = true;
        }
        $options->numpartscorrect = $this->shownumcorrect;
    }
}
