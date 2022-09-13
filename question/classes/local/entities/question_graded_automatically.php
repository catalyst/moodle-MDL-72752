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

use question_attempt;

/**
 * This class represents a question that can be graded automatically.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class question_graded_automatically extends question_with_responses
    implements question_automatically_gradable {
    /** @var Some question types have the option to show the number of sub-parts correct. */
    public $shownumcorrect = false;

    public function get_right_answer_summary() {
        $correctresponse = $this->get_correct_response();
        if (empty($correctresponse)) {
            return null;
        }
        return $this->summarise_response($correctresponse);
    }

    /**
     * Check a request for access to a file belonging to a combined feedback field.
     * @param question_attempt $qa the question attempt being displayed.
     * @param question_display_options $options the options that control display of the question.
     * @param string $filearea the name of the file area.
     * @param array $args the remaining bits of the file path.
     * @return bool whether access to the file should be allowed.
     */
    protected function check_combined_feedback_file_access($qa, $options, $filearea, $args = null) {
        $state = $qa->get_state();

        if ($args === null) {
            debugging('You must pass $args as the fourth argument to check_combined_feedback_file_access.',
                DEBUG_DEVELOPER);
            $args = array($this->id); // Fake it for now, so the rest of this method works.
        }

        if (!$state->is_finished()) {
            $response = $qa->get_last_qt_data();
            if (!$this->is_gradable_response($response)) {
                return false;
            }
            list($notused, $state) = $this->grade_response($response);
        }

        return $options->feedback && $state->get_feedback_class() . 'feedback' == $filearea &&
            $args[0] == $this->id;
    }

    /**
     * Check a request for access to a file belonging to a hint.
     * @param question_attempt $qa the question attempt being displayed.
     * @param question_display_options $options the options that control display of the question.
     * @param array $args the remaining bits of the file path.
     * @return bool whether access to the file should be allowed.
     */
    protected function check_hint_file_access($qa, $options, $args) {
        if (!$options->feedback) {
            return false;
        }
        $hint = $qa->get_applicable_hint();
        $hintid = reset($args); // Itemid is hint id.
        return $hintid == $hint->id;
    }

    public function get_hint($hintnumber, question_attempt $qa) {
        if (!isset($this->hints[$hintnumber])) {
            return null;
        }
        return $this->hints[$hintnumber];
    }

    public function format_hint(question_hint $hint, question_attempt $qa) {
        return $this->format_text($hint->hint, $hint->hintformat, $qa,
            'question', 'hint', $hint->id);
    }
}