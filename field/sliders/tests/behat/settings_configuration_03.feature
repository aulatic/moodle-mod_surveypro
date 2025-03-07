@mod @mod_surveypro @surveyprofield @surveyprofield_sliders
Feature: Submit using sliders item and check form validation (3 of 4)
  Setting I check in this test are:
      # required:                 0 - 1
      # Options (fixed):          milk\ncoffee\nbutter\nbread
      # Default:                  empty - coffee
      # Minimum required options: 0 - 2

  Background:
    Given the following "courses" exist:
      | fullname                          | shortname     | category | numsections |
      | Test submission for sliders item | sliders item | 0        | 3           |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | teacher  | teacher1@nowhere.net |
      | student1 | Student1  | user1    | student1@nowhere.net |
    And the following "course enrolments" exist:
      | user     | course        | role           |
      | teacher1 | sliders item | editingteacher |
      | student1 | sliders item | student        |
    And the following "activities" exist:
      | activity  | name           | intro              | course        |
      | surveypro | Surveypro test | For testing backup | sliders item |
    And I am on the "Surveypro test" "mod_surveypro > Layout from secondary navigation" page logged in as teacher1

    And I set the field "typeplugin" to "sliders"
    And I press "Add"
    And I expand all fieldsets

  @javascript
  Scenario: Test sliders element using configuration 05
    # Configuration 05 consists in:
      # required:                 1
      # Options (fixed):          milk\ncoffee\nbutter\nbread
      # Default:                  empty
      # Minimum required options: 0
    Given I set the following fields to these values:
      | Content                  | What do you usually get for breakfast? |
      | Required                 | 1                                      |
    And I set the multiline field "Options" to "milk\n\n\ncoffee\n     butter\n\nbread\n\n\n      "
    And I set the following fields to these values:
      | Default                  |                                        |
      | Minimum required options | 0                                      |
    And I press "Add"

    And I log out

    When I am on the "Surveypro test" "surveypro activity" page logged in as student1

    # Test number 1: Student flies over the answer
    And I press "New response"
    Then I should not see "No answer"
    And I press "Submit"
    And I press "Continue to responses list"
    Then I should see "1" submissions
    # End of test number 1

    # Test number 2: Student submits a standard answer
    And I press "New response"
    And I set the following fields to these values:
      | id_surveypro_field_sliders_1_0 | 1 |
      | id_surveypro_field_sliders_1_3 | 1 |
    And I press "Submit"
    And I press "Continue to responses list"
    Then I should see "2" submissions
    # End of test number 2

  @javascript
  Scenario: Test sliders element using configuration 06
    # Configuration 06 consists in:
      # required:                 1
      # Options (fixed):          milk\ncoffee\nbutter\nbread
      # Default:                  empty
      # Minimum required options: 2
    Given I set the following fields to these values:
      | Content                  | What do you usually get for breakfast? |
      | Required                 | 1                                      |
    And I set the multiline field "Options" to "milk\n\n\ncoffee\n     butter\n\nbread\n\n\n      "
    And I set the following fields to these values:
      | Default                  |                                        |
      | Minimum required options | 2                                      |
    And I press "Add"

    And I log out

    When I am on the "Surveypro test" "surveypro activity" page logged in as student1

    # Test number 3: Student flies over the answer
    And I press "New response"
    Then I should not see "No answer"
    Then I should see "At least 2 sliderses have to be selected"
    And I press "Submit"
    Then I should see "Please tick at least 2 options"
    And I set the field "id_surveypro_field_sliders_1_1" to "1"
    And I press "Submit"
    Then I should see "Please tick at least 2 options"
    And I set the field "id_surveypro_field_sliders_1_2" to "1"
    And I press "Submit"
    And I press "Continue to responses list"
    Then I should see "1" submissions
    # End of test number 3

    # Test number 4: Student submits a standard answer
    And I press "New response"
    Then I should see "At least 2 sliderses have to be selected"
    And I set the following fields to these values:
      | id_surveypro_field_sliders_1_0 | 1 |
      | id_surveypro_field_sliders_1_3 | 1 |
    And I press "Submit"
    And I press "Continue to responses list"
    Then I should see "2" submissions
    # End of test number 4
