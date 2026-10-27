# Fleet administrator security campaign

## Audience and delivery

The fictional audience is a small fleet's administrator and staff handling renewal requests and driver records. The design assumes they use the dashboard while doing administrative work and have little time for a long policy. This is a design assumption, not a finding from a real client interview.

The application includes three coordinated artefacts:
1. Security guidance at `index.php?page=security`, available from the signed-in navigation.
2. A printable briefing card at `index.php?page=security_card`, with three short discussion scenarios.
3. Contextual reminders beside MOT and insurance date entry.

The card can be printed with the browser's Print command. Both routes use the existing authentication guard and accept no changes.

## Threats and observable behaviours

| Threat | Vulnerability | Behaviour and mitigation | Remaining dependence |
|---|---|---|---|
| T1 Administrator credential theft | Password reuse and phishing | Use an individual account, a unique password and sign out; protect email with MFA where available | The administrator can still disclose credentials; this app does not implement MFA |
| T2 Fraudulent renewal request | Urgency or changed bank details accepted without checking | Verify through an independently trusted insurer/supplier contact before acting | The administrator must perform the check; the app does not process payments |
| T3 False confidence in compliance dates | A manually entered green label is treated as proof | Inspect the original document and correct vehicle before entering a date | Input checks cannot authenticate the document |
| T4 SQL injection | Untrusted input reaches database queries | Keep PDO parameterisation and server-side validation | Future code changes need review and testing |
| T5 Disclosure of driver information | Unredacted screenshots or shared access | Use fictional records for demonstrations; redact personal records and credentials before sharing | Redaction and responsible sharing depend on the user |

The three primary behavioural goals trace to T1, T2 and T3. Privacy advice also addresses T5. Password hashing, login throttling, parameterised values and CSRF checks are application controls, not substitutes for those behaviours.

## Rationale

The guide is available at the point of work; the card supports a short verbal briefing at the administrator's desk. Field reminders appear when a potentially misleading date is entered. Short examples fit the assumed audience better than a generic cyber-security presentation.

The advice draws on the NCSC's small-organisations guidance: https://www.ncsc.gov.uk/collection/small-organisations-guide-to-cyber-security (accessed 27 October 2026).

## Proposed evaluation

Ask a peer to role-play the fleet administrator. Before and after a five-minute briefing, ask the card's three scenarios and score each correct action 0 or 1. Target: 3/3 after the briefing, with independent contact verification, original-policy inspection and safe screenshot handling explained.

Record the real participant, date, answers, score and feedback. No trial or behavioural improvement has been recorded. A small role-play and immediate repeated questions are weak proxies for long-term behaviour; the visible answers may also encourage recall. They do not demonstrate reduced phishing incidents or genuine staff adoption.

## Executed technical checks

On 27 October 2026, PHP 8.3.33 WebAssembly with SQLite passed:
- 11 PHP syntax checks;
- 70 validation/repository tests;
- 26 simulated HTTP checks, including both unauthenticated campaign-route redirects, signed-in rendering of both routes and rejection of a POST to the read-only guide.

The JSON records are in `evidence/`. This is simulation evidence; XAMPP/MySQL import, Apache protections, genuine browser print layout and campaign evaluation remain separate checks.
