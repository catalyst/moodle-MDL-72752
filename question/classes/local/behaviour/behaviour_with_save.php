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

use coding_exception;
use question_attempt;
use question_attempt_step;
use question_attempt_pending_step;
use question_state;


/**
 * A subclass of {@link question_behaviour} that implements a save
 * action that is suitable for most questions that implement the
 * {@link question_manually_gradable} interface.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class behaviour_with_save extends behaviour_base {
    public function required_question_definition_type() {
        return 'question_manually_gradable';
    }

    public function apply_attempt_state(question_attempt_step $step) {
        parent::apply_attempt_state($step);
        if ($this->question->is_complete_response($step->get_qt_data())) {
            $step->set_state(question_state::$complete);
        }
    }

    /**
     * Work out whether the response in $pendingstep are significantly different
     * from the last set of responses we have stored.
     * @param question_attempt_step $pendingstep contains the new responses.
     * @return bool whether the new response is the same as we already have.
     */
    protected function is_same_response(question_attempt_step $pendingstep) {
        return $this->question->is_same_response(
                $this->qa->get_last_step()->get_qt_data(), $pendingstep->get_qt_data());
    }

    /**
     * Work out whether the response in $pendingstep represent a complete answer
     * to the question. Normally this will call
     * {@link question_manually_gradable::is_complete_response}, but some
     * behaviours, for example the CBM ones, have their own parts to the
     * response.
     * @param question_attempt_step $pendingstep contains the new responses.
     * @return bool whether the new response is complete.
     */
    protected function is_complete_response(question_attempt_step $pendingstep) {
        return $this->question->is_complete_response($pendingstep->get_qt_data());
    }

    public function process_autosave(question_attempt_pending_step $pendingstep) {
        // If already finished. Nothing to do.
        if ($this->qa->get_state()->is_finished()) {
            return question_attempt::DISCARD;
        }

        // If the new data is the same as we already have, then we don't need it.
        if ($this->is_same_response($pendingstep)) {
            return question_attempt::DISCARD;
        }

        // Repeat that test discarding any existing autosaved data.
        if ($this->qa->has_autosaved_step()) {
            $this->qa->discard_autosaved_step();
            if ($this->is_same_response($pendingstep)) {
                return question_attempt::DISCARD;
            }
        }

        // OK, we need to save.
        return $this->process_save($pendingstep);
    }

    /**
     * Implementation of processing a save action that should be suitable for
     * most subclasses.
     * @param question_attempt_pending_step $pendingstep a partially initialised step
     *      containing all the information about the action that is being peformed.
     * @return bool either {@link question_attempt::KEEP} or {@link question_attempt::DISCARD}
     */
    public function process_save(question_attempt_pending_step $pendingstep) {
        if ($this->qa->get_state()->is_finished()) {
            return question_attempt::DISCARD;
        } else if (!$this->qa->get_state()->is_active()) {
            throw new coding_exception('Question is not active, cannot process_actions.');
        }

        if ($this->is_same_response($pendingstep)) {
            return question_attempt::DISCARD;
        }

        if ($this->is_complete_response($pendingstep)) {
            $pendingstep->set_state(question_state::$complete);
        } else {
            $pendingstep->set_state(question_state::$todo);
        }
        return question_attempt::KEEP;
    }

    public function summarise_submit(question_attempt_step $step) {
        return get_string('submitted', 'question',
                $this->question->summarise_response($step->get_qt_data()));
    }

    public function summarise_save(question_attempt_step $step) {
        $data = $step->get_submitted_data();
        if (empty($data)) {
            return $this->summarise_start($step);
        }
        return get_string('saved', 'question',
                $this->question->summarise_response($step->get_qt_data()));
    }


    public function summarise_finish($step) {
        $data = $step->get_qt_data();
        if ($data) {
            return get_string('attemptfinishedsubmitting', 'question',
                    $this->question->summarise_response($data));
        }
        return get_string('attemptfinished', 'question');
    }
}
