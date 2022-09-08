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
 * Defines the question behaviour type base class
 *
 * @package    core
 * @subpackage questionbehaviours
 * @copyright  2012 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace core_question\local\behaviour;

use question_display_options;
use question_usage_by_activity;

/**
 * This class represents the type of behaviour, rather than the instance of the
 * behaviour which control a particular question attempt.
 *
 * @copyright  2012 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class behaviour_type_base {
    /**
     * Certain behaviours are definitive of a way that questions can behave when
     * attempted. For example deferredfeedback model, interactive model, etc.
     * These are the options that should be listed in the user-interface, and
     * for these behaviours this method should return true. Other behaviours are
     * more implementation details, for example the informationitem behaviours,
     * or a special subclass like interactive_adapted_for_my_qtype. These
     * behaviours should return false.
     * @return bool whether this is an archetypal behaviour.
     */
    public function is_archetypal() {
        return false;
    }

    /**
     * Override this method if there are some display options that do not make
     * sense 'during the attempt'.
     * @return array of {@link question_display_options} field names, that are
     * not relevant to this behaviour before a 'finish' action.
     */
    public function get_unused_display_options() {
        return array();
    }

    /**
     * With this behaviour, is it possible that a question might finish as the student
     * interacts with it, without a call to the {@link question_attempt::finish()} method?
     * @return bool whether with this behaviour, questions may finish naturally.
     */
    public function can_questions_finish_during_the_attempt() {
        return false;
    }

    /**
     * Adjust a random guess score for a question using this model. You have to
     * do this without knowing details of the specific question, or which usage
     * it is in.
     * @param number $fraction the random guess score from the question type.
     * @return number the adjusted fraction.
     */
    public function adjust_random_guess_score($fraction) {
        return $fraction;
    }

    /**
     * Get summary information about a queston usage.
     *
     * Behaviours are not obliged to do anything here, but this is an opportunity
     * to provide additional information that can be displayed in places like
     * at the top of the quiz review page.
     *
     * In the return value, the array keys should be identifiers of the form
     * qbehaviour_behaviourname_meaningfullkey. For qbehaviour_deferredcbm_highsummary.
     * The values should be arrays with two items, title and content. Each of these
     * should be either a string, or a renderable.
     *
     * To understand how to implement this method, look at the CBM behaviours,
     * and their unit tests.
     *
     * @param question_usage_by_activity $quba the usage to provide summary data for.
     * @return array as described above.
     */
    public function summarise_usage(question_usage_by_activity $quba,
            question_display_options $options) {
        return array();
    }

    /**
     * Does this question behaviour accept multiple submissions of responses within one attempt eg. multiple tries for the
     * interactive or adaptive question behaviours.
     *
     * @return bool
     */
    public function allows_multiple_submitted_responses() {
        return false;
    }
}
