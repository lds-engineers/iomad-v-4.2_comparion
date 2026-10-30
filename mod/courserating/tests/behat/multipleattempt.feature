@mod @mod_courserating
Feature: Non anonymous courserating with multiple submissions
  In order to modify courserating response
  As a student
  I need to be able to see previous response when I re-submit courserating

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | user1    | Username  | 1        |
      | user2    | Username  | 2        |
      | teacher  | Teacher   | 3        |
      | manager  | Manager   | 4        |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user  | course | role    |
      | user1 | C1     | student |
      | user2 | C1     | student |
      | teacher | C1   | editingteacher |
    And the following "activities" exist:
      | activity   | name            | course | idnumber  | anonymous | publish_stats | multiple_submit | section |
      | courserating   | Course courserating | C1     | courserating1 | 2         | 1             | 1               | 0       |

  Scenario: Completing a courserating second time
    When I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "Course courserating"
    And I click on "Edit questions" "link" in the "[role=main]" "css_element"
    And I add a "Short text answer" question to the courserating with:
      | Question                    | first      |
      | Label                       | shorttext1 |
      | Maximum characters accepted | 200        |
    And I add a page break to the courserating
    And I add a "Short text answer" question to the courserating with:
      | Question                    | second     |
      | Label                       | shorttext2 |
      | Maximum characters accepted | 200        |
    And I log out
    And I log in as "user1"
    And I am on "Course 1" course homepage
    And I follow "Course courserating"
    And I follow "Answer the questions"
    And I set the following fields to these values:
      | first | 111 |
    And I press "Next"
    And I set the following fields to these values:
      | second | 222 |
    And I press "Submit your answers"
    And I log out
    And I log in as "user1"
    And I am on "Course 1" course homepage
    And I follow "Course courserating"
    And I follow "Answer the questions"
    Then the field "first" matches value "111"
    And I press "Next"
    And the field "second" matches value "222"
    And I set the following fields to these values:
      | second | 333 |
    And I press "Submit your answers"
