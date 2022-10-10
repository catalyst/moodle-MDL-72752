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

namespace core_question;

use core_question\local\bank\delete_question_manager;
use core_question\local\bank\question_options_manager;

/**
 * Manager class to handle question itself and its associated operations.
 *
 * @package    core_question
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_manager {

    /**
     * Check if the question is used.
     *
     * @param array $questionids of question ids.
     * @return boolean whether any of these questions are being used by any part of Moodle.
     */
    public static function questions_in_use(array $questionids): bool {

        // Are they used by the core question system?
        if (\question_engine::questions_in_use($questionids)) {
            return true;
        }

        // Check if any plugins are using these questions.
        $callbacksbytype = get_plugins_with_function('questions_in_use');
        foreach ($callbacksbytype as $callbacks) {
            foreach ($callbacks as $function) {
                if ($function($questionids)) {
                    return true;
                }
            }
        }

        // Finally check legacy callback.
        $legacycallbacks = get_plugin_list_with_function('mod', 'question_list_instances');
        foreach ($legacycallbacks as $plugin => $function) {
            debugging($plugin . ' implements deprecated method ' . $function .
                '. ' . $plugin . '_questions_in_use should be implemented instead.', DEBUG_DEVELOPER);

            if (isset($callbacksbytype['mod'][substr($plugin, 4)])) {
                continue; // Already done.
            }

            foreach ($questionids as $questionid) {
                if (!empty($function($questionid))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determine whether there are any questions belonging to this context, that is whether any of its
     * question categories contain any questions. This will return true even if all the questions are
     * hidden.
     *
     * @param mixed $context either a context object, or a context id.
     * @return boolean whether any of the question categories beloning to this context have
     *         any questions in them.
     */
    public static function question_context_has_any_questions($context): bool {
        global $DB;
        if (is_object($context)) {
            $contextid = $context->id;
        } else if (is_numeric($context)) {
            $contextid = $context;
        } else {
            throw new \moodle_exception('invalidcontextinhasanyquestions', 'question');
        }
        $sql = 'SELECT qbe.*
                  FROM {question_bank_entries} qbe
                  JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
                 WHERE qc.contextid = ?';
        return $DB->record_exists_sql($sql, [$contextid]);
    }

    /**
     * Load a set of questions, given a list of ids. The $join and $extrafields arguments can be used
     * together to pull in extra data. See, for example, the usage in mod/quiz/attempt.php, and
     * read the code below to see how the SQL is assembled. Throws exceptions on error.
     *
     * @param array $questionids array of question ids.
     * @param string $extrafields extra SQL code to be added to the query.
     * @param string $join extra SQL code to be added to the query.
     * @return array|string question objects.
     */
    public static function question_load_questions($questionids, $extrafields = '', $join = '') {
        $questions = question_options_manager::question_preload_questions($questionids, $extrafields, $join);

        // Load the question type specific information.
        if (!self::get_question_options($questions)) {
            return get_string('questionloaderror', 'question');
        }

        return $questions;
    }

    /**
     * Delete a question and its associated data.
     *
     * @param $questionid
     */
    public static function delete_question($questionid) {
        global $DB;

        $question = $DB->get_record('question', ['id' => $questionid]);
        if (!$question) {
            // In some situations, for example if this was a child of a
            // Cloze question that was previously deleted, the question may already
            // have gone. In this case, just do nothing.
            return;
        }

        $sql = 'SELECT qv.id as versionid,
                       qv.version,
                       qbe.id as entryid,
                       qc.id as categoryid,
                       qc.contextid as contextid
                  FROM {question} q
                  LEFT JOIN {question_versions} qv ON qv.questionid = q.id
                  LEFT JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                  LEFT JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
                 WHERE q.id = ?';
        $questiondata = $DB->get_record_sql($sql, [$question->id]);

        $questionstocheck = [$question->id];

        if ($question->parent) {
            $questionstocheck[] = $question->parent;
        }

        // Do not delete a question if it is used by an activity module
        if (\core_question\question_manager::questions_in_use($questionstocheck)) {
            return;
        }

        $question->versionid = $questiondata->versionid;
        $question->version = $questiondata->version;
        $question->entryid = $questiondata->entryid;

        // This sometimes happens in old sites with bad data.
        if (!$questiondata->contextid) {
            debugging('Deleting question ' . $question->id . ' which is no longer linked to a context. ' .
                'Assuming system context to avoid errors, but this may mean that some data like files, ' .
                'tags, are not cleaned up.');
            $question->contextid = \context_system::instance()->id;
            $question->category = 0;
        } else {
            $question->contextid = $questiondata->contextid;
            $question->category = $questiondata->categoryid;
        }

        delete_question_manager::question_delete_question($question);
    }

    /**
     * All question categories and their questions are deleted for this activity.
     *
     * @param object $cm the course module object representing the activity
     * @param bool $notused the argument is not used any more. Kept for backwards compatibility.
     * @return boolean
     */
    public static function question_delete_activity($cm, $notused = false): bool {
        $modcontext = \context_module::instance($cm->id);
        \core_question\local\bank\delete_question_manager::question_delete_context($modcontext->id);
        return true;
    }

    /**
     * If $oldidnumber ends in some digits then return the next available idnumber of the same form.
     *
     * So idnum -> null (no digits at the end) idnum0099 -> idnum0100 (if that is unused,
     * else whichever of idnum0101, idnume0102, ... is unused. idnum9 -> idnum10.
     *
     * @param string|null $oldidnumber a question idnumber, or can be null.
     * @param int $categoryid a question category id.
     * @return string|null suggested new idnumber for a question in that category, or null if one cannot be found.
     */
    public static function core_question_find_next_unused_idnumber(?string $oldidnumber, int $categoryid): ?string {
        global $DB;

        // The old idnumber is not of the right form, bail now.
        if (!preg_match('~\d+$~', $oldidnumber, $matches)) {
            return null;
        }

        // Find all used idnumbers in one DB query.
        $usedidnumbers = $DB->get_records_select_menu('question_bank_entries', 'questioncategoryid = ? AND idnumber IS NOT NULL',
            [$categoryid], '', 'idnumber, 1');

        // Find the next unused idnumber.
        $numberbit = 'X' . $matches[0]; // Need a string here so PHP does not do '0001' + 1 = 2.
        $stem = substr($oldidnumber, 0, -strlen($matches[0]));
        do {

            // If we have got to something9999, insert an extra digit before incrementing.
            if (preg_match('~^(.*[^0-9])(9+)$~', $numberbit, $matches)) {
                $numberbit = $matches[1] . '0' . $matches[2];
            }
            $numberbit++;
            $newidnumber = $stem . substr($numberbit, 1);
        } while (isset($usedidnumbers[$newidnumber]));

        return $newidnumber;
    }

    /**
     * Get the question_bank_entry object given a question id.
     *
     * @param int $questionid Question id.
     * @return false|mixed
     */
    public static function get_question_bank_entry(int $questionid): \stdClass {
        global $DB;

        $sql = "SELECT qbe.*
                  FROM {question} q
                  JOIN {question_versions} qv ON qv.questionid = q.id
                  JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                 WHERE q.id = :id";

        return $DB->get_record_sql($sql, ['id' => $questionid]);
    }

    /**
     * Get the question versions given a question id in a descending sort .
     *
     * @param int $questionid Question id.
     * @return array
     */
    public static function get_question_version($questionid): array {
        global $DB;

        $version = $DB->get_records('question_versions', ['questionid' => $questionid]);
        krsort($version);

        return $version;
    }

    /**
     * Get the next version number to create base on a Question bank entry id.
     *
     * @param int $questionbankentryid Question bank entry id.
     * @return int next version number.
     */
    public static function get_next_version(int $questionbankentryid): int {
        global $DB;

        $sql = "SELECT MAX(qv.version)
                  FROM {question_versions} qv
                  JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                 WHERE qbe.id = :id";

        $nextversion = $DB->get_field_sql($sql, ['id' => $questionbankentryid]);

        if ($nextversion) {
            return (int)$nextversion + 1;
        }

        return 1;
    }

    /**
     * Checks if question is the latest version.
     *
     * @param string $version Question version to check.
     * @param string $questionbankentryid Entry to check against.
     * @return bool
     */
    public static function is_latest(string $version, string $questionbankentryid) : bool {
        global $DB;

        $sql = 'SELECT MAX(version) AS max
                  FROM {question_versions}
                 WHERE questionbankentryid = ?';
        $latestversion = $DB->get_record_sql($sql, [$questionbankentryid]);

        if (isset($latestversion->max)) {
            return ($version === $latestversion->max) ? true : false;
        }
        return false;
    }

    /**
     * Updates the question objects with question type specific
     * information by calling {@see self::get_question_options()}
     *
     * Can be called either with an array of question objects or with a single
     * question object.
     *
     * @param mixed $questions Either an array of question objects to be updated
     *         or just a single question object
     * @param bool $loadtags load the question tags from the tags table. Optional, default false.
     * @param \stdClass[] $filtercourses The courses to filter the course tags by.
     * @return bool Indicates success or failure.
     */
    public static function get_question_options(&$questions, $loadtags = false, $filtercourses = null) {
        global $DB;

        $questionlist = is_array($questions) ? $questions : [$questions];
        $categoryids = [];
        $questionids = [];

        if (empty($questionlist)) {
            return true;
        }

        foreach ($questionlist as $question) {
            $questionids[] = $question->id;
            if (isset($question->category)) {
                $qcategoryid = $question->category;
            } else {
                $qcategoryid = self::get_question_bank_entry($question->id)->questioncategoryid;
                $question->questioncategoryid = $qcategoryid;
            }

            if (!in_array($qcategoryid, $categoryids)) {
                $categoryids[] = $qcategoryid;
            }
        }

        $categories = $DB->get_records_list('question_categories', 'id', $categoryids);

        if ($loadtags && \core_tag_tag::is_enabled('core_question', 'question')) {
            $tagobjectsbyquestion = \core_tag_tag::get_items_tags('core_question', 'question', $questionids);
        } else {
            $tagobjectsbyquestion = null;
        }

        foreach ($questionlist as $question) {
            if (is_null($tagobjectsbyquestion)) {
                $tagobjects = null;
            } else {
                $tagobjects = $tagobjectsbyquestion[$question->id];
            }
            $qcategoryid = $question->category ?? $question->questioncategoryid ??
                self::get_question_bank_entry($question->id)->questioncategoryid;

            question_options_manager::_tidy_question($question, $categories[$qcategoryid], $tagobjects, $filtercourses);
        }

        return true;
    }

    /**
     * Print the icon for the question type
     *
     * @param object $question The question object for which the icon is required.
     *       Only $question->qtype is used.
     * @return string the HTML for the img tag.
     */
    public static function print_question_icon($question): string {
        global $PAGE;

        if (is_object($question->qtype)) {
            $qtype = $question->qtype->name();
        } else {
            // Assume string.
            $qtype = $question->qtype;
        }

        return $PAGE->get_renderer('question', 'bank')->qtype_icon($qtype);
    }

}
