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
 * This class represents a real question. That is, one that is not a
 * {@link question_information_item}.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class question_with_responses extends question_definition
    implements question_manually_gradable {
    public function classify_response(array $response) {
        return array();
    }

    public function is_gradable_response(array $response) {
        return $this->is_complete_response($response);
    }

    public function un_summarise_response(string $summary) {
        throw new coding_exception('This question type (' . get_class($this) .
            ' does not implement the un_summarise_response testing method.');
    }
}
