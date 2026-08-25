<?php
require __DIR__.'/config.php'; require_admin();
ensure_payments_table($pdo);
$in=json_decode(file_get_contents('php://input'),true) ?: [];
$id=(int)($in['id']??0); $status=(string)($in['status']??'');
$allowed=['Pending Verification','Verified','Rejected'];
if($id<1||!in_array($status,$allowed,true)){http_response_code(422);echo json_encode(['success'=>false,'message'=>'Invalid payment update.']);exit;}
$q=$pdo->prepare("UPDATE payments SET status=?,verified_by=?,verified_at=NOW() WHERE id=?");
$q->execute([$status,(int)$_SESSION['user_id'],$id]);
echo json_encode(['success'=>true,'message'=>'Payment updated.']);
