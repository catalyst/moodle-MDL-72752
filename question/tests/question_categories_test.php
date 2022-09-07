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

use context_module;
use core_course_category;
use core_question\local\bank\question_edit_contexts;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Testing method question categories actions in a module and course context.
 *
 * @package    core_question
 * @category   test
 * @copyright  2021 Catalyst IT Australia Ltd
 * @author     Guillermo Gomez Arias <guillermogomez@catalyst-ca.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \core_question\question_categories_manager
 */
class question_categories_test extends \advanced_testcase {

    /**
     * @var stdClass Course used in the tests.
     */
    protected $course;

    /**
     * @var stdClass mod_qbank used in the tests.
     */
    protected $modqbank;

    /**
     * @var context_module used in the tests.
     */
    protected $modcontext;

    /**
     * Setup test data
     *
     */
    protected function setUp(): void {
        parent::setUp();
        self::setAdminUser();
        $this->resetAfterTest();

        // Create a course and question bank activity.
        $this->course = $this->getDataGenerator()->create_course();
        $this->modqbank = $this->getDataGenerator()->create_module('qbank', ['course' => $this->course->id]);
        $this->modcontext = context_module::instance($this->modqbank->cmid);
        $contexts = new question_edit_contexts($this->modcontext);
        \core_question\question_categories_manager::question_make_default_categories($contexts->all());
    }

    /**
     * Setup a course, a quiz, a question category and a question for testing.
     *
     * @return array The created data objects
     */
    public function setup_quiz_and_questions() {
        // Create course category.
        $category = $this->getDataGenerator()->create_category();

        // Create course.
        $course = $this->getDataGenerator()->create_course(array(
            'numsections' => 5,
            'category' => $category->id
        ));

        $options = array(
            'course' => $course->id,
            'duedate' => time(),
        );

        // Generate an assignment with due date (will generate a course event).
        $quiz = $this->getDataGenerator()->create_module('quiz', $options);

        $qgen = $this->getDataGenerator()->get_plugin_generator('core_question');

        $context = context_module::instance($quiz->cmid);

        $qcat = $qgen->create_question_category(array('contextid' => $context->id));

        $questions = array(
            $qgen->create_question('shortanswer', null, array('category' => $qcat->id)),
            $qgen->create_question('shortanswer', null, array('category' => $qcat->id)),
        );

        quiz_add_quiz_question($questions[0]->id, $quiz);

        return array($category, $course, $quiz, $qcat, $questions);
    }

    /**
     * Test action in a module context.
     */
    public function test_question_categories_actions_module_context() {
        global $DB, $USER;

        // Test that the question category has been created.
        $qcategory = $DB->count_records_select('question_categories', 'contextid = ? AND parent <> 0',
            [$this->modcontext->id]);
        $this->assertEquals(1, $qcategory);

        // Test that the question category has been removed.
        $qbankcm = get_coursemodule_from_id('qbank', $this->modqbank->cmid);
        // Execute the task.
        $removaltask = new \core_course\task\course_delete_modules();
        $data = [
            'cms' => [$qbankcm],
            'userid' => $USER->id,
            'realuserid' => $USER->id
        ];
        $removaltask->set_custom_data($data);
        $removaltask->execute();
        $qcategory = $DB->count_records_select('question_categories', 'contextid = ? AND parent <> 0',
            [$this->modcontext->id]);
        $this->assertEquals(0, $qcategory);
    }

    /**
     * Test action in a course context.
     */
    public function test_question_categories_actions_course_context() {
        global $DB;

        // Test that the question category has been created.
        $qcategory = $DB->count_records_select('question_categories', 'contextid = ? AND parent <> 0',
            [$this->modcontext->id]);
        $this->assertEquals(1, $qcategory);

        // Test that the question category has been removed.
        delete_course($this->course, false);
        $qcategory = $DB->count_records_select('question_categories', 'contextid = ? AND parent <> 0',
            [$this->modcontext->id]);
        $this->assertEquals(0, $qcategory);
    }

    /**
     * Test action in a category context.
     */
    public function test_question_categories_actions_category_context() {
        global $DB;

        // Test that the question category has been created.
        $qcategory = $DB->count_records_select('question_categories', 'contextid = ? AND parent <> 0',
            [$this->modcontext->id]);
        $this->assertEquals(1, $qcategory);

        // Test that the question category has been removed.
        $category = core_course_category::get($this->course->category);
        $category->delete_full(true);
        $qcategory = $DB->count_records_select('question_categories', 'contextid = ? AND parent <> 0',
            [$this->modcontext->id]);
        $this->assertEquals(0, $qcategory);
    }

    /**
     * Test of question_categorylist_parents function.
     * movement_maked_safat
     */
    public function test_question_categorylist_parents() {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $questiongenerator = $generator->get_plugin_generator('core_question');
        $category = $generator->create_category();
        $context = \context_coursecat::instance($category->id);
        // Create a top category.
        $cat0 = \core_question\question_categories_manager::question_get_top_category($context->id, true);
        // Add sub-categories.
        $cat1 = $questiongenerator->create_question_category(['parent' => $cat0->id]);
        $cat2 = $questiongenerator->create_question_category(['parent' => $cat1->id]);
        // Test the 'get parents' function.
        $parentcategories = \core_question\question_categories_manager::question_categorylist_parents($cat2->id);
        $this->assertEquals($cat0->id, $parentcategories[0]);
        $this->assertEquals($cat1->id, $parentcategories[1]);
        $this->assertCount(2, $parentcategories);
    }

    /**
     * Tests for the question_move_questions_to_category function.
     *
     * @covers ::question_move_questions_to_category
     */
    public function test_question_move_questions_to_category() {
        $this->resetAfterTest();

        // Create the test data.
        list($category1, $course1, $quiz1, $questioncat1, $questions1) = $this->setup_quiz_and_questions();
        list($category2, $course2, $quiz2, $questioncat2, $questions2) = $this->setup_quiz_and_questions();

        $this->assertCount(2, $questions1);
        $this->assertCount(2, $questions2);
        $questionsidtomove = [];
        foreach ($questions1 as $question1) {
            $questionsidtomove[] = $question1->id;
        }

        // Move the question from quiz 1 to quiz 2.
        \core_question\question_categories_manager::question_move_questions_to_category($questionsidtomove, $questioncat2->id);
        $this->assert_category_contains_questions($questioncat2->id, 4);
    }

    /**
     * Tests for the idnumber_exist_in_question_category function.
     *
     * @covers ::idnumber_exist_in_question_category
     */
    public function test_idnumber_exist_in_question_category() {
        global $DB;

        $this->resetAfterTest();

        // Create the test data.
        list($category1, $course1, $quiz1, $questioncat1, $questions1) = $this->setup_quiz_and_questions();
        list($category2, $course2, $quiz2, $questioncat2, $questions2) = $this->setup_quiz_and_questions();

        $questionbankentry1 = get_question_bank_entry($questions1[0]->id);
        $entry = new stdClass();
        $entry->id = $questionbankentry1->id;
        $entry->idnumber = 1;
        $DB->update_record('question_bank_entries', $entry);

        $questionbankentry2 = get_question_bank_entry($questions2[0]->id);
        $entry2 = new stdClass();
        $entry2->id = $questionbankentry2->id;
        $entry2->idnumber = 1;
        $DB->update_record('question_bank_entries', $entry2);

        $questionbe = $DB->get_record('question_bank_entries', ['id' => $questionbankentry1->id]);

        // Validate that a first stage of an idnumber exists (this format: xxxx_x).
        list($response, $record) = \core_question\question_categories_manager::
        idnumber_exist_in_question_category($questionbe->idnumber, $questioncat1->id);
        $this->assertEquals([], $record);
        $this->assertEquals(true, $response);

        // Move the question to a category that has a question with the same idnumber.
        \core_question\question_categories_manager::
        question_move_questions_to_category($questions1[0]->id, $questioncat2->id);

        // Validate that function return the last record used for the idnumber.
        list($response, $record) = \core_question\question_categories_manager::
        idnumber_exist_in_question_category($questionbe->idnumber, $questioncat2->id);
        $record = reset($record);
        $idnumber = $record->idnumber;
        $this->assertEquals($idnumber, '1_1');
        $this->assertEquals(true, $response);
    }

}
