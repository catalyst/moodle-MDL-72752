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

/**
 * Manager class to handle question categories and its associated operations.
 *
 * @package    core_question
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_categories_manager {

    /**
     * Returns the categories with their names ordered following parent-child relationships.
     * finally, it tries to return pending categories (those being orphaned, whose parent is
     * incorrect) to avoid missing any category from original array.
     *
     * @param array $categories
     * @param int $id
     * @param int $level
     * @return array
     */
    public static function sort_categories_by_tree(array &$categories, int $id = 0, int $level = 1): array {
        global $DB;

        $children = [];
        $keys = array_keys($categories);

        foreach ($keys as $key) {
            if (!isset($categories[$key]->processed) && $categories[$key]->parent == $id) {
                $children[$key] = $categories[$key];
                $categories[$key]->processed = true;
                $children += self::sort_categories_by_tree($categories, $children[$key]->id, $level + 1);
            }
        }
        // If level = 1, we have finished, try to look for non processed categories (bad parent) and sort them too.
        if ($level === 1) {
            foreach ($keys as $key) {
                // If not processed and it's a good candidate to start (because its parent doesn't exist in the course).
                if (!isset($categories[$key]->processed) && !$DB->record_exists('question_categories',
                        array('contextid' => $categories[$key]->contextid,
                            'id' => $categories[$key]->parent))) {
                    $children[$key] = $categories[$key];
                    $categories[$key]->processed = true;
                    $children += self::sort_categories_by_tree($categories, $children[$key]->id, $level + 1);
                }
            }
        }
        return $children;
    }

    /**
     * Get the default category for the context.
     *
     * @param integer $contextid a context id.
     * @return object|bool the default question category for that context, or false if none.
     */
    public static function question_get_default_category(int $contextid) {
        global $DB;
        $category = $DB->get_records_select('question_categories', 'contextid = ? AND parent <> 0',
            [$contextid], 'id', '*', 0, 1);
        if (!empty($category)) {
            return reset($category);
        }

        return false;
    }

    /**
     * Gets the top category in the given context.
     * This function can optionally create the top category if it doesn't exist.
     *
     * @param int $contextid A context id.
     * @param bool $create Whether create a top category if it doesn't exist.
     * @return bool|\stdClass The top question category for that context, or false if none.
     */
    public static function question_get_top_category(int $contextid, bool $create = false) {
        global $DB;
        $category = $DB->get_record('question_categories', ['contextid' => $contextid, 'parent' => 0]);

        if (!$category && $create) {
            // We need to make one.
            $category = new \stdClass();
            $category->name = 'top'; // A non-real name for the top category. It will be localised at the display time.
            $category->info = '';
            $category->contextid = $contextid;
            $category->parent = 0;
            $category->sortorder = 0;
            $category->stamp = make_unique_id_code();
            $category->id = $DB->insert_record('question_categories', $category);
        }

        return $category;
    }

    /**
     * Gets the list of top categories in the given contexts in the array("categoryid,categorycontextid") format.
     *
     * @param array $contextids List of context ids
     * @return array
     */
    public static function question_get_top_categories_for_contexts(array $contextids): array {
        global $DB;

        $concatsql = $DB->sql_concat_join("','", ['id', 'contextid']);
        list($insql, $params) = $DB->get_in_or_equal($contextids);
        $sql = "SELECT $concatsql
                  FROM {question_categories}
                 WHERE contextid $insql
                   AND parent = 0";

        return $DB->get_fieldset_sql($sql, $params);
    }

    /**
     * Gets the default category in the most specific context.
     * If no categories exist yet then default ones are created in all contexts.
     *
     * @param array $contexts  The context objects for this context and all parent contexts.
     * @return \stdClass The default category - the category in the course context
     */
    public static function question_make_default_categories(array $contexts): \stdClass {
        global $DB;
        static $preferredlevels = [
            CONTEXT_COURSE => 4,
            CONTEXT_MODULE => 3,
            CONTEXT_COURSECAT => 2,
            CONTEXT_SYSTEM => 1,
        ];

        $toreturn = null;
        $preferredness = 0;
        // If it already exists, just return it.
        foreach ($contexts as $key => $context) {
            $topcategory = self::question_get_top_category($context->id, true);
            if (!$exists = $DB->record_exists("question_categories",
                array('contextid' => $context->id, 'parent' => $topcategory->id))) {
                // Otherwise, we need to make one.
                $category = new \stdClass();
                $contextname = $context->get_context_name(false, true);
                // Max length of name field is 255.
                $category->name = shorten_text(get_string('defaultfor', 'question', $contextname), 255);
                $category->info = get_string('defaultinfofor', 'question', $contextname);
                $category->contextid = $context->id;
                $category->parent = $topcategory->id;
                // By default, all categories get this number, and are sorted alphabetically.
                $category->sortorder = 999;
                $category->stamp = make_unique_id_code();
                $category->id = $DB->insert_record('question_categories', $category);
            } else {
                $category = self::question_get_default_category($context->id);
            }
            $thispreferredness = $preferredlevels[$context->contextlevel];
            if (has_any_capability(['moodle/question:usemine', 'moodle/question:useall'], $context)) {
                $thispreferredness += 10;
            }
            if ($thispreferredness > $preferredness) {
                $toreturn = $category;
                $preferredness = $thispreferredness;
            }
        }

        if (!is_null($toreturn)) {
            $toreturn = clone($toreturn);
        }
        return $toreturn;
    }

    /**
     * Get the list of categories.
     *
     * @param int $categoryid
     * @return array of question category ids of the category and all subcategories.
     */
    public static function question_categorylist(int $categoryid): array {
        global $DB;

        // Final list of category IDs.
        $categorylist = [];

        // A list of category IDs to check for any sub-categories.
        $subcategories = [$categoryid];

        while ($subcategories) {
            foreach ($subcategories as $subcategory) {
                // If anything from the temporary list was added already, then we have a loop.
                if (isset($categorylist[$subcategory])) {
                    throw new \coding_exception("Category id=$subcategory is already on the list - loop of categories detected.");
                }
                $categorylist[$subcategory] = $subcategory;
            }

            list ($in, $params) = $DB->get_in_or_equal($subcategories);

            $subcategories = $DB->get_records_select_menu('question_categories', "parent $in", $params,
                null, 'id,id AS id2');
        }

        return $categorylist;
    }

    /**
     * Get all parent categories of a given question category in decending order.
     *
     * @param int $categoryid for which you want to find the parents.
     * @return array of question category ids of all parents categories.
     */
    public static function question_categorylist_parents(int $categoryid): array {
        global $DB;
        $parent = $DB->get_field('question_categories', 'parent', ['id' => $categoryid]);
        if (!$parent) {
            return [];
        }
        $categorylist = [$parent];
        $currentid = $parent;
        while ($currentid) {
            $currentid = $DB->get_field('question_categories', 'parent', ['id' => $currentid]);
            if ($currentid) {
                $categorylist[] = $currentid;
            }
        }
        // Present the list in decending order (the top category at the top).
        $categorylist = array_reverse($categorylist);
        return $categorylist;
    }

    /**
     * This function helps move a question cateogry to a new context by moving all
     * the files belonging to all the questions to the new context.
     * Also moves subcategories.
     *
     * @param integer $categoryid the id of the category being moved.
     * @param integer $oldcontextid the old context id.
     * @param integer $newcontextid the new context id.
     * @param bool $purgecache if calling this function will purge question from the cache or not.
     */
    public static function question_move_category_to_context($categoryid, $oldcontextid, $newcontextid, $purgecache = true): void {
        global $DB;

        $questions = [];
        $sql = "SELECT q.id, q.qtype
                  FROM {question} q
                  JOIN {question_versions} qv ON qv.questionid = q.id
                  JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                 WHERE qbe.questioncategoryid = ?";

        $questionids = $DB->get_records_sql_menu($sql, [$categoryid]);
        foreach ($questionids as $questionid => $qtype) {
            \question_bank::get_qtype($qtype)->move_files(
                $questionid, $oldcontextid, $newcontextid);
            if ($purgecache) {
                // Purge this question from the cache.
                \question_bank::notify_question_edited($questionid);
            }
            $questions[] = (object) [
                'id' => $questionid,
                'contextid' => $oldcontextid
            ];
        }

        $newcontext = \context::instance_by_id($newcontextid);
        question_move_question_tags_to_new_context($questions, $newcontext);

        $subcatids = $DB->get_records_menu('question_categories', ['parent' => $categoryid], '', 'id,1');
        foreach ($subcatids as $subcatid => $notused) {
            $DB->set_field('question_categories', 'contextid', $newcontextid, ['id' => $subcatid]);
            self::question_move_category_to_context($subcatid, $oldcontextid, $newcontextid, $purgecache);
        }
    }

    /**
     * This function should be considered private to the question bank, it is called from
     * question/editlib.php question/contextmoveq.php and a few similar places to to the
     * work of actually moving questions and associated data. However, callers of this
     * function also have to do other work, which is why you should not call this method
     * directly from outside the questionbank.
     *
     * @param array $questionids of question ids.
     * @param integer $newcategoryid the id of the category to move to.
     * @return bool
     */
    public static function question_move_questions_to_category($questionids, $newcategoryid): bool {
        global $DB;

        $newcategorydata = $DB->get_record('question_categories', ['id' => $newcategoryid]);
        if (!$newcategorydata) {
            return false;
        }
        list($questionidcondition, $params) = $DB->get_in_or_equal($questionids);

        $sql = "SELECT qv.id as versionid,
                       qbe.id as entryid,
                       qc.id as category,
                       qc.contextid as contextid,
                       q.id,
                       q.qtype,
                       qbe.idnumber
                  FROM {question} q
                  JOIN {question_versions} qv ON qv.questionid = q.id
                  JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                  JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
                 WHERE q.id $questionidcondition
                       OR (q.parent <> 0 AND q.parent $questionidcondition)";

        // Also, we need to move children questions.
        $params = array_merge($params, $params);
        $questions = $DB->get_records_sql($sql, $params);
        foreach ($questions as $question) {
            if ($newcategorydata->contextid != $question->contextid) {
                \question_bank::get_qtype($question->qtype)->move_files(
                    $question->id, $question->contextid, $newcategorydata->contextid);
            }
            // Check whether there could be a clash of idnumbers in the new category.
            list($idnumberclash, $rec) = self::idnumber_exist_in_question_category($question->idnumber, $newcategoryid);
            if ($idnumberclash) {
                $unique = 1;
                if (count($rec)) {
                    $rec = reset($rec);
                    $idnumber = $rec->idnumber;
                    if (strpos($idnumber, '_') !== false) {
                        $unique = substr($idnumber, strpos($idnumber, '_') + 1) + 1;
                    }
                }
                // For the move process, add a numerical increment to the idnumber. This means that if a question is
                // mistakenly moved then the idnumber will not be completely lost.
                $qbankentry = new \stdClass();
                $qbankentry->id = $question->entryid;
                $qbankentry->idnumber = $question->idnumber . '_' . $unique;
                $DB->update_record('question_bank_entries', $qbankentry);
            }

            // Update the entry to the new category id.
            $entry = new \stdClass();
            $entry->id = $question->entryid;
            $entry->questioncategoryid = $newcategorydata->id;
            $DB->update_record('question_bank_entries', $entry);

            // Log this question move.
            $event = \core\event\question_moved::create_from_question_instance($question, \context::instance_by_id($question->contextid),
                ['oldcategoryid' => $question->category, 'newcategoryid' => $newcategorydata->id]);
            $event->trigger();
        }

        $newcontext = \context::instance_by_id($newcategorydata->contextid);
        question_move_question_tags_to_new_context($questions, $newcontext);

        // Purge these questions from the cache.
        foreach ($questions as $question) {
            \question_bank::notify_question_edited($question->id);
        }

        return true;
    }

    /**
     * Check if an idnumber exist in the category.
     *
     * @param int $questionidnumber
     * @param int $categoryid
     * @param int $limitfrom
     * @param int $limitnum
     * @return array
     */
    public static function idnumber_exist_in_question_category($questionidnumber, $categoryid, $limitfrom = 0, $limitnum = 1): array {
        global $DB;
        $response  = false;
        $record = [];
        // Check if the idnumber exist in the category.
        $sql = 'SELECT qbe.idnumber
                  FROM {question_bank_entries} qbe
                 WHERE qbe.idnumber LIKE ?
                   AND qbe.questioncategoryid = ?
              ORDER BY qbe.idnumber DESC';
        $questionrecord = $DB->record_exists_sql($sql, [$questionidnumber, $categoryid]);
        if ((string) $questionidnumber !== '' && $questionrecord) {
            $record = $DB->get_records_sql($sql, [$questionidnumber . '_%', $categoryid], 0, 1);
            $response  = true;
        }

        return [$response, $record];
    }

    /**
     * Tests whether any question in a category is used by any part of Moodle.
     *
     * @param integer $categoryid a question category id.
     * @param boolean $recursive whether to check child categories too.
     * @return boolean whether any question in this category is in use.
     */
    public static function question_category_in_use($categoryid, $recursive = false): bool {
        global $DB;

        // Look at each question in the category.
        $questionids = \question_bank::get_finder()->get_questions_from_categories([$categoryid], null);
        if ($questionids) {
            if (questions_in_use(array_keys($questionids))) {
                return true;
            }
        }
        if (!$recursive) {
            return false;
        }

        // Look under child categories recursively.
        if ($children = $DB->get_records('question_categories',
            ['parent' => $categoryid], '', 'id, 1')) {
            foreach ($children as $child) {
                if (self::question_category_in_use($child->id, $recursive)) {
                    return true;
                }
            }
        }

        return false;
    }

}
