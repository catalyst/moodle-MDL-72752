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
 * The default questiontype class.
 *
 * @package    moodlecore
 * @subpackage questiontypes
 * @copyright  1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace core_question\local\type;

require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->libdir . '/questionlib.php');

/**
 * This class is used in the return value from
 * {@link question_type::get_possible_responses()}.
 *
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_possible_response {
    /**
     * @var string the classification of this response the student gave to this
     * part of the question. Must match one of the responseclasses returned by
     * {@link question_type::get_possible_responses()}.
     */
    public $responseclass;

    /** @var string the (partial) credit awarded for this responses. */
    public $fraction;

    /**
     * Constructor, just an easy way to set the fields.
     * @param string $responseclassid see the field descriptions above.
     * @param string $response see the field descriptions above.
     * @param number $fraction see the field descriptions above.
     */
    public function __construct($responseclass, $fraction) {
        $this->responseclass = $responseclass;
        $this->fraction = $fraction;
    }

    public static function no_response() {
        return new question_possible_response(get_string('noresponse', 'question'), 0);
    }
}
