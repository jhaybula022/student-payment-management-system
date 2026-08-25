<?php
require __DIR__.'/config.php';
$s = require_login();
ensure_payments_table($pdo);

$q = $pdo->prepare("SELECT id, amount, payment_method, reference_number, proof_file_name, status, created_at, verified_at
                    FROM payments WHERE user_id=? ORDER BY created_at DESC");
$q->execute([(int)$s['user_id']]);

$payments = $q->fetchAll();

// Student account computation. The capstone currently uses a fixed assessment
// of PHP 44,500.00. Submitted payments are included in progress immediately;
// admin verification still controls the official collected amount.
$totalAssessment = 44500.00;
$demoPaid = 25000.00;
$submittedPaid = array_reduce($payments, function($sum, $p){ return $sum + (($p['status'] ?? '') !== 'Rejected' ? (float)$p['amount'] : 0.0); }, 0.0);
$totalPaid = min($totalAssessment, $demoPaid + $submittedPaid);
$outstanding = max(0, $totalAssessment - $totalPaid);
$progress = $totalAssessment > 0 ? min(100, ($totalPaid / $totalAssessment) * 100) : 0;
$verifiedPaid = $demoPaid;
foreach ($payments as $p) {
  if (($p['status'] ?? '') === 'Verified') $verifiedPaid += (float)$p['amount'];
}
$verifiedPaid = min($totalAssessment, $verifiedPaid);

echo json_encode([
  'success'=>true,
  'payments'=>$payments,
  'summary'=>[
    'total_assessment'=>$totalAssessment,
    'total_paid'=>$totalPaid,
    'verified_paid'=>min($totalAssessment, $verifiedPaid),
    'outstanding'=>$outstanding,
    'progress'=>round($progress, 2)
  ]
]);
