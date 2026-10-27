<?php
declare(strict_types=1);

function security_campaign(bool $card = false): void {
    if ($card) { ?>
<p class="no-print">Use your browser's Print command to print this card or save it as a PDF. <a href="index.php?page=security">Return to security guidance</a>.</p>
<article class="panel campaign-card">
  <h2>Pause, verify, record</h2>
  <p>For fleet administrators and staff handling vehicle renewals or driver records.</p>
  <ol>
    <li><strong>Pause before acting on a renewal message.</strong> Unexpected urgency, a password request or changed bank details needs a separate check. Do not use the message's link or telephone number to verify it.</li>
    <li><strong>Verify through a trusted route.</strong> Contact the insurer or supplier using an existing policy, saved contact or a website address you already know. Refer uncertain requests to the responsible manager or IT contact.</li>
    <li><strong>Record dates from the original evidence.</strong> Check the vehicle and policy details before updating Fleet Desk. A green date label only reflects the date entered; it does not authenticate insurance, an MOT or a licence.</li>
    <li><strong>Protect the records.</strong> Use your own administrator account, sign out on shared devices and keep passwords private. Remove driver names, licence numbers, contact details and credentials before sharing project screenshots.</li>
  </ol>
  <h2>Three quick scenarios</h2>
  <p><strong>1.</strong> A renewal email demands payment to a new bank account today. How will you check it?</p>
  <p><strong>2.</strong> A vehicle has a green insurance date in the dashboard. What must you check before relying on it?</p>
  <p><strong>3.</strong> A colleague needs a screenshot of a fault. What information should you remove?</p>
  <p><strong>Discuss:</strong> use an independently trusted contact; inspect the original policy and correct vehicle; redact personal records and all credentials. These checks support judgement and do not certify compliance.</p>
</article>
<?php return; } ?>
<div class="toolbar"><p>Use these checks when handling renewals and updating vehicle records.</p><a class="button" href="index.php?page=security_card">Open printable briefing card</a></div>
<section class="panel"><h2>Check an unexpected renewal request</h2><p>Pause when a message demands urgent payment, requests a password or changes a supplier's bank details. Check through a contact route from an existing policy or trusted supplier record. Do not rely on the message's own link or phone number.</p><p>If the request remains uncertain, keep the message and refer it to the responsible manager or IT contact before acting.</p></section>
<section class="panel"><h2>Use the original vehicle evidence</h2><p>Read the original policy, MOT or licence record before entering an expiry date. Confirm the vehicle or person, the date and the relevant document details. Correct a mistake through the normal record-editing process.</p><p>Fleet Desk calculates reminders from manually entered dates. A green label does not authenticate a document or confirm that a vehicle is legally compliant.</p></section>
<section class="panel"><h2>Protect accounts and driver information</h2><p>Use a unique password for your own administrator account. Protect the associated email account with multi-factor authentication where available. Sign out after using a shared device.</p><p>Before sending a fault screenshot, remove driver contact details, licence numbers, passwords and other personal information. Use fictional demonstration records when explaining the project.</p></section>
<p class="muted">Further guidance: <a href="https://www.ncsc.gov.uk/collection/small-organisations-guide-to-cyber-security" target="_blank" rel="noopener noreferrer">NCSC cyber security guidance for small organisations</a>.</p>
<?php }
