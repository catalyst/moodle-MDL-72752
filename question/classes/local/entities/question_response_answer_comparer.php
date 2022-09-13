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
 * This interface defines the methods that a {@link question_definition} must
 * implement if it is to be graded by the
 * {@link question_first_matching_answer_grading_strategy}.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface question_response_answer_comparer {
    /** @return array of {@link question_answers}. */
    public function get_answers();

    /**
     * @param array $response the response.
     * @param question_answer $answer an answer.
     * @return bool whether the response matches the answer.
     */
    public function compare_response_with_answer(array $response, question_answer $answer);
}
