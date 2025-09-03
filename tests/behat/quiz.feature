@local @opensourcelearning @local_versioncontrol @javascript
Feature: Version control with Quiz and Questions
  In order to track changes to questions and quizzes
  As a teacher
  I need to commit changes at question bank category level and within a quiz

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "categories" exist:
      | name        | category | idnumber |
      | category 1  | 0        | CAT1     | 
    And the following "courses" exist:
      | fullname | shortname | category |  format   |
      | Course 1 | C1        | CAT1     | weeks     |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Category    | CAT1       | Test questions |   
    And the following "activities" exist:
      | activity   | name   | course | idnumber |
      | quiz       | Quiz 1 | C1     | quiz1    |
    And the following "questions" exist:
      | questioncategory | qtype     | name                         | user      | questiontext                  |
      | Test questions   | truefalse | First question               | admin     | Answer the first question     |
      | Test questions   | essay     | Test question to be edited   | admin     | Write about whatever you want | 
    And quiz "Quiz 1" contains the following questions:
      | question          | page |
      | First question    | 1    |
      | Test question to be edited| 1 | 
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on  

@javascript
  Scenario: edit a question 
    And I log in as "admin"
    And I am on the "quiz 1" "quiz activity" page
    Then I navigate to "Version control" in current page administration
    And I set the field "Tracking type" to "Manual"
    And I press "Save changes"
    Then I should see "You have made changes to this activity which have not been committed to version control"
    
    Given I click on "Commit to version control" "link" in the ".notifications" "css_element"
    And I set the following fields to these values:
      | Commit message (description of changes) | Initial quiz commit |
    And I press "Save changes"
    Then I should not see "You have made changes to this activity which have not been committed to version control"
    Then I should see "Changes successfully committed"

    Given I reload the page
    Then I should not see "Changes successfully committed"
    And I should not see "You have made changes to this activity which have not been committed to version control"
    And I run all adhoc tasks
    


    Given i am in "Course 1" as a teacher 
        And I am on the "quiz 1" "quiz activity" page
        Then I navigate to "Question bank" in current page administration
    And I click on the 

    And I set the following fields to these values:
      | Question name | Edited question name |
      | Question text | Write a lot about what you want |
    And I press "id_submitbutton"