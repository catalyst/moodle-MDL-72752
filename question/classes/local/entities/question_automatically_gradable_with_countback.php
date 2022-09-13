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
 * Interface that a {@link question_definition} must implement to be usable by
 * the interactivecountback behaviour.
 *
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface question_automatically_gradable_with_countback extends question_automatically_gradable {
    /**
     * Work out a final grade for this attempt, taking into account all the
     * tries the student made.
     * @param array $responses the response for each try. Each element of this
     * array is a response array, as would be passed to {@link grade_response()}.
     * There may be between 1 and $totaltries responses.
     * @param int $totaltries The maximum number of tries allowed.
     * @return numeric the fraction that should be awarded for this
     * sequence of response.
     */
    public function compute_final_grade($responses, $totaltries);
}
