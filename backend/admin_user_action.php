<?php
require __DIR__.'/config.php';
require_admin();

$in=json_decode(file_get_contents('php://input'),true) ?: [];
$action=(string)($in['action']??'');

try {
  if($action==='create'){
    $username=trim((string)($in['username']??''));
    $full=trim((string)($in['full_name']??''));
    $student=trim((string)($in['student_id']??''));
    $password=(string)($in['password']??'');
    $status=(string)($in['status']??'Active');

    if($username===''||$full===''||strlen($password)<6){
      http_response_code(422); echo json_encode(['success'=>false,'message'=>'Username, full name, and a password of at least 6 characters are required.']); exit;
    }
    if(!in_array($status,['Active','Inactive'],true)) $status='Active';

    $q=$pdo->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
    $q->execute([$username]);
    if($q->fetch()){
      http_response_code(409); echo json_encode(['success'=>false,'message'=>'Username is already registered.']); exit;
    }

    $q=$pdo->prepare("INSERT INTO users(username,password_hash,full_name,student_id,role,status) VALUES(?,?,?,?, 'user', ?)");
    $q->execute([$username,password_hash($password,PASSWORD_DEFAULT),$full,$student,$status]);
    echo json_encode(['success'=>true,'message'=>'Student account created.','id'=>(int)$pdo->lastInsertId()]); exit;
  }

  $id=(int)($in['id']??0);
  if($id<1){http_response_code(422);echo json_encode(['success'=>false,'message'=>'Invalid student ID.']);exit;}

  if($action==='update'){
    $username=trim((string)($in['username']??''));
    $full=trim((string)($in['full_name']??''));
    $student=trim((string)($in['student_id']??''));
    $status=(string)($in['status']??'Active');

    if($username===''||$full===''||!in_array($status,['Active','Inactive'],true)){
      http_response_code(422);echo json_encode(['success'=>false,'message'=>'Invalid student information.']);exit;
    }

    $q=$pdo->prepare("SELECT id FROM users WHERE username=? AND id<>? LIMIT 1");
    $q->execute([$username,$id]);
    if($q->fetch()){http_response_code(409);echo json_encode(['success'=>false,'message'=>'Username is already used by another account.']);exit;}

    $q=$pdo->prepare("UPDATE users SET username=?,full_name=?,student_id=?,status=? WHERE id=? AND role='user'");
    $q->execute([$username,$full,$student,$status,$id]);

    if(trim((string)($in['password']??''))!==''){
      $pw=(string)$in['password'];
      if(strlen($pw)<6){http_response_code(422);echo json_encode(['success'=>false,'message'=>'Password must be at least 6 characters.']);exit;}
      $q=$pdo->prepare("UPDATE users SET password_hash=? WHERE id=? AND role='user'");
      $q->execute([password_hash($pw,PASSWORD_DEFAULT),$id]);
    }
    echo json_encode(['success'=>true,'message'=>'Student account updated.']); exit;
  }

  if($action==='reset_password'){
    $pw=(string)($in['password']??'');
    if(strlen($pw)<6){http_response_code(422);echo json_encode(['success'=>false,'message'=>'Password must be at least 6 characters.']);exit;}
    $q=$pdo->prepare("UPDATE users SET password_hash=? WHERE id=? AND role='user'");
    $q->execute([password_hash($pw,PASSWORD_DEFAULT),$id]);
    echo json_encode(['success'=>true,'message'=>'Password reset successfully.']); exit;
  }

  if($action==='activate' || $action==='deactivate'){
    $status=$action==='activate'?'Active':'Inactive';
    $q=$pdo->prepare("UPDATE users SET status=? WHERE id=? AND role='user'");
    $q->execute([$status,$id]);
    echo json_encode(['success'=>true,'message'=>'Account status updated.']); exit;
  }

  if($action==='delete'){
    $q=$pdo->prepare("SELECT id, username, full_name FROM users WHERE id=? AND role='user' LIMIT 1");
    $q->execute([$id]);
    $studentRow=$q->fetch();
    if(!$studentRow){
      http_response_code(404);
      echo json_encode(['success'=>false,'message'=>'Student account not found or cannot be deleted.']); exit;
    }

    $pdo->beginTransaction();
    try {
      // Remove the student's payment records first so the account can be deleted
      // cleanly without leaving orphaned payment rows in the verification center.
      $q=$pdo->prepare("DELETE FROM payments WHERE user_id=?");
      $q->execute([$id]);

      $q=$pdo->prepare("DELETE FROM users WHERE id=? AND role='user'");
      $q->execute([$id]);

      if($q->rowCount()!==1){
        throw new RuntimeException('Student account could not be deleted.');
      }

      $pdo->commit();
      echo json_encode([
        'success'=>true,
        'message'=>'Student account and its payment records were deleted.',
        'id'=>$id,
        'username'=>$studentRow['username']
      ]); exit;
    } catch(Throwable $deleteError) {
      if($pdo->inTransaction()) $pdo->rollBack();
      throw $deleteError;
    }
  }

  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>'Unknown admin action.']);
} catch(PDOException $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Database error while updating student account.']);
}
