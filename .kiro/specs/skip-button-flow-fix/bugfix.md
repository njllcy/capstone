# Bugfix Requirements Document

## Introduction

The HealthKiosk measurement flow currently redirects users back to the home.php main menu after completing each measurement, breaking the sequential flow between measurement steps. This makes the measurement process inefficient and disrupts the user experience. Additionally, skip buttons for debugging purposes are not consistently visible or styled across all measurement states, making development and testing difficult.

This bugfix addresses the flow continuation issue and improves skip button visibility and functionality for debugging during development.

## Bug Analysis

### Current Behavior (Defect)

1.1 WHEN a user completes a measurement (height, weight, blood pressure, temperature, or oximeter) and clicks "Done ✓" THEN the system redirects to home.php instead of continuing to the next measurement step

1.2 WHEN a user clicks "Skip for Testing" on any measurement page THEN the system redirects to home.php instead of continuing to the next measurement step

1.3 WHEN a user is in the instruction steps phase of a measurement THEN the skip button is not visible, making it difficult to bypass instructions during testing

1.4 WHEN a user encounters an error screen during measurement THEN the skip button may not be consistently visible or styled across all measurement pages

1.5 WHEN viewing measurement pages THEN emoji icons are used instead of SVG files, which may cause rendering inconsistencies across devices

1.6 WHEN a user completes the height measurement and clicks "Done ✓" THEN the system displays "Failed to save to database" error with HTTP 500 Internal Server Error from api/save_vitals.php endpoint because the .env file is not being loaded by PHP (getenv() returns empty values, causing database connection to fail with default credentials)

1.7 WHEN a user is on the summary page and clicks the "Print" button THEN the system returns HTTP 404 Not Found error because the button links to 'print.html' instead of 'print.php'

1.8 WHEN a user is on the summary page and clicks the "Home" button THEN the system returns HTTP 404 Not Found error because the button links to 'home.html' instead of '../home.php'

1.9 WHEN a user completes all measurements and views the summary page THEN there is no "Done" button to return to the swipe screen (index.php) to start a new session

### Expected Behavior (Correct)

2.1 WHEN a user completes a measurement (height, weight, blood pressure, temperature, or oximeter) and clicks "Done ✓" THEN the system SHALL redirect to the next measurement in the predefined sequence

2.2 WHEN a user clicks "Skip for Testing" on any measurement page THEN the system SHALL redirect to the next measurement in the predefined sequence

2.3 WHEN a user is in the instruction steps phase of a measurement THEN the system SHALL display a prominently visible skip button to allow bypassing instructions during testing

2.4 WHEN a user encounters an error screen during measurement THEN the system SHALL display a consistently styled skip button that continues to the next measurement

2.5 WHEN viewing measurement pages THEN the system SHALL use SVG files instead of emoji icons for consistent rendering across all devices

2.6 WHEN the user completes the last measurement in the sequence THEN the system SHALL redirect to the results summary page or home.php

2.7 WHEN a user completes the height measurement and clicks "Done ✓" THEN the system SHALL successfully save the data to the database by properly loading environment variables from the .env file or using alternative configuration methods

2.8 WHEN a user is on the summary page and clicks the "Print" button THEN the system SHALL navigate to 'print.php' in the same directory without 404 errors

2.9 WHEN a user is on the summary page and clicks the "Home" button THEN the system SHALL navigate to '../home.php' without 404 errors

2.10 WHEN a user completes all measurements and views the summary page THEN the system SHALL display a "Done" button that clears the session and redirects to the swipe screen (index.php) to start a new patient session

### Unchanged Behavior (Regression Prevention)

3.1 WHEN a user clicks "Go Back" buttons on error screens THEN the system SHALL CONTINUE TO redirect to home.php as currently implemented

3.2 WHEN a user successfully saves measurement data THEN the system SHALL CONTINUE TO display the success overlay with "Saved Successfully!" message

3.3 WHEN a user encounters a database save error THEN the system SHALL CONTINUE TO display the error banner and retry button without redirecting

3.4 WHEN a user is idle for the configured timeout period THEN the system SHALL CONTINUE TO display the idle timeout overlay

3.5 WHEN a user navigates to home.php THEN the system SHALL CONTINUE TO display completed measurements with green checkmarks

3.6 WHEN WebSocket connection fails after maximum retry attempts THEN the system SHALL CONTINUE TO display the error screen with retry and skip options
