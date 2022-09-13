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
 * Class to represent a question answer, loaded from the question_answers table
 * in the database.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_answer {
    /** @var integer the answer id. */
    public $id;

    /** @var string the answer. */
    public $answer;

    /** @var integer one of the FORMAT_... constans. */
    public $answerformat = FORMAT_PLAIN;

    /** @var number the fraction this answer is worth. */
    public $fraction;

    /** @var string the feedback for this answer. */
    public $feedback;

    /** @var integer one of the FORMAT_... constans. */
    public $feedbackformat;

    /**
     * Constructor.
     * @param int $id the answer.
     * @param string $answer the answer.
     * @param number $fraction the fraction this answer is worth.
     * @param string $feedback the feedback for this answer.
     * @param int $feedbackformat the format of the feedback.
     */
    public function __construct($id, $answer, $fraction, $feedback, $feedbackformat) {
        $this->id = $id;
        $this->answer = $answer;
        $this->fraction = $fraction;
        $this->feedback = $feedback;
        $this->feedbackformat = $feedbackformat;
    }
}

