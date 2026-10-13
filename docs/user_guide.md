# Fleet Desk user guide

1. Sign in using the administrator created during local setup. Do not share the password.
2. The Overview page displays active vehicles, upcoming/expired dates, service reminders and year-to-date maintenance costs. Missing dates are shown separately.
3. Open Vehicles to add a registration, make/model, year, current mileage and optional compliance/service dates. Search by registration, make or model and filter by operational status.
4. Select a vehicle registration to inspect service, repair and assignment history. Edit updates current information; mileage cannot be reduced. In Use is managed through assignments.
5. Open Drivers to add or update fictional demonstration records. For a real deployment, driver details must be handled under the organisation's privacy arrangements. Format validation is not a DVLA licence check.
6. Open Assignments, choose an available active vehicle and an active, unassigned driver with an unexpired licence. End assignment preserves history. A driver cannot be deactivated until their assignment ends.
7. Open Services or Repairs and add a completed-work record. Enter cost in pounds; it is stored as integer pence. Historical service mileage cannot lower the current odometer. New service schedule values must follow the recorded work and exceed current mileage.
8. Open Cost reports and choose a date range. Archived vehicles remain included so historic spending is not lost.
9. Use Archive for an inactive vehicle only after ending any current assignment. Confirm on the dedicated page. Archive is not physical deletion and does not remove history.
10. Use Sign out when finished. The application also expires idle sessions after 30 minutes.

If a form fails validation, correct the message and resubmit. If a database error occurs, check the installation and avoid repeatedly changing records before the cause is known. Local prototype data should be backed up before experiments.
