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
 * This question_grading_strategy interface. Used to share grading code between
 * questions that that subclass {@link question_graded_by_strategy}.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface question_grading_strategy {
    /**
     * Return a question answer that describes the outcome (fraction and feeback)
     * for a particular respons.
     * @param array $response the response.
     * @return question_answer the answer describing the outcome.
     */
    public function grade(array $response);

    /**
     * @return question_answer an answer that contains the a response that would
     *      get full marks.
     */
    public function get_correct_answer();
}
