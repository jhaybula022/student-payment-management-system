<?php
require __DIR__.'/config.php';
$in=json_decode(file_get_contents('php://input'),true) ?: [];
$username=trim((string)($in['username']??''));
$password=(string)($in['password']??'');
if($username===''||$password===''){http_response_code(422);echo json_encode(['success'=>false,'message'=>'Username and password are required.']);exit;}
$q=$pdo->prepare("SELECT id,username,password_hash,full_name,student_id,role,status FROM users WHERE username=? LIMIT 1");
$q->execute([$username]); $u=$q->fetch();
if(!$u || $u['status']!=='Active' || !password_verify($password,$u['password_hash'])){
  http_response_code(401); echo json_encode(['success'=>false,'message'=>'Invalid username or password.']); exit;
}
session_regenerate_id(true);
$_SESSION['user_id']=(int)$u['id']; $_SESSION['username']=$u['username']; $_SESSION['full_name']=$u['full_name']; $_SESSION['student_id']=$u['student_id']; $_SESSION['role']=$u['role'];
echo json_encode(['success'=>true,'user'=>['id'=>(int)$u['id'],'username'=>$u['username'],'full_name'=>$u['full_name'],'student_id'=>$u['student_id'],'role'=>$u['role']]]);
