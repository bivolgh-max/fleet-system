# Fleet project requirements and delivery plan

## Problem and intended users

The client is a fictional small business operating five to twenty vans. Vehicle details, servicing, repair invoices and renewal dates are assumed to be spread across spreadsheets and paper records. This can make it difficult to find the current driver, compare spending or notice an approaching expiry. These are design assumptions, not findings from a real client interview.

The primary user is a fleet administrator who enters and maintains the records. Drivers are stakeholders whose licence, contact and assignment information is held by the administrator. Driver self-service accounts are not included in this mini project. A lecturer or employer can inspect the documentation and code without using real company data.

Fleet Desk gives the administrator a consistent record structure and date-based warnings. It is not an automated DVLA checker, legal compliance certificate or substitute for checking original documents. It does not need to replace Jira: Jira tracks the development work, while the application handles vehicle-specific operational records and calculations. Commercial fleet systems already exist; this project's justification is its focused learning value and small, transparent scope, not a claim of market uniqueness.

## Functional requirements

| ID | Requirement | Measurable acceptance |
|---|---|---|
| FR01 | Authenticate an administrator | Valid login succeeds; invalid login fails; logout removes access |
| FR02 | Add vehicle records | Required values save; duplicate registrations are rejected |
| FR03 | Search and filter vehicles | Registration, make/model search and status filters return matching records |
| FR04 | Edit vehicle records | Valid updates persist; current mileage cannot decrease |
| FR05 | Archive vehicles | Confirmation is required; history survives; active assignments prevent archiving |
| FR06 | Maintain driver records | Add/edit/search work; email, phone and licence-format checks run |
| FR07 | Assign and unassign drivers | One active assignment per vehicle/driver; historical assignments are retained |
| FR08 | Record servicing | Work, date, mileage, supplier and exact cost are linked to the correct vehicle |
| FR09 | Record repairs | Work, date, supplier and cost save; optional Repair status is supported |
| FR10 | Track compliance dates | Missing, current, due-soon and expired records are distinguished |
| FR11 | Track service schedules | A date OR mileage threshold can produce a warning |
| FR12 | Summarise the fleet | Active counts, compliance alerts and service reminders are calculated |
| FR13 | Report spending | Date-filtered service and repair totals are correct without join multiplication |
| FR14 | Supply a portfolio | README, source, database design, test evidence and a static showcase are provided |

## Non functional requirements

| ID | Requirement | Verification or target |
|---|---|---|
| NFR01 | Reduce common web security risks | Hash passwords, parameterise values, escape output, validate CSRF tokens |
| NFR02 | Preserve relational integrity | Foreign keys, unique active assignments and transactional changes |
| NFR03 | Use a responsive interface | Desktop and narrow-screen layouts; final browser check remains required |
| NFR04 | Make forms understandable | Visible labels, explicit units, required markers, readable errors |
| NFR05 | Support maintainable code | Shared validation, repository methods, setup guide and automated tests |
| NFR06 | Keep local operation practical | Target ordinary page responses within two seconds on 5 to 20 vehicles; not benchmarked |
| NFR07 | Protect confidential configuration | Local credentials excluded from Git; private paths denied by Apache |

## User stories

1. As an administrator, I want to sign in so that only authorised people can access records. Acceptance: correct credentials open the dashboard; invalid credentials fail; logout blocks later access.
2. As an administrator, I want to add vehicles so that the fleet register is complete. Acceptance: required data and unique registration checks run before persistence.
3. As an administrator, I want to find a vehicle quickly so that I can respond to questions. Acceptance: make/model/registration search and status filters return the expected records.
4. As an administrator, I want to edit a vehicle so that dates and mileage stay current. Acceptance: changes save; a smaller odometer reading is rejected.
5. As an administrator, I want to archive an inactive vehicle so that history is not lost. Acceptance: confirmation is required; records remain linked; assigned vehicles cannot be archived.
6. As an administrator, I want to maintain driver details so that responsibility is recorded. Acceptance: validated driver data saves and licence duplicates fail.
7. As an administrator, I want to record driver assignments so that current and previous responsibility is visible. Acceptance: only available vehicles and active drivers with unexpired licences can be assigned; duplicate active allocations fail.
8. As an administrator, I want to record services and repairs so that maintenance history and spending can be inspected. Acceptance: records link to a vehicle; negative costs and future completed-work dates fail.
9. As an administrator, I want reminders so that renewals and services can be arranged. Acceptance: dates within 30 days and mileage within 500 miles are flagged; missing values are never labelled Current.
10. As an administrator, I want a cost report so that spending can be reviewed. Acceptance: multiple services and repairs produce exact totals; archive does not hide historical costs.

## Proposed revised schedule

This is a forward plan from 13 October 2026, not a retrospective account of when earlier Jira work was completed. The brief gives 30 October 2026 as the submission date but does not state a submission time.

| Stage | Proposed dates | Deliverables and dependency |
|---|---|---|
| Sprint 1 Planning and Setup | 13 to 16 October | Requirements, lecturer negotiation, ERD, dictionary, database setup and login review |
| Sprint 2 Core Development | 17 to 23 October | Review vehicle/driver functions, assignments, maintenance, warnings and costs after the schema is confirmed |
| Sprint 3 Testing and Portfolio | 24 to 28 October | Target MySQL tests, browser checks, corrections, screenshots and GitHub upload |
| Final review | 29 October | Confirm personal sections, interview evidence, links, word count and filename |
| Submission | 30 October | Export one combined PDF and submit through the confirmed course route |

The implementation and simulated checks exist now; the schedule reserves time for the student's installation, understanding, review and genuine evidence. It does not claim three sprint meetings have already happened.

## Risk register

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Too much scope for a mini project | Medium | High | Keep one admin role; defer uploads, external lookups and messaging |
| SQLite tests conceal a MySQL-specific fault | Medium | High | Import and retest on XAMPP; inspect generated constraints; run concurrency checks |
| Local PHP or MySQL configuration differs | Medium | Medium | Provide explicit prerequisites, config example and installation guide |
| Confidential data is published | Low | High | Fictional records only; ignore configuration and database files; review commits |
| Personal reflection is unsupported | Medium | High | Add genuine examples and feedback; do not claim independent work without evidence |
| Deadline is missed | Low | High | Finish implementation review before 28 October and verify submission route early |

## Scope and completion rule

Included: the functions and evidence described above. Excluded: payroll, route planning, GPS/telematics, live MOT validation, real company deployment, driver login, document uploads and automated email/SMS. A task is complete only when its acceptance criteria and appropriate evidence are satisfied. Prepared code is not the same as tested target deployment or lecturer approval.
