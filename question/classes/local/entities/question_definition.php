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

use coding_exception;
use moodle_page;
use question_attempt;
use question_attempt_step;
use question_display_options;
use question_engine;
use question_utils;
use stdClass;

/**
 * The definition of a question of a particular type.
 *
 * This class is a close match to the question table in the database.
 * Definitions of question of a particular type normally subclass one of the
 * more specific classes {@link question_with_responses},
 * {@link question_graded_automatically} or {@link question_information_item}.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class question_definition {
    /** @var integer id of the question in the datase, or null if this question
     * is not in the database. */
    public $id;

    /** @var integer question category id. */
    public $category;

    /** @var integer question context id. */
    public $contextid;

    /** @var integer parent question id. */
    public $parent = 0;

    /** @var question_type the question type this question is. */
    public $qtype;

    /** @var string question name. */
    public $name;

    /** @var string question text. */
    public $questiontext;

    /** @var integer question test format. */
    public $questiontextformat;

    /** @var string question general feedback. */
    public $generalfeedback;

    /** @var integer question test format. */
    public $generalfeedbackformat;

    /** @var number what this quetsion is marked out of, by default. */
    public $defaultmark = 1;

    /** @var integer How many question numbers this question consumes. */
    public $length = 1;

    /** @var number penalty factor of this question. */
    public $penalty = 0;

    /** @var string unique identifier of this question. */
    public $stamp;

    /** @var string question idnumber. */
    public $idnumber;

    /** @var integer timestamp when this question was created. */
    public $timecreated;

    /** @var integer timestamp when this question was modified. */
    public $timemodified;

    /** @var integer userid of the use who created this question. */
    public $createdby;

    /** @var integer userid of the use who modified this question. */
    public $modifiedby;

    /** @var array of question_hints. */
    public $hints = array();

    /** @var boolean question status hidden/ready/draft in the question bank. */
    public $status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;

    /** @var int Version id of the question in a question bank */
    public $versionid;

    /** @var int Version number of the question in a question bank */
    public $version;

    /** @var int Bank entry id for the question */
    public $questionbankentryid;

    /**
     * @var array of array of \core_customfield\data_controller objects indexed by fieldid for the questions custom fields.
     */
    public $customfields = array();

    /**
     * Constructor. Normally to get a question, you call
     * {@link question_bank::load_question()}, but questions can be created
     * directly, for example in unit test code.
     * @return unknown_type
     */
    public function __construct() {
    }

    /**
     * @return the name of the question type (for example multichoice) that this
     * question is.
     */
    public function get_type_name() {
        return $this->qtype->name();
    }

    /**
     * Creat the appropriate behaviour for an attempt at this quetsion,
     * given the desired (archetypal) behaviour.
     *
     * This default implementation will suit most normal graded questions.
     *
     * If your question is of a patricular type, then it may need to do something
     * different. For example, if your question can only be graded manually, then
     * it should probably return a manualgraded behaviour, irrespective of
     * what is asked for.
     *
     * If your question wants to do somthing especially complicated is some situations,
     * then you may wish to return a particular behaviour related to the
     * one asked for. For example, you migth want to return a
     * qbehaviour_interactive_adapted_for_myqtype.
     *
     * @param question_attempt $qa the attempt we are creating a behaviour for.
     * @param string $preferredbehaviour the requested type of behaviour.
     * @return question_behaviour the new behaviour object.
     */
    public function make_behaviour(question_attempt $qa, $preferredbehaviour) {
        return question_engine::make_archetypal_behaviour($preferredbehaviour, $qa);
    }

    /**
     * Start a new attempt at this question, storing any information that will
     * be needed later in the step.
     *
     * This is where the question can do any initialisation required on a
     * per-attempt basis. For example, this is where the multiple choice
     * question type randomly shuffles the choices (if that option is set).
     *
     * Any information about how the question has been set up for this attempt
     * should be stored in the $step, by calling $step->set_qt_var(...).
     *
     * @param question_attempt_step The first step of the {@link question_attempt}
     *      being started. Can be used to store state.
     * @param int $varant which variant of this question to start. Will be between
     *      1 and {@link get_num_variants()} inclusive.
     */
    public function start_attempt(question_attempt_step $step, $variant) {
    }

    /**
     * When an in-progress {@link question_attempt} is re-loaded from the
     * database, this method is called so that the question can re-initialise
     * its internal state as needed by this attempt.
     *
     * For example, the multiple choice question type needs to set the order
     * of the choices to the order that was set up when start_attempt was called
     * originally. All the information required to do this should be in the
     * $step object, which is the first step of the question_attempt being loaded.
     *
     * @param question_attempt_step The first step of the {@link question_attempt}
     *      being loaded.
     */
    public function apply_attempt_state(question_attempt_step $step) {
    }

    /**
     * Verify if an attempt at this question can be re-graded using the other question version.
     *
     * To put it another way, will {@see update_attempt_state_date_from_old_version()} be able to work?
     *
     * It is expected that this relationship is symmetrical, so if you can regrade from V1 to V3, then
     * you can change back from V3 to V1.
     *
     * @param question_definition $otherversion a different version of the question to use in the regrade.
     * @return string|null null if the regrade can proceed, else a reason why not.
     */
    public function validate_can_regrade_with_other_version(question_definition $otherversion): ?string {
        if (get_class($otherversion) !== get_class($this)) {
            return get_string('cannotregradedifferentqtype', 'question');
        }

        return null;
    }

    /**
     * Update the data representing the initial state of an attempt another version of this question, to allow for the changes.
     *
     * What is required is probably most easily understood using an example. Think about multiple choice questions.
     * The first step has a variable '_order' which is a comma-separated list of question_answer ids.
     * A different version of the question will have different question_answers with different ids. However, the list of
     * choices should be similar, and so we need to shuffle the new list of ids in the same way that the old one was.
     *
     * This method should only be called if {@see validate_can_regrade_with_other_version()} did not
     * flag up a potential problem. So, this method will throw a {@see coding_exception} if it is not
     * possible to work out a return value.
     *
     * @param question_attempt_step $oldstep the first step of a {@see question_attempt} at $oldquestion.
     * @param question_definition $oldquestion the previous version of the question, which $oldstate comes from.
     * @return array the submit data which can be passed to {@see apply_attempt_state} to start
     *     an attempt at this version of this question, corresponding to the attempt at the old question.
     * @throws coding_exception if this can't be done.
     */
    public function update_attempt_state_data_for_new_version(
            question_attempt_step $oldstep, question_definition $oldquestion) {
        $message = $this->validate_can_regrade_with_other_version($oldquestion);
        if ($message) {
            throw new coding_exception($message);
        }

        return $oldstep->get_qt_data();
    }

    /**
     * Generate a brief, plain-text, summary of this question. This is used by
     * various reports. This should show the particular variant of the question
     * as presented to students. For example, the calculated quetsion type would
     * fill in the particular numbers that were presented to the student.
     * This method will return null if such a summary is not possible, or
     * inappropriate.
     * @return string|null a plain text summary of this question.
     */
    public function get_question_summary() {
        return $this->html_to_text($this->questiontext, $this->questiontextformat);
    }

    /**
     * @return int the number of vaiants that this question has.
     */
    public function get_num_variants() {
        return 1;
    }

    /**
     * @return string that can be used to seed the pseudo-random selection of a
     *      variant.
     */
    public function get_variants_selection_seed() {
        return $this->stamp;
    }

    /**
     * Some questions can return a negative mark if the student gets it wrong.
     *
     * This method returns the lowest mark the question can return, on the
     * fraction scale. that is, where the maximum possible mark is 1.0.
     *
     * @return float minimum fraction this question will ever return.
     */
    public function get_min_fraction() {
        return 0;
    }

    /**
     * Some questions can return a mark greater than the maximum.
     *
     * This method returns the lowest highest the question can return, on the
     * fraction scale. that is, where the nominal maximum mark is 1.0.
     *
     * @return float maximum fraction this question will ever return.
     */
    public function get_max_fraction() {
        return 1;
    }

    /**
     * Given a response, rest the parts that are wrong.
     * @param array $response a response
     * @return array a cleaned up response with the wrong bits reset.
     */
    public function clear_wrong_from_response(array $response) {
        return array();
    }

    /**
     * Return the number of subparts of this response that are right.
     * @param array $response a response
     * @return array with two elements, the number of correct subparts, and
     * the total number of subparts.
     */
    public function get_num_parts_right(array $response) {
        return array(null, null);
    }

    /**
     * @param moodle_page the page we are outputting to.
     * @return type_renderer_base the renderer to use for outputting this question.
     */
    public function get_renderer(moodle_page $page) {
        return $page->get_renderer($this->qtype->plugin_name());
    }

    /**
     * What data may be included in the form submission when a student submits
     * this question in its current state?
     *
     * This information is used in calls to optional_param. The parameter name
     * has {@link question_attempt::get_field_prefix()} automatically prepended.
     *
     * @return array|string variable name => PARAM_... constant, or, as a special case
     *      that should only be used in unavoidable, the constant question_attempt::USE_RAW_DATA
     *      meaning take all the raw submitted data belonging to this question.
     */
    public abstract function get_expected_data();

    /**
     * What data would need to be submitted to get this question correct.
     * If there is more than one correct answer, this method should just
     * return one possibility. If it is not possible to compute a correct
     * response, this method should return null.
     *
     * @return array|null parameter name => value.
     */
    public abstract function get_correct_response();


    /**
     * Takes an array of values representing a student response represented in a way that is understandable by a human and
     * transforms that to the response as the POST values returned from the HTML form that takes the student response during a
     * student attempt. Primarily this is used when reading csv values from a file of student responses in order to be able to
     * simulate the student interaction with a quiz.
     *
     * In most cases the array will just be returned as is. Some question types will need to transform the keys of the array,
     * as the meaning of the keys in the html form is deliberately obfuscated so that someone looking at the html does not get an
     * advantage. The values that represent the response might also be changed in order to more meaningful to a human.
     *
     * See the examples of question types that have overridden this in core and also see the csv files of simulated student
     * responses used in unit tests in :
     * - mod/quiz/tests/fixtures/stepsXX.csv
     * - mod/quiz/report/responses/tests/fixtures/steps00.csv
     * - mod/quiz/report/statistics/tests/fixtures/stepsXX.csv
     *
     * Also see {@link https://github.com/jamiepratt/moodle-quiz_simulate}, a quiz report plug in for uploading and downloading
     * student responses as csv files.
     *
     * @param array $simulatedresponse an array of data representing a student response
     * @return array a response array as would be returned from the html form (but without prefixes)
     */
    public function prepare_simulated_post_data($simulatedresponse) {
        return $simulatedresponse;
    }

    /**
     * Does the opposite of {@link prepare_simulated_post_data}.
     *
     * This takes a student response (the POST values returned from the HTML form that takes the student response during a
     * student attempt) it then represents it in a way that is understandable by a human.
     *
     * Primarily this is used when creating a file of csv from real student responses in order later to be able to
     * simulate the same student interaction with a quiz later.
     *
     * @param string[] $realresponse the response array as was returned from the form during a student attempt (without prefixes).
     * @return string[] an array of data representing a student response.
     */
    public function get_student_response_values_for_simulation($realresponse) {
        return $realresponse;
    }

    /**
     * Apply {@link format_text()} to some content with appropriate settings for
     * this question.
     *
     * @param string $text some content that needs to be output.
     * @param int $format the FORMAT_... constant.
     * @param question_attempt $qa the question attempt.
     * @param string $component used for rewriting file area URLs.
     * @param string $filearea used for rewriting file area URLs.
     * @param bool $clean Whether the HTML needs to be cleaned. Generally,
     *      parts of the question do not need to be cleaned, and student input does.
     * @return string the text formatted for output by format_text.
     */
    public function format_text($text, $format, $qa, $component, $filearea, $itemid,
            $clean = false) {
        $formatoptions = new stdClass();
        $formatoptions->noclean = !$clean;
        $formatoptions->para = false;
        $text = $qa->rewrite_pluginfile_urls($text, $component, $filearea, $itemid);
        return format_text($text, $format, $formatoptions);
    }

    /**
     * Convert some part of the question text to plain text. This might be used,
     * for example, by get_response_summary().
     * @param string $text The HTML to reduce to plain text.
     * @param int $format the FORMAT_... constant.
     * @return string the equivalent plain text.
     */
    public function html_to_text($text, $format) {
        return question_utils::to_plain_text($text, $format);
    }

    /** @return the result of applying {@link format_text()} to the question text. */
    public function format_questiontext($qa) {
        return $this->format_text($this->questiontext, $this->questiontextformat,
                $qa, 'question', 'questiontext', $this->id);
    }

    /** @return the result of applying {@link format_text()} to the general feedback. */
    public function format_generalfeedback($qa) {
        return $this->format_text($this->generalfeedback, $this->generalfeedbackformat,
                $qa, 'question', 'generalfeedback', $this->id);
    }

    /**
     * Take some HTML that should probably already be a single line, like a
     * multiple choice choice, or the corresponding feedback, and make it so that
     * it is suitable to go in a place where the HTML must be inline, like inside a <p> tag.
     * @param string $html to HTML to fix up.
     * @return string the fixed HTML.
     */
    public function make_html_inline($html) {
        $html = preg_replace('~\s*<p>\s*~u', '', $html);
        $html = preg_replace('~\s*</p>\s*~u', '<br />', $html);
        $html = preg_replace('~(<br\s*/?>)+$~u', '', $html);
        return trim($html);
    }

    /**
     * Checks whether the users is allow to be served a particular file.
     * @param question_attempt $qa the question attempt being displayed.
     * @param question_display_options $options the options that control display of the question.
     * @param string $component the name of the component we are serving files for.
     * @param string $filearea the name of the file area.
     * @param array $args the remaining bits of the file path.
     * @param bool $forcedownload whether the user must be forced to download the file.
     * @return bool true if the user can access this file.
     */
    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        if ($component == 'question' && $filearea == 'questiontext') {
            // Question text always visible, but check it is the right question id.
            return $args[0] == $this->id;

        } else if ($component == 'question' && $filearea == 'generalfeedback') {
            return $options->generalfeedback && $args[0] == $this->id;

        } else {
            // Unrecognised component or filearea.
            return false;
        }
    }

    /**
     * Return the question settings that define this question as structured data.
     *
     * This is used by external systems such as the Moodle mobile app, which want to display the question themselves,
     * rather than using the renderer provided.
     *
     * This method should only return the data that the student is allowed to see or know, given the current state of
     * the question. For example, do not include the 'General feedback' until the student has completed the question,
     * and even then, only include it if the question_display_options say it should be visible.
     *
     * But, within those rules, it is recommended that you return all the settings for the question,
     * to give maximum flexibility to the external system providing its own rendering of the question.
     *
     * @param question_attempt $qa the current attempt for which we are exporting the settings.
     * @param question_display_options $options the question display options which say which aspects of the question
     * should be visible.
     * @return mixed structure representing the question settings. In web services, this will be JSON-encoded.
     */
    public function get_question_definition_for_external_rendering(question_attempt $qa, question_display_options $options) {

        debugging('This question does not implement the get_question_definition_for_external_rendering() method yet.',
            DEBUG_DEVELOPER);
        return null;
    }
}
