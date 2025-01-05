@mod @mod_surveypro @surveyprofield @surveyprofield_sliders
Feature: Create a sliders item
  In order to test sliders setup form
  As a teacher
  I fill each its field and I return back to verify all is where I wrote it

  @javascript
  Scenario: Test sliders setup form
    Given the following "courses" exist:
      | fullname            | shortname           | category | groupmode |
      | sliders setup form | sliders setup form | 0        | 0         |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@nowhere.net |
    And the following "course enrolments" exist:
      | user     | course              | role           |
      | teacher1 | sliders setup form | editingteacher |
    And the following "activities" exist:
      | activity  | name                     | intro                    | course              |
      | surveypro | Test sliders setup form | Test sliders setup form | sliders setup form |
    And surveypro "Test sliders setup form" has the following items:
      | type  | plugin  |
      | field | boolean |
    And I am on the "Test sliders setup form" "mod_surveypro > Layout from secondary navigation" page logged in as teacher1

    # add an sliders item
    And I set the field "typeplugin" to "sliders"
    And I press "Add"

    And I expand all fieldsets
    And I set the following fields to these values:
      | Content                  | What do you usually get for breakfast? |
      | Required                 | 1                                      |
      | Indent                   | 1                                      |
      | Question position        | left                                   |
      | Element number           | II.a                                   |
      | Hide filling instruction | 1                                      |
      | Variable                 | C1                                     |
      | Additional note          | Additional note                        |
      | Hidden                   | 1                                      |
      | Search form              | 1                                      |
      | Reserved                 | 1                                      |
      | Parent element           | Boolean [1]: Is it true?               |
      | Parent content           | 1                                      |
    And I set the multiline field "Options" to "milk\n\n\ncoffee\n     butter\n\nbread\n\n\n      "
    And I set the following fields to these values:
      | Option "other"           | other->specify                         |
    And I set the multiline field "Default" to "\n\n\ncoffee\n    bread\nother\n\n      "
    And I set the following fields to these values:
      | "No answer" as defaults  | 0                                      |
      | Adjustment               | vertical                               |
      | Download format          | label of selected items                |
      | minimumrequired          | 2                                      |
    And I press "Add"

    And I follow "edit_item_2"
    Then the field "Content" matches value "What do you usually get for breakfast?"
    Then the field "Required" matches value "1"
    Then the field "Indent" matches value "1"
    Then the field "Question position" matches value "left"
    Then the field "Element number" matches value "II.a"
    Then the field "Hide filling instruction" matches value "1"
    Then the field "Variable" matches value "C1"
    Then the field "Additional note" matches value "Additional note"
    Then the field "Hidden" matches value "1"
    Then the field "Search form" matches value "1"
    Then the field "Reserved" matches value "1"
    Then the field "Parent element" matches value "Boolean [1]: Is it true?"
    Then the field "Parent content" matches value "1"
    Then the field "Options" matches multiline:
      """
      milk
      coffee
      butter
      bread
      """
    Then the field "Option \"other\"" matches value "other->specify"
    Then the field "Default" matches multiline:
      """
      coffee
      bread
      other
      """
    Then the field "\"No answer\" as defaults" matches value "0"
    Then the field "Adjustment" matches value "vertical"
    Then the field "Download format" matches value "label of selected items"
    Then the field "id_minimumrequired" matches value "2"
    Then the field "minimumrequired" matches value "2"
    And I press "Cancel"

    And I follow "show_item_2"
    And I select "Preview" from the "jump" singleselect
    Then I should see "II.a What do you usually get for breakfast?"
    Then the field "id_surveypro_field_sliders_2_0" matches value "0"
    Then the field "id_surveypro_field_sliders_2_1" matches value "1"
    Then the field "id_surveypro_field_sliders_2_2" matches value "0"
    Then the field "id_surveypro_field_sliders_2_3" matches value "1"
    Then the field "id_surveypro_field_sliders_2_other" matches value "1"
    Then the field "id_surveypro_field_sliders_2_text" matches value "specify"
    Then I should see "Additional note"
