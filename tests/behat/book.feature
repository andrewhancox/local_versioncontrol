@local @opensourcelearning @local_versioncontrol @javascript
Feature: In a book, create chapters and sub chapters
  In order to create chapters and subchapters
  As a teacher
  I need to add chapters and subchapters to a book.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
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
      | book     | Test book | C1     | 1       |
    And the following "mod_book > chapter" exists:
      | book    | Test book                       |
      | title   | Dummy first chapter             |
      | content | Dream is the start of a journey |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on

  Scenario: Create chapters and sub chapters and navigate between them
    Given I am on the "Test book" "book activity" page
    And I click on "Add new chapter after \"Dummy first chapter\"" "link" in the "Table of contents" "block"
    And I set the following fields to these values:
      | Chapter title | Dummy second chapter |
      | Content | The path is the second part |
    And I press "Save changes"
    Then I should not see "You have made changes to this activity which have not been committed to version control"

    Given I log out
    And I log in as "admin"
    And I am on the "Test book" "book activity" page
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

    Given I log out
    And I log in as "teacher1"
    And I am on the "Test book" "book activity" page
    And I turn editing mode on
    And I click on "Add new chapter after \"Dummy second chapter\"" "link" in the "Table of contents" "block"
    And I set the following fields to these values:
      | Chapter title | Dummy third chapter |
      | Content | The path is the third part |
    And I press "Save changes"
    Then I should see "You have made changes to this activity which have not been committed to version control"

    Given I click on "Commit to version control" "link" in the ".notifications" "css_element"
    And I set the following fields to these values:
      | Commit message (description of changes) | Added third chapter |
    And I press "Save changes"
    Then I should not see "You have made changes to this activity which have not been committed to version control"
    Then I should see "Changes successfully committed"
    And I run all adhoc tasks

    Then I navigate to "Version control" in current page administration
    And I should see "Admin User" in the "Initial commit" "table_row"
    And I should see "Teacher 1" in the "Added third chapter" "table_row"

    Then I click on "View commit" "link" in the "Added third chapter" "table_row"
    And I should see "<title>Dummy third chapter</title>" in the ".myDiffElement" "css_element"
