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
 * Manager class to handle deletion of questions and its associated operations.
 *
 * @package    core_question
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_question_manager {

    /**
     * Check if there is more versions left for the entry.
     * If not delete the entry.
     *
     * @param int $entryid
     */
    public static function delete_question_bank_entry($entryid): void {
        global $DB;
        if (!$DB->record_exists('question_versions', ['questionbankentryid' => $entryid])) {
            $DB->delete_records('question_bank_entries', ['id' => $entryid]);
        }
    }

    /**
     * Deletes question and all associated data from the database
     *
     * It will not delete a question if it is used somewhere, instead it will just delete the reference.
     *
     * @param \stdClass $questionid The id of the question being deleted
     */
    public static function question_delete_question($question): void {
        global $DB;
        // Delete previews of the question.
        $dm = new \question_engine_data_mapper();
        $dm->delete_previews($question->id);

        // Delete questiontype-specific data.
        \question_bank::get_qtype($question->qtype, false)->delete_question($question->id, $question->contextid);

        // Delete all tag instances.
        \core_tag_tag::remove_all_item_tags('core_question', 'question', $question->id);

        // Delete the custom filed data for the question.
        $customfieldhandler = \qbank_customfields\customfield\question_handler::create();
        $customfieldhandler->delete_instance($question->id);

        // Now recursively delete all child questions
        if ($children = $DB->get_records('question',
            array('parent' => $question->id), '', 'id, qtype')) {
            foreach ($children as $child) {
                if ($child->id != $question->id) {
                    self::question_delete_question($child->id);
                }
            }
        }

        // Delete question comments.
        $DB->delete_records('comments', ['itemid' => $question->id, 'component' => 'qbank_comment',
            'commentarea' => 'question']);
        // Finally delete the question record itself.
        $DB->delete_records('question', ['id' => $question->id]);
        $DB->delete_records('question_versions', ['id' => $question->versionid]);
        $DB->delete_records('question_references',
            [
                'version' => $question->version,
                'questionbankentryid' => $question->entryid,
            ]);
        \core_question\local\bank\delete_question_manager::delete_question_bank_entry($question->entryid);
        \question_bank::notify_question_edited($question->id);

        // Log the deletion of this question.
        $event = \core\event\question_deleted::create_from_question_instance($question);
        $event->add_record_snapshot('question', $question);
        $event->trigger();
    }

    /**
     * All question categories and their questions are deleted for this context id.
     *
     * @param int $contextid The contextid to delete question categories from
     * @return array only returns an empty array for backwards compatibility.
     */
    public static function question_delete_context($contextid): array {
        global $DB;

        $fields = 'id, parent, name, contextid';
        if ($categories = $DB->get_records('question_categories', ['contextid' => $contextid], 'parent', $fields)) {
            // Sort categories following their tree (parent-child) relationships this will make the feedback more readable.
            $categories = \core_question\question_categories_manager::sort_categories_by_tree($categories);
            foreach ($categories as $category) {
                \core_question\question_categories_manager::question_category_delete_safe($category);
            }
        }
        return [];
    }

    /**
     * Creates a new category to save the questions in use.
     *
     * @param array $questionids of question ids
     * @param int $newcontextid the context to create the saved category in.
     * @param string $oldplace a textual description of the think being deleted,
     *      e.g. from get_context_name
     * @param object $newcategory
     * @return mixed false on
     */
    public static function question_save_from_deletion($questionids, $newcontextid, $oldplace, $newcategory = null) {
        global $DB;

        // Make a category in the parent context to move the questions to.
        if (is_null($newcategory)) {
            $newcategory = new \stdClass();
            $newcategory->parent = \core_question\question_categories_manager::question_get_top_category($newcontextid, true)->id;
            $newcategory->contextid = $newcontextid;
            // Max length of column name in question_categories is 255.
            $newcategory->name = shorten_text(get_string('questionsrescuedfrom', 'question', $oldplace), 255);
            $newcategory->info = get_string('questionsrescuedfrominfo', 'question', $oldplace);
            $newcategory->sortorder = 999;
            $newcategory->stamp = make_unique_id_code();
            $newcategory->id = $DB->insert_record('question_categories', $newcategory);
        }

        // Move any remaining questions to the 'saved' category.
        if (!\core_question\question_categories_manager::question_move_questions_to_category($questionids, $newcategory->id)) {
            return false;
        }
        return $newcategory;
    }
}
