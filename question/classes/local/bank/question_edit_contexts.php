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

namespace core_question\local\bank;

use context_module;

/**
 * Tracks all the contexts related to the one we are currently editing questions and provides helper methods to check permissions.
 *
 * @package   core_question
 * @copyright 2007 Jamie Pratt me@jamiep.org
 * @author    2021 Safat Shahin <safatshahin@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_edit_contexts {

    /**
     * @var \string[][] array of the capabilities.
     */
    public static $caps = [
            'editq' => [
                    'moodle/question:add',
                    'moodle/question:editmine',
                    'moodle/question:editall',
                    'moodle/question:viewmine',
                    'moodle/question:viewall',
                    'moodle/question:usemine',
                    'moodle/question:useall',
                    'moodle/question:movemine',
                    'moodle/question:moveall'],
            'questions' => [
                    'moodle/question:add',
                    'moodle/question:editmine',
                    'moodle/question:editall',
                    'moodle/question:viewmine',
                    'moodle/question:viewall',
                    'moodle/question:movemine',
                    'moodle/question:moveall'],
            'categories' => [
                    'moodle/question:managecategory'],
            'import' => [
                    'moodle/question:add'],
            'export' => [
                    'moodle/question:viewall',
                    'moodle/question:viewmine']];

    /**
     * @var array of contexts.
     */
    protected $allcontexts;

    /**
     * Constructor
     * @param \context $thiscontext the current context.
     * @param int $courseid course id to get the qbanks in the course
     */
    public function __construct(\context $thiscontext, $courseid = null) {
        $contexts = [$thiscontext];
        if ($courseid) {
            $qbankmodules = get_coursemodules_in_course('qbank', $courseid);
            if (!empty($qbankmodules)) {
                foreach ($qbankmodules as $qbankmodule) {
                    $contexts [] = context_module::instance($qbankmodule->id);
                }
            }
        }
        // System context.
        $systemqbankmodules = get_coursemodules_in_course('qbank', 1);
        if (!empty($systemqbankmodules)) {
            foreach ($systemqbankmodules as $systemqbankmodule) {
                $contexts [] = context_module::instance($systemqbankmodule->id);
            }
        }
        $this->allcontexts = array_values($contexts);
    }

    /**
     * Get all the contexts.
     *
     * @return \context[] all parent contexts
     */
    public function all() {
        return $this->allcontexts;
    }

    /**
     * Get the lowest context.
     *
     * @return \context lowest context which must be either the module or course context
     */
    public function lowest() {
        return $this->allcontexts[0];
    }

    /**
     * Get the contexts having cap.
     *
     * @param string $cap capability
     * @return \context[] parent contexts having capability, zero based index
     */
    public function having_cap($cap) {
        $contextswithcap = [];
        foreach ($this->allcontexts as $context) {
            if (has_capability($cap, $context)) {
                $contextswithcap[] = $context;
            }
        }
        return $contextswithcap;
    }

    /**
     * Get the contexts having at least one cap.
     *
     * @param array $caps capabilities
     * @return \context[] parent contexts having at least one of $caps, zero based index
     */
    public function having_one_cap($caps) {
        $contextswithacap = [];
        foreach ($this->allcontexts as $context) {
            foreach ($caps as $cap) {
                if (has_capability($cap, $context)) {
                    $contextswithacap[] = $context;
                    break; // Done with caps loop.
                }
            }
        }
        return $contextswithacap;
    }

    /**
     * Context having at least one cap.
     *
     * @param string $tabname edit tab name
     * @return \context[] parent contexts having at least one of $caps, zero based index
     */
    public function having_one_edit_tab_cap($tabname) {
        return $this->having_one_cap(self::$caps[$tabname]);
    }

    /**
     * Contexts for adding question and also using it.
     *
     * @return \context[] those contexts where a user can add a question and then use it.
     */
    public function having_add_and_use() {
        $contextswithcap = [];
        foreach ($this->allcontexts as $context) {
            if (!has_capability('moodle/question:add', $context)) {
                continue;
            }
            if (!has_any_capability(['moodle/question:useall', 'moodle/question:usemine'], $context)) {
                continue;
            }
            $contextswithcap[] = $context;
        }
        return $contextswithcap;
    }

    /**
     * Has at least one parent context got the cap $cap?
     *
     * @param string $cap capability
     * @return boolean
     */
    public function have_cap($cap) {
        return (count($this->having_cap($cap)));
    }

    /**
     * Has at least one parent context got one of the caps $caps?
     *
     * @param array $caps capability
     * @return boolean
     */
    public function have_one_cap($caps) {
        foreach ($caps as $cap) {
            if ($this->have_cap($cap)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Has at least one parent context got one of the caps for actions on $tabname
     *
     * @param string $tabname edit tab name
     * @return boolean
     */
    public function have_one_edit_tab_cap($tabname) {
        return $this->have_one_cap(self::$caps[$tabname]);
    }

    /**
     * Throw error if at least one parent context hasn't got the cap $cap
     *
     * @param string $cap capability
     */
    public function require_cap($cap) {
        if (!$this->have_cap($cap)) {
            throw new \moodle_exception('nopermissions', '', '', $cap);
        }
    }

    /**
     * Throw error if at least one parent context hasn't got one of the caps $caps
     *
     * @param array $caps capabilities
     */
    public function require_one_cap($caps) {
        if (!$this->have_one_cap($caps)) {
            $capsstring = join(', ', $caps);
            throw new \moodle_exception('nopermissions', '', '', $capsstring);
        }
    }

    /**
     * Throw error if at least one parent context hasn't got one of the caps $caps
     *
     * @param string $tabname edit tab name
     */
    public function require_one_edit_tab_cap($tabname) {
        if (!$this->have_one_edit_tab_cap($tabname)) {
            throw new \moodle_exception('nopermissions', '', '', 'access question edit tab '.$tabname);
        }
    }

    /**
     * Get the array of capabilities for question.
     *
     * @return array all the capabilities that relate to accessing particular questions.
     */
    public static function question_get_question_capabilities(): array {
        return [
            'moodle/question:add',
            'moodle/question:editmine',
            'moodle/question:editall',
            'moodle/question:viewmine',
            'moodle/question:viewall',
            'moodle/question:usemine',
            'moodle/question:useall',
            'moodle/question:movemine',
            'moodle/question:moveall',
            'moodle/question:tagmine',
            'moodle/question:tagall',
            'moodle/question:commentmine',
            'moodle/question:commentall',
        ];
    }

    /**
     * Get the question bank caps.
     *
     * @return array all the question bank capabilities.
     */
    public static function question_get_all_capabilities(): array {
        $caps = self::question_get_question_capabilities();
        $caps[] = 'moodle/question:managecategory';
        $caps[] = 'moodle/question:flag';
        return $caps;
    }

    /**
     * Require capability on question.
     *
     * @param object|int $question
     * @param string $cap
     * @return bool
     */
    public static function question_require_capability_on($question, $cap): bool {
        if (!\core_question\local\bank\question_edit_contexts::question_has_capability_on($question, $cap)) {
            throw new \moodle_exception('nopermissions', '', '', $cap);
        }
        return true;
    }

    /**
     * Check capability on category.
     *
     * @param int|\stdClass|\question_definition $questionorid object or id.
     *      If an object is passed, it should include ->contextid and ->createdby.
     * @param string $cap 'add', 'edit', 'view', 'use', 'move' or 'tag'.
     * @return bool this user has the capability $cap for this question $question?
     */
    public static function question_has_capability_on($questionorid, $cap): bool {
        global $USER, $DB;

        if (is_numeric($questionorid)) {
            $questionid = (int)$questionorid;
        } else if (is_object($questionorid)) {
            // All we really need in this function is the contextid and author of the question.
            // We won't bother fetching other details of the question if these 2 fields are provided.
            if (isset($questionorid->contextid) && isset($questionorid->createdby)) {
                $question = $questionorid;
            } else if (!empty($questionorid->id)) {
                $questionid = $questionorid->id;
            }
        }

        // At this point, either $question or $questionid is expected to be set.
        if (isset($questionid)) {
            try {
                $question = \question_bank::load_question_data($questionid);
            } catch (\Exception $e) {
                // Let's log the exception for future debugging,
                // but not during Behat, or we can't test these cases.
                if (!defined('BEHAT_SITE_RUNNING')) {
                    debugging($e->getMessage(), DEBUG_NORMAL, $e->getTrace());
                }

                $sql = 'SELECT q.id,
                               q.createdby,
                               qc.contextid
                          FROM {question} q
                          JOIN {question_versions} qv
                            ON qv.questionid = q.id
                          JOIN {question_bank_entries} qbe
                            ON qbe.id = qv.questionbankentryid
                          JOIN {question_categories} qc
                            ON qc.id = qbe.questioncategoryid
                         WHERE q.id = :id';

                // Well, at least we tried. Seems that we really have to read from DB.
                $question = $DB->get_record_sql($sql, ['id' => $questionid]);
            }
        }

        if (!isset($question)) {
            throw new \coding_exception('$questionorid parameter needs to be an integer or an object.');
        }

        $context = \context::instance_by_id($question->contextid);

        // These are existing questions capabilities that are set per category.
        // Each of these has a 'mine' and 'all' version that is appended to the capability name.
        $capabilitieswithallandmine = ['edit' => 1, 'view' => 1, 'use' => 1, 'move' => 1, 'tag' => 1, 'comment' => 1];

        if (!isset($capabilitieswithallandmine[$cap])) {
            return has_capability('moodle/question:' . $cap, $context);
        }

        return has_capability('moodle/question:' . $cap . 'all', $context) ||
            ($question->createdby == $USER->id && has_capability('moodle/question:' . $cap . 'mine', $context));
    }
}
