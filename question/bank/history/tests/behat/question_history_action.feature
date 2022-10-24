@qbank @qbank_history @javascript
Feature: Use the qbank plugin manager page for question history
  In order to check the plugin behaviour with enable and disable

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity   | name      | course | idnumber |
      | quiz       | Test quiz | C1     | quiz1    |
    And the following "question categories" exist:
      | contextlevel            | reference | name           |
      | Activity module         | quiz1     | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name           | questiontext              |
      | Test questions   | truefalse | First question | Answer the first question |

  Scenario: Enable/disable question history column from the base view
    Given I log in as "admin"
    When I navigate to "Plugins > Question bank plugins > Manage question bank plugins" in site administration
    And I should see "Question history"
    And I click on "Disable" "link" in the "Question history" "table_row"
    And I am on the "Test quiz" "mod_quiz > question bank" page
    Then the "History" action should not exist for the "First question" question in the question bank
    And I navigate to "Plugins > Question bank plugins > Manage question bank plugins" in site administration
    And I click on "Enable" "link" in the "Question history" "table_row"
    And I am on the "Test quiz" "mod_quiz > question bank" page
    Then the "History" action should exist for the "First question" question in the question bank

  Scenario: History page shows only the specified features and questions
    Given I am on the "Test quiz" "mod_quiz > question bank" page logged in as "admin"
    And I choose "History" action for "First question" in the question bank
    And I should see "Question"
    And I should see "Actions"
    And I should see "Status"
    And I should see "Version"
    And I should see "Created by"
    And I should see "First question"
    And the "History" action should not exist for the "First question" question in the question bank
    And I click on "#qbank-history-close" "css_element"
    And the "History" action should exist for the "First question" question in the question bank

  Scenario: Actions in the history page should redirect to history page
    Given I log in as "admin"
    And I am on the "Test quiz" "quiz activity" page
    When I navigate to "Question bank" in current page administration
    And I choose "History" action for "First question" in the question bank
    And I should see "First question"
    And I choose "Edit question" action for "First question" in the question bank
    And I set the field "id_name" to "Renamed question v2"
    And I set the field "id_questiontext" to "edited question"
    And I press "id_submitbutton"
    And I should see "First question"
    And I should see "Renamed question v2"
    And I click on ".dropdown-toggle" "css_element" in the "First question" "table_row"
    Then I should not see "History" in the "region-main" "region"
