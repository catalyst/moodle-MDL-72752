@qbank @qbank_editquestion @javascript
Feature: Use the qbank base view filter questions according to
  question status

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity   | name      | course | idnumber |
      | quiz       | Test quiz | C1     | quiz1    |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name                  | questiontext              |
      | Test questions   | truefalse | First question        | Answer the first question |
      | Test questions   | truefalse | Second question       | Answer the first question |

  @javascript
  Scenario: Questions bank can filter according to question status
    Given I am on the "Course 1" "Question bank" page logged in as admin
    And I click on "Clear filters" "button"
    And I set the field "type" in the "Filter 1" "fieldset" to "Category"
    And I set the field "Type or select..." in the "Filter 1" "fieldset" to "Test questions"
    And I click on "Apply filters" "button"
    And I should see "First question"
    And I should see "Second question"
    And I should see "Ready" in the "First question" "table_row"
    And I should see "Ready" in the "Second question" "table_row"
    And I click on "question_status_dropdown" "select" in the "First question" "table_row"
    And I should see "Draft"
    And I click on "draft" "option"
    And I click on "Clear filters" "button"
    And I set the field "type" in the "Filter 1" "fieldset" to "Category"
    And I set the field "Type or select..." in the "Filter 1" "fieldset" to "Test questions"
    And I click on "Add condition" "button"
    And I set the field "type" in the "Filter 2" "fieldset" to "Status"
    And I set the field "Type or select..." in the "Filter 2" "fieldset" to "Draft"
    And I click on "Apply filters" "button"
    And I should see "First question"
    And I click on "Clear filters" "button"
    And I set the field "type" in the "Filter 1" "fieldset" to "Category"
    And I set the field "Type or select..." in the "Filter 1" "fieldset" to "Test questions"
    And I click on "Add condition" "button"
    And I set the field "type" in the "Filter 2" "fieldset" to "Status"
    And I set the field "Type or select..." in the "Filter 2" "fieldset" to "Ready"
    And I click on "Apply filters" "button"
    And I should see "Second question"
