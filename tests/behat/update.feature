@tool @tool_uploadexternalcontent @_file_upload
Feature: An admin can update a single external content activity course using a text delimited file
  In order to update courses using a text delimited file
  As an admin
  I need to be able to upload a text delimited file and navigate through the import process

  @javascript
  Scenario: Update of course from file with comma delimiter
    Given the following "courses" exist:
        | fullname                                        | shortname                                        | idnumber                              |
        | C1b49aa30-e719-11e6-9835-f723b46a2688 Full Name | C1b49aa30-e719-11e6-9835-f723b46a2688 Short Name | C1b49aa30-e719-11e6-9835-f723b46a2688 |
    And the following "activities" exist:
        | activity         | name           | intro          | content          | course                                           | idnumber                              |
        | externalcontent  | External Name  | External Intro | External Content | C1b49aa30-e719-11e6-9835-f723b46a2688 Short Name | C1b49aa30-e719-11e6-9835-f723b46a2688 |
    And I log in as "admin"
    And I navigate to "Courses > Upload external content courses" in site administration
    And I upload "admin/tool/uploadexternalcontent/tests/fixtures/onecourseupdate.csv" file to "CSV file" filemanager
    And I press "Import"
    And I press "Confirm"
    And I should see "Courses updated: 1"
    And I am on site homepage
    And I should see "C1b49aa30-e719-11e6-9835-f723b46a2688 Full Name Updated"
