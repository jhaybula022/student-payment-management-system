<?php
require __DIR__.'/config.php'; require_admin();
$q=$pdo->query("SELECT id,username,full_name,student_id,role,status,created_at FROM users ORDER BY id DESC");
echo json_encode(['success'=>true,'users'=>$q->fetchAll()]);
