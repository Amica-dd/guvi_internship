<?php
// Login endpoint: verifies credentials and issues Redis-backed token.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/util.php';
require_once __DIR__ . '/redis.php';

$in = get_json_input();
$email = isset($in['email']) ? trim(strtolower($in['email'])) : '';
$password = $in['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    json_response(false, 'Invalid email or password');
    exit;
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT id, name, email, password, age, dob, contact FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        json_response(false, 'Invalid credentials');
        exit;
    }

    $token = generate_token();
    redis_set_token($token, (int)$user['id'], 3600); // 1 hour TTL

    unset($user['password']);
    json_response(true, 'Login successful', ['token' => $token, 'user' => $user]);
} catch (PDOException $e) {
    json_response(false, 'Database error: ' . $e->getMessage());
}
