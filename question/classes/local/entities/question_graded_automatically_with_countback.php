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
 * This class represents a question that can be graded automatically with
 * countback grading in interactive mode.
 *
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class question_graded_automatically_with_countback
    extends question_graded_automatically
    implements question_automatically_gradable_with_countback {

    public function make_behaviour(question_attempt $qa, $preferredbehaviour) {
        if ($preferredbehaviour == 'interactive') {
            return question_engine::make_behaviour('interactivecountback',
                $qa, $preferredbehaviour);
        }
        return question_engine::make_archetypal_behaviour($preferredbehaviour, $qa);
    }
}
