@mod @mod_courserating @core_completion
Feature: View activity completion in the courserating activity
  In order to have visibility of courserating completion requirements
  As a student
  I need to be able to view my courserating completion progress

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Vinnie    | Student1 | student1@example.com |
      | teacher1 | Darrell   | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion | showcompletionconditions |
      | Course 1 | C1        | 1                | 1                        |
    And the following "course enrolments" exist:
      | user | course | role           |
      | student1 | C1 | student        |
      | teacher1 | C1 | editingteacher |
    And the following "activity" exists:
      | activity         | courserating      |
      | course           | C1            |
      | idnumber         | mh1           |
      | name             | Music history |
      | section          | 1             |
      | completion       | 2             |
      | completionview   | 1             |
      | completionsubmit | 1             |
    And I am on the "Music history" "courserating activity" page logged in as teacher1
    And I click on "Edit questions" "link" in the "[role=main]" "css_element"
    And I add a "Multiple choice" question to the courserating with:
        | Question               | What is your favourite instrument |
        | Label                  | instrument1                       |
        | Multiple choice type   | Multiple choice - single answer   |
        | Multiple choice values | drums\guitar\hurdygurdy           |
    And I log out

  Scenario: courserating module displays completion conditions to teachers
    When I am on the "Music history" "courserating activity" page logged in as teacher1
    Then "Music history" should have the "View" completion condition
    And "Music history" should have the "Submit courserating" completion condition

  Scenario: Student can complete a courserating activity when all conditions are met
    Given I am on the "Music history" "courserating activity" page logged in as student1
    And the "View" completion condition of "Music history" is displayed as "todo"
    And the "Submit courserating" completion condition of "Music history" is displayed as "todo"
    When I follow "Answer the questions"
    And I set the field "drums" to "1"
    And I press "Submit your answers"
    And I press "Continue"
    And I am on the "Music history" "courserating activity" page
    Then the "View" completion condition of "Music history" is displayed as "done"
    And the "Submit courserating" completion condition of "Music history" is displayed as "done"

  @javascript
  Scenario: A student can manually mark the courserating activity as done but a teacher cannot
    Given I am on the "Music history" "courserating activity editing" page logged in as teacher1
    And I expand all fieldsets
    And I set the field "Completion tracking" to "Students can manually mark the activity as completed"
    And I press "Save and display"
    # Teacher view.
    And the manual completion button for "Music history" should be disabled
    And I log out
    # Student view.
    When I am on the "Music history" "courserating activity" page logged in as student1
    Then the manual completion button of "Music history" is displayed as "Mark as done"
    And I toggle the manual completion state of "Music history"
    And the manual completion button of "Music history" is displayed as "Done"
