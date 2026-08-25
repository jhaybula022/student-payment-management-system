<?php
declare(strict_types=1);
$host='127.0.0.1'; $db='bestlink_student_system'; $user='root'; $pass='';
header('Content-Type:text/plain; charset=utf-8');
try {
  $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
  $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
  $pdo->exec("USE `$db`");
  $pdo->exec("CREATE TABLE IF NOT EXISTS users(
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    student_id VARCHAR(50),
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $pdo->exec("CREATE TABLE IF NOT EXISTS payments(
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method VARCHAR(100) NOT NULL,
    reference_number VARCHAR(100) NOT NULL,
    proof_file_name VARCHAR(255),
    status VARCHAR(30) NOT NULL DEFAULT 'Pending Verification',
    verified_by INT UNSIGNED NULL,
    verified_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pay_user (user_id), INDEX idx_pay_status(status), INDEX idx_pay_reference(reference_number)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $columns = [
    'user_id'=>"ALTER TABLE payments ADD COLUMN user_id INT UNSIGNED NOT NULL AFTER id",
    'amount'=>"ALTER TABLE payments ADD COLUMN amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER user_id",
    'payment_method'=>"ALTER TABLE payments ADD COLUMN payment_method VARCHAR(100) NOT NULL DEFAULT '' AFTER amount",
    'reference_number'=>"ALTER TABLE payments ADD COLUMN reference_number VARCHAR(100) NOT NULL DEFAULT '' AFTER payment_method",
    'proof_file_name'=>"ALTER TABLE payments ADD COLUMN proof_file_name VARCHAR(255) NULL AFTER reference_number",
    'status'=>"ALTER TABLE payments ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'Pending Verification' AFTER proof_file_name",
    'verified_by'=>"ALTER TABLE payments ADD COLUMN verified_by INT UNSIGNED NULL AFTER status",
    'verified_at'=>"ALTER TABLE payments ADD COLUMN verified_at DATETIME NULL AFTER verified_by",
    'created_at'=>"ALTER TABLE payments ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER verified_at"
  ];
  $check=$pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME=?");
  foreach($columns as $name=>$sql){$check->execute([$name]);if(!(int)$check->fetchColumn())$pdo->exec($sql);}

  $checkUser=$pdo->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
  $ins=$pdo->prepare("INSERT INTO users(username,password_hash,full_name,student_id,role,status) VALUES(?,?,?,?,?, 'Active')");
  $demo=[['admin','admin123','System Administrator',null,'admin'],['student','123456','Student User','BLCP-2026-0001','user']];
  foreach($demo as $u){$checkUser->execute([$u[0]]);if(!$checkUser->fetch())$ins->execute([$u[0],password_hash($u[1],PASSWORD_DEFAULT),$u[2],$u[3],$u[4]]);}
  echo "SETUP / DATABASE REPAIR COMPLETE\n\nAdmin: admin / admin123\nStudent: student / 123456\n\nExisting payment records were preserved.\nYou can now test payment submission and admin verification.\n\nFor security, delete setup.php after setup.\n";
} catch(Throwable $e) { http_response_code(500); echo "SETUP ERROR: ".$e->getMessage(); }
