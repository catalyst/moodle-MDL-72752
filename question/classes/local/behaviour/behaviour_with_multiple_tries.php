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

/**
 * Defines the question behaviour base class
 *
 * @package    moodlecore
 * @subpackage questionbehaviours
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace core_question\local\behaviour;

use question_state;

abstract class behaviour_with_multiple_tries extends behaviour_with_save {
    public function step_has_a_submitted_response($step) {
        return $step->has_behaviour_var('submit') && $step->get_state() != question_state::$invalid;
    }
}
