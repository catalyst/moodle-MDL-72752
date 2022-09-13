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

/**
 * This grading strategy is used by question types like shortanswer an numerical.
 * It gets a list of possible answers from the question, and returns the first one
 * that matches the given response. It returns the first answer with fraction 1.0
 * when asked for the correct answer.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_first_matching_answer_grading_strategy implements question_grading_strategy {
    /**
     * @var question_response_answer_comparer (presumably also a
     * {@link question_definition}) the question we are doing the grading for.
     */
    protected $question;

    /**
     * @param question_response_answer_comparer $question (presumably also a
     * {@link question_definition}) the question we are doing the grading for.
     */
    public function __construct(question_response_answer_comparer $question) {
        $this->question = $question;
    }

    public function grade(array $response) {
        foreach ($this->question->get_answers() as $aid => $answer) {
            if ($this->question->compare_response_with_answer($response, $answer)) {
                $answer->id = $aid;
                return $answer;
            }
        }
        return null;
    }

    public function get_correct_answer() {
        foreach ($this->question->get_answers() as $answer) {
            $state = question_state::graded_state_for_fraction($answer->fraction);
            if ($state == question_state::$gradedright) {
                return $answer;
            }
        }
        return null;
    }
}