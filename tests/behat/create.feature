@tool @tool_uploadexternalcontent @_file_upload
Feature: An admin can create a single external content activity course using a text delimited file
  In order to create courses using a text delimited file
  As an admin
  I need to be able to upload a text delimited file and navigate through the import process

  @javascript
  Scenario: Creation of non-existent course from file with comma delimiter
    When I log in as "admin"
    And I navigate to "Courses > Upload external content courses" in site administration
    And I upload "admin/tool/uploadexternalcontent/tests/fixtures/onecourse.csv" file to "CSV file" filemanager
    And I select "comma" from the "CSV delimiter" singleselect
    And I press "Import"
    And I press "Confirm"
    And I should see "Courses created: 1"
    And I am on site homepage
    And I should see "C1b49aa30-e719-11e6-9835-f723b46a2688 Full Name"

  @javascript
  Scenario: Creation of non-existent course from file with colon delimiter
    When I log in as "admin"
    And I navigate to "Courses > Upload external content courses" in site administration
    And I upload "admin/tool/uploadexternalcontent/tests/fixtures/onecoursecolon.txt" file to "CSV file" filemanager
    And I select "colon" from the "CSV delimiter" singleselect
    And I press "Import"
    And I press "Confirm"
    And I should see "Courses created: 1"
    And I am on site homepage
    And I should see "C1b49aa30-e719-11e6-9835-f723b46a2699 Full Name"

  @javascript
  Scenario: Creation of non-existent course from file with semicolon delimiter
    When I log in as "admin"
    And I navigate to "Courses > Upload external content courses" in site administration
    And I upload "admin/tool/uploadexternalcontent/tests/fixtures/onecoursesemicolon.txt" file to "CSV file" filemanager
    And I select "semicolon" from the "CSV delimiter" singleselect
    And I press "Import"
    And I press "Confirm"
    And I should see "Courses created: 1"
    And I am on site homepage
    And I should see "C1b49aa30-e719-11e6-9835-f723b46a2689 Full Name"

  @javascript
  Scenario: Creation of non-existent course from file with tab delimiter
    When I log in as "admin"
    And I navigate to "Courses > Upload external content courses" in site administration
    And I upload "admin/tool/uploadexternalcontent/tests/fixtures/onecoursetab.txt" file to "CSV file" filemanager
    And I select "tab" from the "CSV delimiter" singleselect
    And I press "Import"
    And I press "Confirm"
    And I should see "Courses created: 1"
    And I am on site homepage
    And I should see "C1b49aa30-e719-11e6-9835-f723b46a2999 Full Name"
