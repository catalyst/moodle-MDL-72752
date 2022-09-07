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
 * Question context unit tests.
 *
 * @package    core_question
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \core_question\local\bank\question_edit_contexts
 */
class question_capability_test extends \advanced_testcase {

    /**
     * Data provider for tests of question_has_capability_on_context and question_require_capability_on_context.
     *
     * @return  array
     */
    public function question_capability_on_question_provider() {
        return [
            'Unrelated capability which is present' => [
                'capabilities' => [
                    'moodle/question:config' => CAP_ALLOW,
                ],
                'testcapability' => 'config',
                'isowner' => true,
                'expect' => true,
            ],
            'Unrelated capability which is present (not owner)' => [
                'capabilities' => [
                    'moodle/question:config' => CAP_ALLOW,
                ],
                'testcapability' => 'config',
                'isowner' => false,
                'expect' => true,
            ],
            'Unrelated capability which is not set' => [
                'capabilities' => [
                ],
                'testcapability' => 'config',
                'isowner' => true,
                'expect' => false,
            ],
            'Unrelated capability which is not set (not owner)' => [
                'capabilities' => [
                ],
                'testcapability' => 'config',
                'isowner' => false,
                'expect' => false,
            ],
            'Unrelated capability which is prevented' => [
                'capabilities' => [
                    'moodle/question:config' => CAP_PREVENT,
                ],
                'testcapability' => 'config',
                'isowner' => true,
                'expect' => false,
            ],
            'Unrelated capability which is prevented (not owner)' => [
                'capabilities' => [
                    'moodle/question:config' => CAP_PREVENT,
                ],
                'testcapability' => 'config',
                'isowner' => false,
                'expect' => false,
            ],
            'Related capability which is not set' => [
                'capabilities' => [
                ],
                'testcapability' => 'edit',
                'isowner' => true,
                'expect' => false,
            ],
            'Related capability which is not set (not owner)' => [
                'capabilities' => [
                ],
                'testcapability' => 'edit',
                'isowner' => false,
                'expect' => false,
            ],
            'Related capability which is allowed at all, unset at mine' => [
                'capabilities' => [
                    'moodle/question:editall' => CAP_ALLOW,
                ],
                'testcapability' => 'edit',
                'isowner' => true,
                'expect' => true,
            ],
            'Related capability which is allowed at all, unset at mine (not owner)' => [
                'capabilities' => [
                    'moodle/question:editall' => CAP_ALLOW,
                ],
                'testcapability' => 'edit',
                'isowner' => false,
                'expect' => true,
            ],
            'Related capability which is allowed at all, prevented at mine' => [
                'capabilities' => [
                    'moodle/question:editall' => CAP_ALLOW,
                    'moodle/question:editmine' => CAP_PREVENT,
                ],
                'testcapability' => 'edit',
                'isowner' => true,
                'expect' => true,
            ],
            'Related capability which is allowed at all, prevented at mine (not owner)' => [
                'capabilities' => [
                    'moodle/question:editall' => CAP_ALLOW,
                    'moodle/question:editmine' => CAP_PREVENT,
                ],
                'testcapability' => 'edit',
                'isowner' => false,
                'expect' => true,
            ],
            'Related capability which is unset all, allowed at mine' => [
                'capabilities' => [
                    'moodle/question:editall' => CAP_PREVENT,
                    'moodle/question:editmine' => CAP_ALLOW,
                ],
                'testcapability' => 'edit',
                'isowner' => true,
                'expect' => true,
            ],
            'Related capability which is unset all, allowed at mine (not owner)' => [
                'capabilities' => [
                    'moodle/question:editall' => CAP_PREVENT,
                    'moodle/question:editmine' => CAP_ALLOW,
                ],
                'testcapability' => 'edit',
                'isowner' => false,
                'expect' => false,
            ],
        ];
    }

    /**
     * Tests that question_has_capability_on does not throw exception on broken questions.
     *
     * @covers ::question_has_capability_on
     */
    public function test_question_has_capability_on_broken_question() {
        $this->resetAfterTest();
        global $DB;

        // Create the test data.
        $generator = $this->getDataGenerator();
        $questiongenerator = $generator->get_plugin_generator('core_question');

        $category = $generator->create_category();
        $context = \context_coursecat::instance($category->id);
        $questioncat = $questiongenerator->create_question_category([
            'contextid' => $context->id,
        ]);

        // Create a cloze question.
        $question = $questiongenerator->create_question('multianswer', null, [
            'category' => $questioncat->id,
        ]);
        // Now, break the question.
        $DB->delete_records('question_multianswer', ['question' => $question->id]);

        $this->setAdminUser();

        $result = \core_question\local\bank\question_edit_contexts::question_has_capability_on($question->id, 'tag');
        $this->assertTrue($result);

        $this->assertDebuggingCalled();
    }

    /**
     * Tests for the deprecated question_has_capability_on function when passing a stdClass as parameter.
     *
     * @dataProvider question_capability_on_question_provider
     * @param   array   $capabilities The capability assignments to set.
     * @param   string  $capability The capability to test
     * @param   bool    $isowner Whether the user to create the question should be the owner or not.
     * @param   bool    $expect The expected result.
     *
     * @covers ::question_has_capability_on
     */
    public function test_question_has_capability_on_using_stdclass($capabilities, $capability, $isowner, $expect) {
        $this->resetAfterTest();

        // Create the test data.
        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        $category = $this->getDataGenerator()->create_category();
        $context = \context_coursecat::instance($category->id);

        // Assign the user to the role.
        role_assign($roleid, $user->id, $context->id);

        // Assign the capabilities to the role.
        foreach ($capabilities as $capname => $capvalue) {
            assign_capability($capname, $capvalue, $roleid, $context->id);
        }

        $this->setUser($user);

        // The current fake question we make use of is always a stdClass and typically has no ID.
        $fakequestion = (object) [
            'contextid' => $context->id,
        ];

        if ($isowner) {
            $fakequestion->createdby = $user->id;
        } else {
            $fakequestion->createdby = $otheruser->id;
        }

        $result = \core_question\local\bank\question_edit_contexts::question_has_capability_on($fakequestion, $capability);
        $this->assertEquals($expect, $result);
    }

    /**
     * Tests for the deprecated question_has_capability_on function when using question definition.
     *
     * @dataProvider question_capability_on_question_provider
     * @param   array   $capabilities The capability assignments to set.
     * @param   string  $capability The capability to test
     * @param   bool    $isowner Whether the user to create the question should be the owner or not.
     * @param   bool    $expect The expected result.
     *
     * @covers ::question_has_capability_on
     */
    public function test_question_has_capability_on_using_question_definition($capabilities, $capability, $isowner, $expect) {
        $this->resetAfterTest();

        // Create the test data.
        $generator = $this->getDataGenerator();
        $questiongenerator = $generator->get_plugin_generator('core_question');
        $user = $generator->create_user();
        $otheruser = $generator->create_user();
        $roleid = $generator->create_role();
        $category = $generator->create_category();
        $context = \context_coursecat::instance($category->id);
        $questioncat = $questiongenerator->create_question_category([
            'contextid' => $context->id,
        ]);

        // Assign the user to the role.
        role_assign($roleid, $user->id, $context->id);

        // Assign the capabilities to the role.
        foreach ($capabilities as $capname => $capvalue) {
            assign_capability($capname, $capvalue, $roleid, $context->id);
        }

        // Create the question.
        $qtype = 'truefalse';
        $overrides = [
            'category' => $questioncat->id,
            'createdby' => ($isowner) ? $user->id : $otheruser->id,
        ];

        $question = $questiongenerator->create_question($qtype, null, $overrides);

        $this->setUser($user);
        $result = \core_question\local\bank\question_edit_contexts::question_has_capability_on($question, $capability);
        $this->assertEquals($expect, $result);
    }

    /**
     * Tests for the deprecated question_has_capability_on function when using a real question id.
     *
     * @dataProvider question_capability_on_question_provider
     * @param   array   $capabilities The capability assignments to set.
     * @param   string  $capability The capability to test
     * @param   bool    $isowner Whether the user to create the question should be the owner or not.
     * @param   bool    $expect The expected result.
     *
     * @covers ::question_has_capability_on
     */
    public function test_question_has_capability_on_using_question_id($capabilities, $capability, $isowner, $expect) {
        $this->resetAfterTest();

        // Create the test data.
        $generator = $this->getDataGenerator();
        $questiongenerator = $generator->get_plugin_generator('core_question');
        $user = $generator->create_user();
        $otheruser = $generator->create_user();
        $roleid = $generator->create_role();
        $category = $generator->create_category();
        $context = \context_coursecat::instance($category->id);
        $questioncat = $questiongenerator->create_question_category([
            'contextid' => $context->id,
        ]);

        // Assign the user to the role.
        role_assign($roleid, $user->id, $context->id);

        // Assign the capabilities to the role.
        foreach ($capabilities as $capname => $capvalue) {
            assign_capability($capname, $capvalue, $roleid, $context->id);
        }

        // Create the question.
        $qtype = 'truefalse';
        $overrides = [
            'category' => $questioncat->id,
            'createdby' => ($isowner) ? $user->id : $otheruser->id,
        ];

        $question = $questiongenerator->create_question($qtype, null, $overrides);

        $this->setUser($user);
        $result = \core_question\local\bank\question_edit_contexts::question_has_capability_on($question->id, $capability);
        $this->assertEquals($expect, $result);
    }

    /**
     * Tests for the deprecated question_has_capability_on function when using a string as question id.
     *
     * @dataProvider question_capability_on_question_provider
     * @param   array   $capabilities The capability assignments to set.
     * @param   string  $capability The capability to test
     * @param   bool    $isowner Whether the user to create the question should be the owner or not.
     * @param   bool    $expect The expected result.
     *
     * @covers ::question_has_capability_on
     */
    public function test_question_has_capability_on_using_question_string_id($capabilities, $capability, $isowner, $expect) {
        $this->resetAfterTest();

        // Create the test data.
        $generator = $this->getDataGenerator();
        $questiongenerator = $generator->get_plugin_generator('core_question');
        $user = $generator->create_user();
        $otheruser = $generator->create_user();
        $roleid = $generator->create_role();
        $category = $generator->create_category();
        $context = \context_coursecat::instance($category->id);
        $questioncat = $questiongenerator->create_question_category([
            'contextid' => $context->id,
        ]);

        // Assign the user to the role.
        role_assign($roleid, $user->id, $context->id);

        // Assign the capabilities to the role.
        foreach ($capabilities as $capname => $capvalue) {
            assign_capability($capname, $capvalue, $roleid, $context->id);
        }

        // Create the question.
        $qtype = 'truefalse';
        $overrides = [
            'category' => $questioncat->id,
            'createdby' => ($isowner) ? $user->id : $otheruser->id,
        ];

        $question = $questiongenerator->create_question($qtype, null, $overrides);

        $this->setUser($user);
        $result = \core_question\local\bank\question_edit_contexts::question_has_capability_on((string) $question->id, $capability);
        $this->assertEquals($expect, $result);
    }

    /**
     * Tests for the question_has_capability_on function when using a moved question.
     *
     * @dataProvider question_capability_on_question_provider
     * @param   array   $capabilities The capability assignments to set.
     * @param   string  $capability The capability to test
     * @param   bool    $isowner Whether the user to create the question should be the owner or not.
     * @param   bool    $expect The expected result.
     *
     * @covers ::question_has_capability_on
     */
    public function test_question_has_capability_on_using_moved_question($capabilities, $capability, $isowner, $expect) {
        $this->resetAfterTest();

        // Create the test data.
        $generator = $this->getDataGenerator();
        $questiongenerator = $generator->get_plugin_generator('core_question');
        $user = $generator->create_user();
        $otheruser = $generator->create_user();
        $roleid = $generator->create_role();
        $category = $generator->create_category();
        $context = \context_coursecat::instance($category->id);
        $questioncat = $questiongenerator->create_question_category([
            'contextid' => $context->id,
        ]);

        $newcategory = $generator->create_category();
        $newcontext = \context_coursecat::instance($newcategory->id);
        $newquestioncat = $questiongenerator->create_question_category([
            'contextid' => $newcontext->id,
        ]);

        // Assign the user to the role in the _new_ context..
        role_assign($roleid, $user->id, $newcontext->id);

        // Assign the capabilities to the role in the _new_ context.
        foreach ($capabilities as $capname => $capvalue) {
            assign_capability($capname, $capvalue, $roleid, $newcontext->id);
        }

        // Create the question.
        $qtype = 'truefalse';
        $overrides = [
            'category' => $questioncat->id,
            'createdby' => ($isowner) ? $user->id : $otheruser->id,
        ];

        $question = $questiongenerator->create_question($qtype, null, $overrides);

        // Move the question.
        question_move_questions_to_category([$question->id], $newquestioncat->id);

        // Test that the capability is correct after the question has been moved.
        $this->setUser($user);
        $result = \core_question\local\bank\question_edit_contexts::question_has_capability_on($question->id, $capability);
        $this->assertEquals($expect, $result);
    }

    /**
     * Tests for the question_has_capability_on function when using a real question.
     *
     * @dataProvider question_capability_on_question_provider
     * @param   array   $capabilities The capability assignments to set.
     * @param   string  $capability The capability to test
     * @param   bool    $isowner Whether the user to create the question should be the owner or not.
     * @param   bool    $expect The expected result.
     *
     * @covers ::question_has_capability_on
     */
    public function test_question_has_capability_on_using_question($capabilities, $capability, $isowner, $expect) {
        $this->resetAfterTest();

        // Create the test data.
        $generator = $this->getDataGenerator();
        $questiongenerator = $generator->get_plugin_generator('core_question');
        $user = $generator->create_user();
        $otheruser = $generator->create_user();
        $roleid = $generator->create_role();
        $category = $generator->create_category();
        $context = \context_coursecat::instance($category->id);
        $questioncat = $questiongenerator->create_question_category([
            'contextid' => $context->id,
        ]);

        // Assign the user to the role.
        role_assign($roleid, $user->id, $context->id);

        // Assign the capabilities to the role.
        foreach ($capabilities as $capname => $capvalue) {
            assign_capability($capname, $capvalue, $roleid, $context->id);
        }

        // Create the question.
        $question = $questiongenerator->create_question('truefalse', null, [
            'category' => $questioncat->id,
            'createdby' => ($isowner) ? $user->id : $otheruser->id,
        ]);
        $question = \question_bank::load_question_data($question->id);

        $this->setUser($user);
        $result = \core_question\local\bank\question_edit_contexts::question_has_capability_on($question, $capability);
        $this->assertEquals($expect, $result);
    }

    /**
     * Tests that question_has_capability_on throws an exception for wrong parameter types.
     *
     * @covers ::question_has_capability_on
     */
    public function test_question_has_capability_on_wrong_param_type() {
        $this->resetAfterTest();
        // Create the test data.
        $generator = $this->getDataGenerator();
        $questiongenerator = $generator->get_plugin_generator('core_question');
        $user = $generator->create_user();

        $category = $generator->create_category();
        $context = \context_coursecat::instance($category->id);
        $questioncat = $questiongenerator->create_question_category([
            'contextid' => $context->id,
        ]);

        // Create the question.
        $question = $questiongenerator->create_question('truefalse', null, [
            'category' => $questioncat->id,
            'createdby' => $user->id,
        ]);
        $question = \question_bank::load_question_data($question->id);

        $this->setUser($user);
        $result = \core_question\local\bank\question_edit_contexts::question_has_capability_on((string)$question->id, 'tag');
        $this->assertFalse($result);

        $this->expectException('coding_exception');
        $this->expectExceptionMessage('$questionorid parameter needs to be an integer or an object.');
        \core_question\local\bank\question_edit_contexts::question_has_capability_on('one', 'tag');
    }

}
