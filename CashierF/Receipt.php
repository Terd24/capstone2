<?php
session_start();
require_once '../StudentLogin/db_conn.php';

// Require cashier (or allow after login via link)
if (!isset($_SESSION['role'])) {
  // still allow viewing if a valid OR is provided (for printing), but you can restrict if needed
}

$or = isset($_GET['or']) ? trim($_GET['or']) : '';
if ($or === '') {
  http_response_code(400);
  echo 'Missing OR number';
  exit;
}

// Fetch payment + student info
$stmt = $conn->prepare("SELECT p.id_number, p.school_year_term, p.fee_type, p.amount, p.payment_method, p.or_number, p.date,
                               sa.first_name, sa.last_name, sa.academic_track AS program, sa.grade_level AS year_section
                        FROM student_payments p
                        JOIN student_account sa ON sa.id_number = p.id_number
                        WHERE p.or_number = ?");
$stmt->bind_param('s', $or);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
  http_response_code(404);
  echo 'Receipt not found';
  exit;
}
$row = $res->fetch_assoc();
$stmt->close();

$studentName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
$dateStr = date('d/m/y', strtotime($row['date']));
// Display only the numeric part of the OR for the printed number label
$orNumberDigits = preg_replace('/\D+/', '', (string)$row['or_number']);
$cashierName = $_SESSION['cashier_name'] ?? 'Cashier';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Official Receipt <?= htmlspecialchars($or) ?></title>
  <link rel="icon" href="../images/LogoCCI.png" type="image/png">
  <style>
    body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f8fafc; color: #111827; }
    .sheet { width: 816px; /* 8.5in minus margins */ max-width: 100%; margin: 24px auto; background: #fff; border: 1px solid #d1d5db; position: relative; }
    .perfs { position: absolute; top: 0; bottom: 0; width: 12px; background: repeating-linear-gradient(#fff 0 10px, #e5e7eb 10px 12px); }
    .perfs.left { left: -12px; border-right: 1px dashed #d1d5db; }
    .perfs.right { right: -12px; border-left: 1px dashed #d1d5db; }
    .header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 2px solid #111827; }
    .school { display: flex; align-items: center; gap: 12px; }
    .school img { width: 56px; height: 56px; }
    .school .name { font-weight: 700; font-size: 18px; text-transform: uppercase; }
    .school .addr { font-size: 12px; color: #374151; }
    .label-box { border: 2px solid #111827; padding: 8px 12px; font-weight: 700; }
    .or-box { border: 2px solid #111827; display: flex; align-items: center; gap: 12px; padding: 6px 10px; }
    .or-tag { font-weight: 700; }
    .or-num { font-weight: 800; font-size: 22px; color: #dc2626; letter-spacing: 1px; padding: 0 2px; }
    .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 16px; padding: 12px 20px; }
    .meta .col { display: grid; grid-auto-rows: min-content; gap: 6px; }
    .meta .field { display: grid; grid-template-columns: 120px 1fr; column-gap: 8px; font-size: 14px; }
    .meta .label { color: #374151; }
    .section-title { padding: 8px 20px; font-weight: 700; font-size: 14px; color: #111827; }
    .particulars { margin: 0 20px 12px; border: 1px solid #d1d5db; }
    .particulars table { width: 100%; border-collapse: collapse; }
    .particulars th, .particulars td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
    .particulars th { background: #f3f4f6; text-align: left; font-size: 13px; }
    .amount { text-align: right; white-space: nowrap; }
    .footer { display: flex; justify-content: space-between; align-items: flex-end; padding: 16px 20px 24px; }
    .sign { text-align: left; }
    .sign .line { margin-top: 36px; border-top: 1px solid #111827; width: 240px; }
    .badge { border: 1px solid #111827; font-weight: 700; padding: 2px 6px; border-radius: 6px; font-size: 12px; }
    .actions { display: flex; gap: 8px; padding: 12px 20px; }
    .btn { background: #0B2C62; color: #fff; border: 0; padding: 10px 14px; border-radius: 8px; cursor: pointer; }
    .btn.secondary { background: #6b7280; }
    .watermark { position: absolute; left: 50%; top: 52%; transform: translate(-50%,-50%); opacity: 0.06; pointer-events: none; }
    .watermark img { width: 340px; height: 340px; filter: grayscale(1); }
    @media print {
      body { background: #fff; }
      .actions { display: none; }
      /* Set to receipt width on print */
      .sheet { width: 190mm; margin: 0; border: none; }
      .perfs { display: none; }
      .header { border-bottom: 1px solid #111827; padding: 8px 12px; }
      .meta { padding: 8px 12px; gap: 4px 8px; }
      .particulars th, .particulars td { padding: 6px 8px; }
      .footer { padding: 12px; }
      .or-num { font-size: 18px; }
      .school img { width: 36px; height: 36px; }
      .school .name { font-size: 14px; }
      @page { size: 120mm auto; margin: 5mm; }
    }
  </style>
</head>
<body>
  <div class="sheet">
    <div class="perfs left"></div>
    <div class="perfs right"></div>
    <div class="watermark"><img src="../images/LogoCCI.png" alt="CCI"></div>

    <div class="header">
      <div class="school">
        <img src="../images/LogoCCI.png" alt="CCI" />
        <div>
          <div class="name">Cornerstone College Inc.</div>
          <div class="addr">190 Libis II, SJDM, Bulacan</div>
        </div>
      </div>
      <div class="label-box">OFFICIAL RECEIPT</div>
      <div class="or-box">
        <div class="or-tag">No.</div>
        <div class="or-num"><?= htmlspecialchars($orNumberDigits) ?></div>
      </div>
    </div>

    <div class="meta">
      <div class="col">
        <div class="field"><div class="label">Student Name:</div><div><?= htmlspecialchars($studentName) ?></div></div>
        <div class="field"><div class="label">Student ID No.:</div><div><?= htmlspecialchars($row['id_number']) ?></div></div>
        <div class="field"><div class="label">Course/Program:</div><div><?= htmlspecialchars($row['program'] ?? 'N/A') ?></div></div>
        <div class="field"><div class="label">Year & Section:</div><div><?= htmlspecialchars($row['year_section'] ?? 'N/A') ?></div></div>
      </div>
      <div class="col">
        <div class="field"><div class="label">Date:</div><div><?= htmlspecialchars($dateStr) ?></div></div>
        <div class="field"><div class="label">S.Y. / Term:</div><div><?= htmlspecialchars($row['school_year_term']) ?></div></div>
      </div>
    </div>

    <div class="section-title">PARTICULARS</div>
    <div class="particulars">
      <table>
        <thead>
          <tr>
            <th style="width:70%">Description</th>
            <th style="width:30%" class="amount">Amount</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><?= htmlspecialchars($row['fee_type']) ?> — <?= htmlspecialchars($row['payment_method']) ?></td>
            <td class="amount">₱<?= number_format((float)$row['amount'], 2) ?></td>
          </tr>
          <tr>
            <td></td>
            <td style="font-weight:700; padding:10px 12px;">
              <div style="display:flex; justify-content:space-between; align-items:center;">
                <span>Total</span>
                <span>₱<?= number_format((float)$row['amount'], 2) ?></span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="footer">
      <div class="sign">
        <div style="font-size:12px;">Teller: <?= htmlspecialchars($cashierName) ?></div>
      </div>
    </div>

    <div class="actions">
      <button class="btn" onclick="window.print()">Print / Save as PDF</button>
      <button class="btn secondary" onclick="window.close()">Close</button>
    </div>
  </div>
</body>
</html>
