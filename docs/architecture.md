# Architecture and technical decisions

## Request flow

A browser requests public/index.php. The bootstrap sets session and security headers, then connects through PDO. The controller checks authentication and CSRF for changes. Validation functions check values and the FleetRepository executes parameterised SQL. Views escape output and return HTML styled with the local CSS file. Successful changes use a 303 redirect so refreshing the page does not repeat the form submission.

## Relational design

Users hold administrator credentials. Vehicles and Drivers are independent entities. Assignments resolves the many-to-many relationship over time; each vehicle and driver may have many historical assignments, but only one active assignment each. Maintenance and Repairs each have one required parent vehicle. LoginAttempts is a separate authentication-support table indexed by an email hash; it has no foreign key because unknown account attempts also need throttling.

The business data follows a practical third-normal-form structure: one vehicle per record, separate driver details, foreign-key-linked maintenance records and no repeated driver fields inside Vehicles. Generated active IDs in Assignments intentionally enforce a temporal uniqueness rule; they are derived constraints rather than duplicated user-maintained data. Users and LoginAttempts are not arbitrarily connected to Vehicles without an implemented ownership requirement.

## Data integrity

Duplicate registrations and licence numbers are rejected. Foreign keys prevent orphan history. Vehicle edits, assignments, unassignment and service updates are transactional. MySQL uses SELECT FOR UPDATE where the operation must inspect and change related state together. Unique generated columns enforce the active-assignment rule even when requests race. Row-lock behaviour still needs multi-connection MySQL verification.

Archive is used instead of physical deletion. This retains service, repair and assignment history and keeps historical cost reports consistent. Archive is refused while an active driver assignment exists. There is no restore/archive-deletion feature in this version.

Costs are non-negative integer pence. Services and repairs are aggregated separately before joining to vehicles; otherwise two services joined to two repairs could create four rows and inflate totals. This is specifically covered by automated test B29.

## Security

The setup script uses password_hash with PASSWORD_DEFAULT and a VARCHAR(255) storage field. Authentication uses password_verify. PDO prepares user-provided values; SQL identifiers come from fixed application code. HTML output uses htmlspecialchars with appropriate flags. Session IDs change after login, cookies are HttpOnly with SameSite Lax, state-changing forms use random CSRF tokens, protected pages reject unauthenticated requests and responses request no-store caching. A 30-minute idle timeout and account-based login throttling are included.

There is no claim of complete security. HTTPS, distributed abuse controls, MFA, independently tested headers/access restrictions, security review and an immutable audit log remain future deployment requirements. Local Apache .htaccess restrictions must be tested; other servers need equivalent access controls.

## Warning rules

MOT and insurance dates before today are Overdue. Dates from today through today plus 30 days inclusive are Due soon. Future dates outside that window are Current. Null values are Not recorded, never presumed compliant. Service is Overdue if its date is before today OR the current mileage reaches its target; it is Due soon within the date window OR within 500 miles. No external records are consulted.

## Reproducibility and evidence

The tests run in an isolated database and preserve live data. The 70 repository/validation checks and 21 simulated HTTP checks use PHP 8.3.33 WebAssembly with SQLite, not an XAMPP or production deployment. Syntax checks cover all supplied PHP files. JSON results and the target manual-test plan are separate so unexecuted checks cannot be confused with passes.
