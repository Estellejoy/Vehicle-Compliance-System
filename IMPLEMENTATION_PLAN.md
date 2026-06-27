# Vehicle Compliance System Work Plan

## Goal

Split the requested updates into two isolated work streams so they can be developed in parallel and merged at the end with minimal conflict.

## Branch Strategy

- `feature/person-1-citizen-system`
- `feature/person-2-officer-admin`

Each branch should stay focused on its own module set and avoid editing shared files unless strictly necessary. Any shared schema or layout changes should be coordinated before merge.

## Person 1 - Citizen & System Features

### UI / UX

- Increase the font size throughout the system.
- Keep these sections unchanged:
  - Vision
  - Mission
  - `Track and Manage...`

### User Accounts

- Research and implement support for multiple roles on one account.
- Allow a single email address to hold more than one role.
- Ensure role selection is clearly visible during login.

### Authentication

- Strengthen password rules:
  - Minimum length
  - Uppercase letters
  - Lowercase letters
  - Numbers
  - Special characters
- Add a `Forgot Password` feature.

### Vehicle Registration

- Research whether a vehicle can have multiple owners.
- Research how jointly owned vehicles are registered.
- Research who is legally responsible for jointly owned vehicles.

### Vehicle Information

- Add `Chassis Number (VIN)`.
- Add `Insurance Type` with values such as `Comprehensive` and `Third Party`.
- Add `Insurance Expiry Date`.
- Add `Payment Period` such as `Annual`.
- Add `Driver's Licence Class`.
- Confirm the current licence categories in Kenya before implementation.
- Add `Next Probable Service` based on kilometres rather than months.

### Vehicle Servicing

- Research the average service interval in kilometres.
- Research what is usually checked during vehicle servicing.

## Person 2 - Officer, Admin & Reporting Features

### Officer Module

- Replace `Oil Change` with `Upload Service Report / Service Form`.
- Create a dummy service document for testing.

### Inspector Information

- Add `Inspector / Officer Staff ID`.

### Printing & Reports

- Allow both citizens and officers to print records.
- Allow both citizens and officers to download reports.
- Generate annual compliance reports.
- Make printed outputs clear, meaningful, and user-friendly.

### Admin Module

- Allow admin users to create user accounts.
- Allow admin users to reset or change passwords.
- Allow admin users to manage user roles.
- Allow admin users to manage officer accounts.
- Allow admin users to generate management reports.

### Research Tasks

- Investigate whether users can hold multiple roles in one system account.
- Determine best practices for password recovery by administrators.
- Identify the reports managers typically require.

## Non-Blocking Notes

- The two work streams are independent enough to be developed in parallel.
- Research items can be completed alongside implementation work.
- Shared decisions to confirm before merge:
  - Multi-role account model
  - Password recovery flow
  - Vehicle ownership rules
  - Kenya licence categories
  - Service interval assumptions

## Merge Plan

1. Develop Person 1 and Person 2 changes in separate branches.
2. Keep commits focused by feature area.
3. Rebase or merge the feature branches against the latest main branch before integration.
4. Resolve shared schema, UI, or route conflicts during the final merge.
5. Validate the combined system after merge.

## Deliverable

This document is the working reference for the split implementation effort and the final integration phase.
