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
 * This class represents a 'question' that actually does not allow the student
 * to respond, like the description 'question' type.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_information_item extends question_definition {
    public function __construct() {
        parent::__construct();
        $this->defaultmark = 0;
        $this->penalty = 0;
        $this->length = 0;
    }

    public function make_behaviour(question_attempt $qa, $preferredbehaviour) {
        return question_engine::make_behaviour('informationitem', $qa, $preferredbehaviour);
    }

    public function get_expected_data() {
        return array();
    }

    public function get_correct_response() {
        return array();
    }

    public function get_question_summary() {
        return null;
    }
}
