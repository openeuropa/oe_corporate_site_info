@api @javascript @gogo
Feature: Corporate Site Information Configuration form feature
  In order to use corporate site information
  As an user with sufficient permissions
  I need to be able to update appropriate fields in site information form

  Scenario: Update value of Site owner and Content owner fields for corporate site information.
    Given I am logged in as a user with the "view the administration theme, administer site configuration, view published skos concept entities, access administration pages" permissions

    When I visit "admin/config/system/site-information"
    Then I should see the text "Corporate site information"

    When I fill in "Site owner" with "ACP–EU Joint Assembly"
    And I fill in the 1st "Default content owner(s)" with "Directorate-General for Agriculture and Rural Development"
    And I press "Add another item"
    And I wait for AJAX to finish
    And I fill in the 2nd "Default content owner(s)" with "Directorate-General for Budget"
    And I press "Save configuration"
    And I fill in the 3rd "Default content owner(s)" with "Directorate-General for Climate Action"
    And I press "Save configuration"
    Then the "Site owner" field should contain "ACP–EU Joint Assembly (http://publications.europa.eu/resource/authority/corporate-body/ACP-EU_JA)"
    And the 1st "Default content owner(s)" field value is "Directorate-General for Agriculture and Rural Development (http://publications.europa.eu/resource/authority/corporate-body/AGRI)"
    And the 2nd "Default content owner(s)" field value is "Directorate-General for Budget (http://publications.europa.eu/resource/authority/corporate-body/BUDG)"
    And the 3rd "Default content owner(s)" field value is "Directorate-General for Climate Action (http://publications.europa.eu/resource/authority/corporate-body/CLIMA)"

    When I press "Show row weights"
    And I fill in the 3rd "Weight for row" with "-2"
    And I press "Save configuration"
    Then the 1st "Default content owner(s)" field value is "Directorate-General for Climate Action (http://publications.europa.eu/resource/authority/corporate-body/CLIMA)"
    And the 2nd "Default content owner(s)" field value is "Directorate-General for Agriculture and Rural Development (http://publications.europa.eu/resource/authority/corporate-body/AGRI)"
    And the 3rd "Default content owner(s)" field value is "Directorate-General for Budget (http://publications.europa.eu/resource/authority/corporate-body/BUDG)"

    When I fill in "Site owner" with "invalid skos term"
    And I fill in the 2nd "Default content owner(s)" with ""
    And I press "Save configuration"
    Then I should see the text 'There are no entities matching \"invalid skos term\".'

    When I fill in "Site owner" with "ACP–EU Joint Assembly"
    And I press "Save configuration"
    Then the 1st "Default content owner(s)" field value is "Directorate-General for Climate Action (http://publications.europa.eu/resource/authority/corporate-body/CLIMA)"
    And the 2nd "Default content owner(s)" field value is "Directorate-General for Budget (http://publications.europa.eu/resource/authority/corporate-body/BUDG)"
    And the 3rd "Default content owner(s)" field value is ""

    When I fill in the 1st "Default content owner(s)" with ""
    And I fill in the 2nd "Default content owner(s)" with ""
    And I press "Save configuration"
    Then I should see the text "You have to select at least 1 content owner."
