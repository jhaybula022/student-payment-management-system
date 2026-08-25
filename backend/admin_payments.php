<?php
require __DIR__.'/config.php'; require_admin();
ensure_payments_table($pdo);
$q=$pdo->query("SELECT p.id,p.amount,p.payment_method,p.reference_number,p.proof_file_name,p.status,p.created_at,p.verified_at,u.username,u.full_name,u.student_id
FROM payments p INNER JOIN users u ON u.id=p.user_id ORDER BY p.created_at DESC");
echo json_encode(['success'=>true,'payments'=>$q->fetchAll()]);
