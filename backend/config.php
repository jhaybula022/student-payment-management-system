<?php
declare(strict_types=1);
session_start();

$host='127.0.0.1';
$db='bestlink_student_system';
$user='root';
$pass='';
$charset='utf8mb4';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
  $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset",$user,$pass,[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
  ]);
} catch(Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Database connection failed. Start MySQL and run setup.php.']);
  exit;
}
function require_login(): array {
  if(empty($_SESSION['user_id'])){
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Please log in first.']); exit;
  }
  return $_SESSION;
}

function ensure_payments_table(PDO $pdo): void {
  $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method VARCHAR(100) NOT NULL,
    reference_number VARCHAR(100) NOT NULL,
    proof_file_name VARCHAR(255) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Pending Verification',
    verified_by INT UNSIGNED NULL,
    verified_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pay_user (user_id),
    INDEX idx_pay_status (status),
    INDEX idx_pay_reference (reference_number)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $columns = [
    'user_id' => "ALTER TABLE payments ADD COLUMN user_id INT UNSIGNED NOT NULL AFTER id",
    'amount' => "ALTER TABLE payments ADD COLUMN amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER user_id",
    'payment_method' => "ALTER TABLE payments ADD COLUMN payment_method VARCHAR(100) NOT NULL DEFAULT '' AFTER amount",
    'reference_number' => "ALTER TABLE payments ADD COLUMN reference_number VARCHAR(100) NOT NULL DEFAULT '' AFTER payment_method",
    'proof_file_name' => "ALTER TABLE payments ADD COLUMN proof_file_name VARCHAR(255) NULL AFTER reference_number",
    'status' => "ALTER TABLE payments ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'Pending Verification' AFTER proof_file_name",
    'verified_by' => "ALTER TABLE payments ADD COLUMN verified_by INT UNSIGNED NULL AFTER status",
    'verified_at' => "ALTER TABLE payments ADD COLUMN verified_at DATETIME NULL AFTER verified_by",
    'created_at' => "ALTER TABLE payments ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER verified_at"
  ];

  $check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = ?");
  foreach ($columns as $name => $sql) {
    $check->execute([$name]);
    if (!(int)$check->fetchColumn()) {
      $pdo->exec($sql);
    }
  }
}

function require_admin(): array {
  $s=require_login();
  if(($s['role']??'user')!=='admin'){
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Admin access required.']); exit;
  }
  return $s;
}
