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

/**
 * Manager class to handle question options and its associated operations.
 *
 * @package    core_question
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_options_manager {

    /**
     * Given a list of ids, load the basic information about a set of questions from
     * the questions table. The $join and $extrafields arguments can be used together
     * to pull in extra data. See, for example, the usage in mod/quiz/attemptlib.php, and
     * read the code below to see how the SQL is assembled. Throws exceptions on error.
     *
     * @param array $questionids array of question ids to load. If null, then all
     * questions matched by $join will be loaded.
     * @param string $extrafields extra SQL code to be added to the query.
     * @param string $join extra SQL code to be added to the query.
     * @param array $extraparams values for any placeholders in $join.
     * You must use named placeholders.
     * @param string $orderby what to order the results by. Optional, default is unspecified order.
     *
     * @return array partially complete question objects. You need to call get_question_options
     * on them before they can be properly used.
     */
    public static function question_preload_questions($questionids = null, $extrafields = '', $join = '',
        $extraparams = [], $orderby = ''): array {
        global $DB;

        if ($questionids === null) {
            $extracondition = '';
            $params = [];
        } else {
            if (empty($questionids)) {
                return [];
            }

            list($questionidcondition, $params) = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED, 'qid0000');
            $extracondition = 'WHERE q.id ' . $questionidcondition;
        }

        if ($join) {
            $join = 'JOIN ' . $join;
        }

        if ($extrafields) {
            $extrafields = ', ' . $extrafields;
        }

        if ($orderby) {
            $orderby = 'ORDER BY ' . $orderby;
        }

        $sql = "SELECT q.*,
                   qc.id as category,
                   qv.status,
                   qv.id as versionid,
                   qv.version,
                   qv.questionbankentryid,
                   qc.contextid as contextid
                   {$extrafields}
              FROM {question} q
              JOIN {question_versions} qv
                ON qv.questionid = q.id
              JOIN {question_bank_entries} qbe
                ON qbe.id = qv.questionbankentryid
              JOIN {question_categories} qc
                ON qc.id = qbe.questioncategoryid
              {$join}
              {$extracondition}
              {$orderby}";

        // Load the questions.
        $questions = $DB->get_records_sql($sql, $extraparams + $params);
        foreach ($questions as $question) {
            $question->_partiallyloaded = true;
        }

        return $questions;
    }
    /**
     * Private function to factor common code out of get_question_options().
     *
     * @param object $question the question to tidy.
     * @param \stdClass $category The question_categories record for the given $question.
     * @param \stdClass[]|null $tagobjects The tags for the given $question.
     * @param \stdClass[]|null $filtercourses The courses to filter the course tags by.
     */
    public static function _tidy_question($question, $category, array $tagobjects = null, array $filtercourses = null): void {
        // Load question-type specific fields.
        if (!\question_bank::is_qtype_installed($question->qtype)) {
            $question->questiontext = \html_writer::tag('p', get_string('warningmissingtype',
                    'qtype_missingtype')) . $question->questiontext;
        }

        // Convert numeric fields to float (Prevents these being displayed as 1.0000000.).
        $question->defaultmark += 0;
        $question->penalty += 0;

        if (isset($question->_partiallyloaded)) {
            unset($question->_partiallyloaded);
        }

        $question->categoryobject = $category;
        \question_bank::get_qtype($question->qtype)->get_question_options($question);

        if (!is_null($tagobjects)) {
            $categorycontext = \context::instance_by_id($category->contextid);
            $sortedtagobjects = \core_question\local\bank\question_tags_manager::question_sort_tags($tagobjects, $categorycontext, $filtercourses);
            $question->coursetagobjects = $sortedtagobjects->coursetagobjects;
            $question->coursetags = $sortedtagobjects->coursetags;
            $question->tagobjects = $sortedtagobjects->tagobjects;
            $question->tags = $sortedtagobjects->tags;
        }
    }

    /**
     * Save a new question type order to the config_plugins table.
     *
     * @param array $neworder An arra $index => $qtype. Indices should start at 0 and be in order.
     * @param object $config get_config('question'), if you happen to have it around, to save one DB query.
     */
    public static function question_save_qtype_order($neworder, $config = null): void {
        if (is_null($config)) {
            $config = get_config('question');
        }

        foreach ($neworder as $index => $qtype) {
            $sortvar = $qtype . '_sortorder';
            if (!isset($config->$sortvar) || $config->$sortvar != $index + 1) {
                set_config($sortvar, $index + 1, 'question');
            }
        }
    }

    /**
     * Move one question type in a list of question types. If you try to move one element
     * off of the end, nothing will change.
     *
     * @param array $sortedqtypes An array $qtype => anything.
     * @param string $tomove one of the keys from $sortedqtypes
     * @param integer $direction +1 or -1
     * @return array an array $index => $qtype, with $index from 0 to n in order, and
     *      the $qtypes in the same order as $sortedqtypes, except that $tomove will
     *      have been moved one place.
     */
    public static function question_reorder_qtypes($sortedqtypes, $tomove, $direction): array {
        $neworder = array_keys($sortedqtypes);
        // Find the element to move.
        $key = array_search($tomove, $neworder);
        if ($key === false) {
            return $neworder;
        }
        // Work out the other index.
        $otherkey = $key + $direction;
        if (!isset($neworder[$otherkey])) {
            return $neworder;
        }
        // Do the swap.
        $swap = $neworder[$otherkey];
        $neworder[$otherkey] = $neworder[$key];
        $neworder[$key] = $swap;
        return $neworder;
    }

    /**
     * Check whether a given grade is one of a list of allowed options. If not,
     * depending on $matchgrades, either return the nearest match, or return false
     * to signal an error.
     *
     * @param array $gradeoptionsfull list of valid options
     * @param int $grade grade to be tested
     * @param string $matchgrades 'error' or 'nearest'
     * @return false|int|string either 'fixed' value or false if error.
     */
    public static function match_grade_options($gradeoptionsfull, $grade, $matchgrades = 'error') {

        if ($matchgrades === 'error') {
            // ...(Almost) exact match, or an error.
            foreach ($gradeoptionsfull as $value => $option) {
                // Slightly fuzzy test, never check floats for equality.
                if (abs($grade - $value) < 0.00001) {
                    return $value; // Be sure the return the proper value.
                }
            }
            // Didn't find a match so that's an error.
            return false;

        } else if ($matchgrades === 'nearest') {
            // Work out nearest value.
            $best = false;
            $bestmismatch = 2;
            foreach ($gradeoptionsfull as $value => $option) {
                $newmismatch = abs($grade - $value);
                if ($newmismatch < $bestmismatch) {
                    $best = $value;
                    $bestmismatch = $newmismatch;
                }
            }
            return $best;

        } else {
            // Unknow option passed.
            throw new \coding_exception('Unknown $matchgrades ' . $matchgrades .
                ' passed to match_grade_options');
        }
    }

}
