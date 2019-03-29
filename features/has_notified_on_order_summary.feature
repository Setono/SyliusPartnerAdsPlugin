@notified
Feature: Check if partner ads has been notified on order summary page
    As a Customer

    Background:
        Given the store operates on a single channel in "United States"
        And I enter the shop from partner ads link
        And the store has a product "Sig Sauer P226" priced at "$499.99"
        And the store ships everywhere for free
        And the store allows paying with "Cash on Delivery"
        And I am a logged in customer
        And a partner ads program with program id "1234" is made for current channel
        And a partner ads cookie has been set

    @ui
    Scenario: Partner ads has been notified
        Given I have product "Sig Sauer P226" in the cart
        And I specified the shipping address as "Ankh Morpork", "Frost Alley", "90210", "United States" for "Jon Snow"
        And I proceed with "Free" shipping method and "Cash on Delivery" payment
        When I confirm my order
        Then partner ads should have been notified
