<?php
// Register endpoint
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/util.php';

$in = get_json_input();
$name = isset($in['name']) ? trim($in['name']) : '';
$email = isset($in['email']) ? trim(strtolower($in['email'])) : '';
$password = $in['password'] ?? '';
$age = isset($in['age']) && $in['age'] !== '' ? (int)$in['age'] : null;
$dob = isset($in['dob']) && $in['dob'] !== '' ? $in['dob'] : null;
$contact = isset($in['contact']) && $in['contact'] !== '' ? trim($in['contact']) : null;

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
    json_response(false, 'Invalid input: ensure name/email are valid and password length >= 6');
    exit;
}

try {
    $pdo = getPDO();
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password, age, dob, contact) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$name, $email, $hash, $age, $dob, $contact]);
    json_response(true, 'Registered successfully');
} catch (PDOException $e) {
    if ((int)$e->getCode() === 23000 || str_contains($e->getMessage(), 'Duplicate')) {
        json_response(false, 'Email already registered');
    } else {
        json_response(false, 'Database error: ' . $e->getMessage());
    }
}
