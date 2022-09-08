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


/**
 * This class exists to allow behaviours that worked in Moodle 2.3 to continue
 * to work. It implements the question_behaviour_type API for the other behaviour
 * as much as possible in a backwards-compatible way.
 *
 * @copyright  2012 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behaviour_type_fallback extends behaviour_type_base {

    /** @var string the behaviour class name. */
    protected $behaviourclass;

    /**
     * @param string $behaviourtype the type of behaviour we are providing a fallback for.
     */
    public function __construct($behaviour) {
        question_engine::load_behaviour_class($behaviour);
        $this->behaviourclass = 'qbehaviour_' . $behaviour;
    }

    public function is_archetypal() {
        return constant($this->behaviourclass . '::IS_ARCHETYPAL');
    }

    /**
     * Override this method if there are some display options that do not make
     * sense 'during the attempt'.
     * @return array of {@link question_display_options} field names, that are
     * not relevant to this behaviour before a 'finish' action.
     */
    public function get_unused_display_options() {
        return call_user_func(array($this->behaviourclass, 'get_unused_display_options'));
    }

    /**
     * Adjust a random guess score for a question using this model. You have to
     * do this without knowing details of the specific question, or which usage
     * it is in.
     * @param number $fraction the random guess score from the question type.
     * @return number the adjusted fraction.
     */
    public function adjust_random_guess_score($fraction) {
        return call_user_func(array($this->behaviourclass, 'adjust_random_guess_score'),
            $fraction);
    }
}
