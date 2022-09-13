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
 * This class represents a question that can be graded automatically by using
 * a {@link question_grading_strategy}.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class question_graded_by_strategy extends question_graded_automatically {
    /** @var question_grading_strategy the strategy to use for grading. */
    protected $gradingstrategy;

    /** @param question_grading_strategy  $strategy the strategy to use for grading. */
    public function __construct(question_grading_strategy $strategy) {
        parent::__construct();
        $this->gradingstrategy = $strategy;
    }

    public function get_correct_response() {
        $answer = $this->get_correct_answer();
        if (!$answer) {
            return array();
        }

        return array('answer' => $answer->answer);
    }

    /**
     * Get an answer that contains the feedback and fraction that should be
     * awarded for this resonse.
     * @param array $response a response.
     * @return question_answer the matching answer.
     */
    public function get_matching_answer(array $response) {
        return $this->gradingstrategy->grade($response);
    }

    /**
     * @return question_answer an answer that contains the a response that would
     *      get full marks.
     */
    public function get_correct_answer() {
        return $this->gradingstrategy->get_correct_answer();
    }

    public function grade_response(array $response) {
        $answer = $this->get_matching_answer($response);
        if ($answer) {
            return array($answer->fraction,
                question_state::graded_state_for_fraction($answer->fraction));
        } else {
            return array(0, question_state::$gradedwrong);
        }
    }

    public function classify_response(array $response) {
        if (empty($response['answer'])) {
            return array($this->id => question_classified_response::no_response());
        }

        $ans = $this->get_matching_answer($response);
        if (!$ans) {
            return array($this->id => new question_classified_response(
                0, $response['answer'], 0));
        }

        return array($this->id => new question_classified_response(
            $ans->id, $response['answer'], $ans->fraction));
    }
}