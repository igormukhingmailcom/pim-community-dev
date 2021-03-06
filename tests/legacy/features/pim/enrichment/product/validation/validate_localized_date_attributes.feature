@javascript
Feature: Validate localized date attributes of a product
  In order to keep my data consistent
  As a regular user
  I need to be able to see validation errors for localized date attributes

  Background:
    Given the "default" catalog configuration
    And the following attributes:
      | code      | label-fr_FR | type             | scopable | unique | date_min   | date_max   | group |
      | release   | Release     | pim_catalog_date | 0        | 1      | 2013-01-01 | 2015-12-12 | other |
      | available | Dispo       | pim_catalog_date | 1        | 0      | 2013-01-01 | 2015-12-12 | other |
      | sold      | Solde       | pim_catalog_date | 0        | 0      |            |            | other |
    And the following family:
      | code | label-en_US | attributes                 | requirements-ecommerce | requirements-mobile |
      | baz  | Baz         | sku,release,available,sold | sku                    | sku                 |
    And the following products:
      | sku | family |
      | foo | baz    |
      | bar | baz    |
    And I am logged in as "Julien"
    And I am on the "foo" product page

  Scenario: Validate the unique constraint of date attribute
    Given I change the Release to "02/02/2013"
    And I save the product
    When I am on the "bar" product page
    And I change the Release to "02/02/2013"
    And I save the product
    Then I should see validation tooltip "L'attribut release ne peut avoir la même valeur qu'une seule fois. La valeur 2013-02-02 est déjà enregistrée pour un autre produit."
    And there should be 1 error in the "[other]" tab

  Scenario: Validate the date min constraint of date attribute
    Given I change the Release to "01/01/2011"
    And I save the product
    Then I should see validation tooltip "L'attribut release requiert une date égale ou postérieure à 2013-01-01."
    And there should be 1 error in the "[other]" tab

  Scenario: Validate the date min constraint of scopable date attribute
    Given I change the Dispo to "01/01/2012"
    And I save the product
    Then I should see validation tooltip "L'attribut available requiert une date égale ou postérieure à 2013-01-01."
    And there should be 1 error in the "[other]" tab

  Scenario: Validate the date max constraint of date attribute
    Given I change the Release to "01/01/2016"
    And I save the product
    Then I should see validation tooltip "L'attribut release requiert une date antérieure ou égale à 2015-12-12."
    And there should be 1 error in the "[other]" tab

  Scenario: Validate the date max constraint of scopable date attribute
    Given I change the Dispo to "03/03/2017"
    And I save the product
    Then I should see validation tooltip "L'attribut available requiert une date antérieure ou égale à 2015-12-12."
    And there should be 1 error in the "[other]" tab
