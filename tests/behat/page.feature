@local @opensourcelearning @local_versioncontrol @javascript
Feature: on a page, make changes to the page
  In order to change the content of a page
  As a teacher
  I need to change the settings of a page

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exists:
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
    And I log in as "admin"
    And I am on the "test page" "page activity" page
    Then I navigate to "Version control" in current page administration
    And I set the field "Tracking type" to "Manual"
    And I press "Save changes"
    Then I should see "You have made changes to this activity which have not been committed to version control"
    
        Given I click on "Commit to version control" "link" in the ".notifications" "css_element"
    And I set the following fields to these values:
      | Commit message (description of changes) | Initial commit |
    And I press "Save changes"
    Then I should not see "You have made changes to this activity which have not been committed to version control"
    Then I should see "Changes successfully committed"

    Given I reload the page
    Then I should not see "Changes successfully committed"
    And I should not see "You have made changes to this activity which have not been committed to version control"
    And I run all adhoc tasks
    
    And I log out
    
    Given I am on the "test page" "page activity editing" page logged in as teacher1
    And I expand all fieldsets 
    And I set the field "Page content" to "content after first changing"
    And I press "Save and display"
    Then I should see "You have made changes to this activity which have not been committed to version control"

    And  I reload the page
    Then I should not see "Changes successfully committed"
    And I should see "You have made changes to this activity which have not been committed to version control"

    And I click on "Commit to version control" "link" in the ".notifications" "css_element"
    And I set the following fields to these values:
      | Commit message (description of changes) | changes contents of page |
    And I press "Save changes"
    Then I should not see "You have made changes to this activity which have not been committed to version control"
    Then I should see "Changes successfully committed"
    And I run all adhoc tasks

    Then I navigate to "Version control" in current page administration
    And I should see "Admin User" in the "Initial commit" "table_row"
    And I should see "Teacher 1" in the "changes contents of page" "table_row"

    Then I click on "View commit" "link" in the "changes contents of page" "table_row"
    And I should see "<content>content after first changing</content>" in the ".myDiffElement" "css_element"