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

namespace qbank_viewquestionname;

/**
 * Helper class for view question name plugins to contain all the standard methods.
 *
 * @package    qbank_viewquestionname
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Question in place editing api call.
     *
     * @param \stdClass $question the question object
     * @param string $questiondisplay question display html
     * @return \core\output\inplace_editable
     */
    public static function make_question_name_inplace_editable($question): \core\output\inplace_editable {
        $a = new \stdClass();
        $a->name = format_string($question->name);
        return new \core\output\inplace_editable('qbank_viewquestionname', 'questionname', $question->id,
            question_has_capability_on($question, 'edit'), format_string($question->name), $question->name,
            get_string('edit_question_name_hint', 'qbank_viewquestionname'),
            get_string('edit_question_name_label', 'qbank_viewquestionname', $a));
    }

    public static function question_dispay_helper() {

    }
}
