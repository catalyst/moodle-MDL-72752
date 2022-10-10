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
 * Question manager unit tests.
 *
 * @package    core_question
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \core_question\local\bank\delete_question_manager
 * @coversDefaultClass \core_question\question_manager
 */
class question_manager_test extends \advanced_testcase {

    /**
     * Setup a course, a quiz, a question category and a question for testing.
     *
     * @param string $type The type of question category to create.
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

        $context = \context_module::instance($quiz->cmid);

        $qcat = $qgen->create_question_category(array('contextid' => $context->id));

        $questions = array(
            $qgen->create_question('shortanswer', null, array('category' => $qcat->id)),
            $qgen->create_question('shortanswer', null, array('category' => $qcat->id)),
        );

        quiz_add_quiz_question($questions[0]->id, $quiz);

        return array($category, $course, $quiz, $qcat, $questions);
    }

    /**
     * Assert that a category contains a specific number of questions.
     *
     * @param int $categoryid int Category id.
     * @param int $numberofquestions Number of question in a category.
     * @return void Questions in a category.
     */
    protected function assert_category_contains_questions(int $categoryid, int $numberofquestions): void {
        $questionsid = \question_bank::get_finder()->get_questions_from_categories([$categoryid], null);
        $this->assertEquals($numberofquestions, count($questionsid));
    }

    /**
     * Test question bank entry deletion.
     *
     * @covers ::delete_question_bank_entry
     */
    public function test_delete_question_bank_entry() {
        global $DB;
        $this->resetAfterTest();
        // Setup.
        $context = \context_system::instance();
        $qgen = $this->getDataGenerator()->get_plugin_generator('core_question');
        $qcat = $qgen->create_question_category(array('contextid' => $context->id));
        $q1 = $qgen->create_question('shortanswer', null, array('category' => $qcat->id));
        // Make sure there is an entry in the entry table.
        $sql = 'SELECT qbe.id as id,
                       qv.id as versionid
                  FROM {question_bank_entries} qbe
                  JOIN {question_versions} qv
                    ON qbe.id = qv.questionbankentryid
                  JOIN {question} q
                    ON qv.questionid = q.id
                 WHERE q.id = ?';
        $records = $DB->get_records_sql($sql, [$q1->id]);
        $this->assertCount(1, $records);
        // Delete the record.
        $record = reset($records);
        \core_question\local\bank\delete_question_manager::delete_question_bank_entry($record->id);
        $records = $DB->get_records('question_bank_entries', ['id' => $record->id]);
        // As the version record exists, it wont delete the data to resolve any errors.
        $this->assertCount(1, $records);
        $DB->delete_records('question_versions', ['id' => $record->versionid]);
        \core_question\local\bank\delete_question_manager::delete_question_bank_entry($record->id);
        $records = $DB->get_records('question_bank_entries', ['id' => $record->id]);
        $this->assertCount(0, $records);
    }

    /**
     * Test that deleting a question from the question bank works in the normal case.
     *
     * @covers ::question_delete_question
     * @covers ::delete_question
     */
    public function test_question_delete_question() {
        global $DB;
        $this->resetAfterTest();
        // Setup.
        $context = \context_system::instance();
        $qgen = $this->getDataGenerator()->get_plugin_generator('core_question');
        $qcat = $qgen->create_question_category(array('contextid' => $context->id));
        $q1 = $qgen->create_question('shortanswer', null, array('category' => $qcat->id));
        $q2 = $qgen->create_question('shortanswer', null, array('category' => $qcat->id));

        // Do.
        \core_question\question_manager::delete_question($q1->id);

        // Verify.
        $this->assertFalse($DB->record_exists('question', ['id' => $q1->id]));
        // Check that we did not delete too much.
        $this->assertTrue($DB->record_exists('question', ['id' => $q2->id]));
    }

    /**
     * Test that deleting a broken question from the question bank does not cause fatal errors.
     */
    public function test_question_delete_question_broken_data() {
        global $DB;
        $this->resetAfterTest();
        // Setup.
        $context = \context_system::instance();
        $qgen = $this->getDataGenerator()->get_plugin_generator('core_question');
        $qcat = $qgen->create_question_category(array('contextid' => $context->id));
        $q1 = $qgen->create_question('shortanswer', null, array('category' => $qcat->id));

        // Now delete the category, to simulate what happens in old sites where
        // referential integrity has failed.
        $DB->delete_records('question_categories', ['id' => $qcat->id]);

        // Do.
        \core_question\question_manager::delete_question($q1->id);

        // Verify.
        $this->assertDebuggingCalled('Deleting question ' . $q1->id .
            ' which is no longer linked to a context. Assuming system context ' .
            'to avoid errors, but this may mean that some data like ' .
            'files, tags, are not cleaned up.');
        $this->assertFalse($DB->record_exists('question', ['id' => $q1->id]));
    }

    /**
     * This function tests the question_delete_context function.
     */
    public function test_question_delete_context() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        list($category, $course, $quiz, $qcat, $questions) = $this->setup_quiz_and_questions();

        // Get the module context id.
        $result = \core_question\local\bank\delete_question_manager::question_delete_context($qcat->contextid);

        // Verify category deleted.
        $criteria = array('id' => $qcat->id);
        $this->assertEquals(0, $DB->count_records('question_categories', $criteria));

        // Verify questions deleted or moved.
        $this->assert_category_contains_questions($qcat->id, 0);
    }

    /**
     * Get test cases for test_core_question_find_next_unused_idnumber.
     *
     * @return array test cases.
     */
    public function find_next_unused_idnumber_cases(): array {
        return [
            ['id', null],
            ['id1a', null],
            ['id001', 'id002'],
            ['id9', 'id10'],
            ['id009', 'id010'],
            ['id999', 'id1000'],
            ['0', '1'],
            ['-1', '-2'],
            ['01', '02'],
            ['09', '10'],
            ['1.0E+29', '1.0E+30'], // Idnumbers are strings, not floats.
            ['1.0E-29', '1.0E-30'], // By the way, this is not a sensible idnumber!
            ['10.1', '10.2'],
            ['10.9', '10.10'],

        ];
    }

    /**
     * Test core_question_find_next_unused_idnumber in the case when there are no other questions.
     *
     * @dataProvider find_next_unused_idnumber_cases
     * @param string $oldidnumber value to pass to core_question_find_next_unused_idnumber.
     * @param string|null $expectednewidnumber expected result.
     * @covers ::core_question_find_next_unused_idnumber
     */
    public function test_core_question_find_next_unused_idnumber(string $oldidnumber, ?string $expectednewidnumber) {
        $this->assertSame($expectednewidnumber, \core_question\question_manager::
        core_question_find_next_unused_idnumber($oldidnumber, 0));
    }

    public function test_core_question_find_next_unused_idnumber_skips_used() {
        $this->resetAfterTest();

        /** @var \core_question_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category();
        $othercategory = $generator->create_question_category();
        $generator->create_question('truefalse', null, ['category' => $category->id, 'idnumber' => 'id9']);
        $generator->create_question('truefalse', null, ['category' => $category->id, 'idnumber' => 'id10']);
        // Next one to make sure only idnumbers from the right category are ruled out.
        $generator->create_question('truefalse', null, ['category' => $othercategory->id, 'idnumber' => 'id11']);

        $this->assertSame('id11', \core_question\question_manager::
        core_question_find_next_unused_idnumber('id9', $category->id));
        $this->assertSame('id11', \core_question\question_manager::
        core_question_find_next_unused_idnumber('id8', $category->id));
    }

    /**
     * Test question bank entry object.
     *
     * @covers ::get_question_bank_entry
     */
    public function test_get_question_bank_entry() {
        global $DB;
        $this->resetAfterTest();
        // Setup.
        $context = \context_system::instance();
        $qgen = $this->getDataGenerator()->get_plugin_generator('core_question');
        $qcat = $qgen->create_question_category(array('contextid' => $context->id));
        $q1 = $qgen->create_question('shortanswer', null, array('category' => $qcat->id));
        // Make sure there is an entry in the entry table.
        $sql = 'SELECT qbe.id as id,
                       qv.id as versionid
                  FROM {question_bank_entries} qbe
                  JOIN {question_versions} qv
                    ON qbe.id = qv.questionbankentryid
                  JOIN {question} q
                    ON qv.questionid = q.id
                 WHERE q.id = ?';
        $records = $DB->get_records_sql($sql, [$q1->id]);
        $this->assertCount(1, $records);
        $record = reset($records);
        $questionbankentry = \core_question\question_manager::get_question_bank_entry($q1->id);
        $this->assertEquals($questionbankentry->id, $record->id);
    }

    /**
     * Test method is_latest().
     *
     * @covers ::is_latest
     *
     */
    public function test_is_latest() {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $qcat1 = $generator->create_question_category(['name' => 'My category', 'sortorder' => 1, 'idnumber' => 'myqcat']);
        $question = $generator->create_question('shortanswer', null, ['name' => 'q1', 'category' => $qcat1->id]);
        $record = $DB->get_record('question_versions', ['questionid' => $question->id]);
        $firstversion = $record->version;
        $questionbankentryid = $record->questionbankentryid;
        $islatest = \core_question\question_manager::is_latest($firstversion, $questionbankentryid);
        $this->assertTrue($islatest);
    }

    /**
     * Test the version objects for a question.
     *
     * @covers ::get_question_version
     */
    public function test_get_question_version() {
        global $DB;
        $this->resetAfterTest();
        // Setup.
        $context = \context_system::instance();
        $qgen = $this->getDataGenerator()->get_plugin_generator('core_question');
        $qcat = $qgen->create_question_category(array('contextid' => $context->id));
        $q1 = $qgen->create_question('shortanswer', null, array('category' => $qcat->id));
        // Make sure there is an entry in the entry table.
        $sql = 'SELECT qbe.id as id,
                       qv.id as versionid
                  FROM {question_bank_entries} qbe
                  JOIN {question_versions} qv
                    ON qbe.id = qv.questionbankentryid
                  JOIN {question} q
                    ON qv.questionid = q.id
                 WHERE q.id = ?';
        $records = $DB->get_records_sql($sql, [$q1->id]);
        $this->assertCount(1, $records);
        $record = reset($records);
        $questionversions = \core_question\question_manager::get_question_version($q1->id);
        $questionversion = reset($questionversions);
        $this->assertEquals($questionversion->id, $record->versionid);
    }

    /**
     * Test get next version of a question.
     *
     * @covers ::get_next_version
     */
    public function test_get_next_version() {
        global $DB;
        $this->resetAfterTest();
        // Setup.
        $context = \context_system::instance();
        $qgen = $this->getDataGenerator()->get_plugin_generator('core_question');
        $qcat = $qgen->create_question_category(array('contextid' => $context->id));
        $q1 = $qgen->create_question('shortanswer', null, array('category' => $qcat->id));
        // Make sure there is an entry in the entry table.
        $sql = 'SELECT qbe.id as id,
                       qv.id as versionid,
                       qv.version
                  FROM {question_bank_entries} qbe
                  JOIN {question_versions} qv
                    ON qbe.id = qv.questionbankentryid
                  JOIN {question} q
                    ON qv.questionid = q.id
                 WHERE q.id = ?';
        $records = $DB->get_records_sql($sql, [$q1->id]);
        $this->assertCount(1, $records);
        $record = reset($records);
        $this->assertEquals(1, $record->version);
        $nextversion = \core_question\question_manager::get_next_version($record->id);
        $this->assertEquals(2, $nextversion);
    }

    /**
     * Test question re-order question types.
     *
     * @covers ::question_reorder_qtypes
     */
    public function test_question_reorder_qtypes() {
        $this->assertEquals(
            array(0 => 't2', 1 => 't1', 2 => 't3'),
            \core_question\local\bank\question_options_manager::question_reorder_qtypes(array('t1' => '', 't2' => '', 't3' => ''), 't1', +1));
        $this->assertEquals(
            array(0 => 't1', 1 => 't2', 2 => 't3'),
            \core_question\local\bank\question_options_manager::question_reorder_qtypes(array('t1' => '', 't2' => '', 't3' => ''), 't1', -1));
        $this->assertEquals(
            array(0 => 't2', 1 => 't1', 2 => 't3'),
            \core_question\local\bank\question_options_manager::question_reorder_qtypes(array('t1' => '', 't2' => '', 't3' => ''), 't2', -1));
        $this->assertEquals(
            array(0 => 't1', 1 => 't2', 2 => 't3'),
            \core_question\local\bank\question_options_manager::question_reorder_qtypes(array('t1' => '', 't2' => '', 't3' => ''), 't3', +1));
        $this->assertEquals(
            array(0 => 't1', 1 => 't2', 2 => 't3'),
            \core_question\local\bank\question_options_manager::question_reorder_qtypes(array('t1' => '', 't2' => '', 't3' => ''), 'missing', +1));
    }

    public function test_match_grade_options() {
        $gradeoptions = \question_bank::fraction_options_full();

        $this->assertEquals(0.3333333, \core_question\local\bank\question_options_manager::
        match_grade_options($gradeoptions, 0.3333333, 'error'));
        $this->assertEquals(0.3333333, \core_question\local\bank\question_options_manager::
        match_grade_options($gradeoptions, 0.333333, 'error'));
        $this->assertEquals(0.3333333, \core_question\local\bank\question_options_manager::
        match_grade_options($gradeoptions, 0.33333, 'error'));
        $this->assertFalse(\core_question\local\bank\question_options_manager::
        match_grade_options($gradeoptions, 0.3333, 'error'));

        $this->assertEquals(0.3333333, \core_question\local\bank\question_options_manager::
        match_grade_options($gradeoptions, 0.3333333, 'nearest'));
        $this->assertEquals(0.3333333, \core_question\local\bank\question_options_manager::
        match_grade_options($gradeoptions, 0.333333, 'nearest'));
        $this->assertEquals(0.3333333, \core_question\local\bank\question_options_manager::
        match_grade_options($gradeoptions, 0.33333, 'nearest'));
        $this->assertEquals(0.3333333, \core_question\local\bank\question_options_manager::
        match_grade_options($gradeoptions, 0.33, 'nearest'));

        $this->assertEquals(-0.1428571, \core_question\local\bank\question_options_manager::
        match_grade_options($gradeoptions, -0.15, 'nearest'));
    }

    /**
     * get_question_options should add the category object to the given question.
     */
    public function test_get_question_options_includes_category_object_single_question() {
        $this->resetAfterTest();
        list($category, $course, $quiz, $qcat, $questions) = $this->setup_quiz_and_questions();
        $question = array_shift($questions);

        \core_question\question_manager::get_question_options($question);

        $this->assertEquals($qcat, $question->categoryobject);
    }

    /**
     * get_question_options should add the category object to all of the questions in
     * the given list.
     */
    public function test_get_question_options_includes_category_object_multiple_questions() {
        $this->resetAfterTest();
        list($category, $course, $quiz, $qcat, $questions) = $this->setup_quiz_and_questions();

        \core_question\question_manager::get_question_options($questions);

        foreach ($questions as $question) {
            $this->assertEquals($qcat, $question->categoryobject);
        }
    }

}
