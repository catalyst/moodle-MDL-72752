@qbank @qbank_managecategories @category_reorder @javascript
Feature: A teacher can reorder question categories
  In order to change question category order
  As a teacher
  I need to reorder them

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | weeks  |
    And the following "activities" exist:
      | activity   | name             | intro                   | course | idnumber |
      | qbank      | Test qbank name  | Test qbank description  | C1     | qbank1   |
    And the following "categories" exist:
      | name       | category | idnumber |
      | Category 1 | 0        | CAT1     |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "system role assigns" exist:
      | user     | role           | contextlevel |
      | teacher1 | editingteacher | System       |
    And the following "question categories" exist:
      | contextlevel     | reference     | name                   | idnumber     | info                   |
      | Activity module  | qbank1        | Course category 1      | questioncat1 | Module 1 description   |
      | Activity module  | qbank1        | Course category 2      | questioncat2 | Module 2 description   |
      | Activity module  | qbank1        | Course category 3      | questioncat3 | Module 3 description   |
      | Activity module  | qbank1        | Default for Category 1 |              | Module 4 description   |
    And I am on the "C1" "Course" page logged in as "teacher1"
    And I follow "Test qbank name"
    And I navigate to "Question bank" in current page administration
    And I select "Categories" from the "Question bank tertiary navigation" singleselect

  Scenario: Teacher can see complete edit menu if multiples categories exist under context
    When I click on "Edit" "text" in the "Course category 1" "list_item"
    Then I should see "Edit settings"
    And I should see "Delete"
    And I should see "Export as Moodle XML"

  Scenario: Teacher can reorder categories
    Given "Course category 1" "text" should appear before "Course category 2" "text"
    Given "Course category 2" "text" should appear before "Course category 3" "text"
    And I click on "Move" "link" in the "Course category 1" "list_item"
    And I click on "Default for Category 1" "link" in the "Move category" modal
    Then "Course category 1" "text" should appear after "Default for Category 1" "text"
    And I click on "Move" "link" in the "Course category 2" "list_item"
    And "Course category 2" "text" should appear before "Default for Category 1" "text"

  Scenario: Teacher can display and hide category descriptions
    When I click on "Show descriptions" "checkbox"
    Then I should see "Module 1 description"
    And I click on "Show descriptions" "checkbox"
    And I should not see "Module 1 description"

  Scenario: Teacher can add a new category
    When I click on "Add category" "button"
    And I click on "Parent category" "select"
    And I click on "Top for Test qbank name" "option"
    And I click on "Name" "field"
    And I type "A brand new category"
    And I set the field "Category info" to "A brand new description for a brand new category"
    And I set the field "ID number" to "12345"
    And I click on "Add category" "button" in the "Add category" modal
    And I should see "A brand new category"
    And I should see "12345"
    And I should not see "A brand new description for a brand new category"
    And I click on "qbshowdescr" "checkbox"
    And I wait until the page is ready
    Then I should see "A brand new description for a brand new category"

  Scenario: Teacher cannot submit form if proper input are not entered
    When I click on "Add category" "button"
    And I click on "Add category" "button" in the "Add category" modal
    Then I should see "- The category name cannot be blank."

  Scenario: Teacher cannot drag and drop a used idnumber in context
    Given "Course category 1" "text" should appear before "Course category 3" "text"
    And I click on "Move" "link" in the "Course category 1" "list_item"
    And I click on "Course category 3" "link" in the "Move category" modal
    Then "Course category 1" "text" should appear after "Course category 3" "text"
    And I click on "Edit" "text" in the "Course category 2" "list_item"
    And I choose "Edit settings" in the open action menu
    And I set the field "ID number" to "questioncat1"
    And I click on "Edit category" "button" in the "Edit category" modal
    And I should see "This ID number is already in use"
