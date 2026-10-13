# Target environment test plan

M01-M15 have NOT been executed in the user's XAMPP installation. Record actual outcomes and screenshots. Do not replace Pending with Pass without performing the steps. M16 was verified separately through GitHub APIs; see [publication evidence](github_publication.md).

| ID | Check and steps | Expected outcome | Actual status |
|---|---|---|---|
| M01 | Create fleet_system and import schema.mysql.sql in phpMyAdmin | All seven tables exist; no SQL import errors | Pending |
| M02 | Inspect SHOW CREATE TABLE assignments | Generated active IDs, unique indexes and foreign keys exist | Pending |
| M03 | Create a new administrator and sign in | Password is hashed; correct credentials open the dashboard | Pending |
| M04 | Seed fictional data in empty tables; run verification_queries.sql | Five vehicles, three drivers, three services, two repairs; total 92050 pence | Pending |
| M05 | Open a protected page while signed out; sign out and use Back | Login is required; a fresh protected request does not reveal data | Pending |
| M06 | Add, find and edit a vehicle; try its registration again | Valid changes persist; duplicate registration and lower mileage fail | Pending |
| M07 | Assign a driver; try a conflicting assignment; end it | Conflict fails; ending preserves history and correct status | Pending |
| M08 | Enter a negative cost, impossible date and future completed-work date | Invalid entries fail without partial records | Pending |
| M09 | Inspect boundaries at today, today plus 30 days and plus 31 days | Agreed warning rules hold in the target timezone | Pending |
| M10 | Compare cost report with manual addition and Q9 | Exact totals agree; archive does not hide spending | Pending |
| M11 | Use a second browser/session to race assignments and end/reassign actions | No duplicate active allocations; status remains consistent | Pending |
| M12 | Open private directories through Apache, including config/config.example.php | Access denied; directory listing disabled | Pending |
| M13 | Use desktop and a 390-pixel-wide mobile browser, plus keyboard navigation | Readable layout, labels, focus and accessible navigation | Pending |
| M14 | Review unexpected input, HTML text and CSRF manipulation in forms | Output escaped; prepared values used; invalid token rejected | Pending |
| M15 | Run php tests/run.php locally and retain the JSON output | All checks pass; output states its SQLite environment | Pending |
| M16 | Upload reviewed source and check the real GitHub link | Correct repository opens; configuration/secrets are absent | Pass via GitHub API |

## Test result record

For every executed manual test, keep the test ID, date, environment/version, input, expected output, actual output, pass/fail, screenshot filename and defect reference. Use a separate clean demo database for intentionally invalid or conflicting data. Avoid displaying passwords, session IDs, real driver records or personal identifiers in screenshots.

## Defect log

Use defect ID, description, severity, reproduction steps, affected test, fix and retest result. Do not invent defects or retrospective activity to fill the table. The assistant's review strengthened end-assignment logic by re-reading the assignment after acquiring the vehicle lock; multi-session MySQL verification remains Pending.
