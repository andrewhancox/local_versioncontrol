@local @opensourcelearning @local_versioncontrol @javascript
Feature: on a page, make changes to the page
  In order to change the content of a page
  As a teacher
  I need to change the settings of a page

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | teacher | teacher1@example.com |
      | student1 | Student | student | student1@example.com |
    And the following "course" exists:
      | fullname    | Course 1 |
      | shortname   | C1       |
      | format      | topics   |
      | numsections | 1        |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student        |
    And the following "activities" exist:
      | activity | name      | course | section |
      | page     | test page | C1     | 1       |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on

  Scenario: Change the content of a page
  Given I am on the "test page" "page activity editing" page logged in as admin
    And I expand all fieldsets    
    And I set the page content to "content after changing"
    And I press "Save and display"
    Then I should see "You have made changes to this activity which have not been committed to version control"

Scenario: reloading the page, before committing
    Given I reload the page
    Then I should not see "Changes successfully committed"
    And I should not see "You have made changes to this activity which have not been committed to version control"
    And I run all adhoc tasks


  Scenario: commit to version control
    Given I click on "Commit to version control" "link" in the ".notifications" "css_element"
    And I set the following fields to these values:
      | Commit message (description of changes) | changes contents of page |
    And I press "Save changes"
    Then I should not see "You have made changes to this activity which have not been committed to version control"
    Then I should see "Changes successfully committed"
    And I run all adhoc tasks

    Then I navigate to "Version control" in current page administration
    And I should see "Admin User" in the "Initial commit" "table_row"
    And I should see "Teacher 1" in the "Added third chapter" "table_row"

    Then I click on "View commit" "link" in the "Added third chapter" "table_row"
    And I should see "<title>changes contents of page</title>" in the ".myDiffElement" "css_element"