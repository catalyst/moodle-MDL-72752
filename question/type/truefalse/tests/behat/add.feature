@qtype @qtype_truefalse @javascript
Feature: Test creating a True/False question
  As a teacher
  In order to test my students
  I need to be able to create a True/False question

  Background:
    Given the following "users" exist:
      | username |
      | teacher  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
    And the following "activities" exist:
      | activity   | name             | intro                   | course | idnumber |
      | qbank      | Test qbank name  | Test qbank description  | C1     | qbank1   |
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    And I follow "Test qbank name"

  @javascript
  Scenario: Create a True/False question with Correct answer as False
    And I add a "True/False" question filling the form with:
      | Question name                      | true-false-001                             |
      | Question text                      | Manchester is the capital city of England. |
      | Default mark                       | 1                                          |
      | General feedback                   | London is the capital city of England.     |
      | Correct answer                     | False                                      |
      | Feedback for the response 'True'.  | Well done!                                 |
      | Feedback for the response 'False'. | Read more about England.                   |
    Then I should see "true-false-001"

  @javascript
  Scenario: Create a True/False question with Correct answer as True
    And I add a "True/False" question filling the form with:
      | Question name                      | true-false-002                         |
      | Question text                      | London is the capital city of England. |
      | Default mark                       | 1                                      |
      | General feedback                   | London is the capital city of England. |
      | Correct answer                     | True                                   |
      | Feedback for the response 'True'.  | Well done!                             |
      | Feedback for the response 'False'. | Read more about England.               |
    Then I should see "true-false-002"
