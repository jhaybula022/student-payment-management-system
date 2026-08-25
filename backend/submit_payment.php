<?php
require __DIR__.'/config.php';
$s = require_login();

try {
  ensure_payments_table($pdo);
  $in = json_decode(file_get_contents('php://input'), true) ?: [];
  $amount = (float)($in['amount'] ?? 0);
  $method = trim((string)($in['payment_method'] ?? ''));
  $ref = trim((string)($in['reference_number'] ?? ''));
  $proof = trim((string)($in['proof_file_name'] ?? ''));

  if ($amount <= 0 || $method === '' || $ref === '') {
    http_response_code(422);
    echo json_encode(['success'=>false,'message'=>'Amount, payment method, and reference number are required.']);
    exit;
  }

  $q = $pdo->prepare("INSERT INTO payments (user_id, amount, payment_method, reference_number, proof_file_name, status) VALUES (?, ?, ?, ?, ?, 'Pending Verification')");
  $q->execute([(int)$s['user_id'], $amount, $method, $ref, $proof]);

  echo json_encode([
    'success'=>true,
    'message'=>'Payment submitted and is pending admin verification.',
    'payment_id'=>(int)$pdo->lastInsertId(),
    'status'=>'Pending Verification'
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  error_log('submit_payment.php: '.$e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Payment could not be saved. Database error: '.$e->getMessage()]);
}
