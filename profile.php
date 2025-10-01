<?php
// Profile endpoint: get/update profile based on Redis session token from localStorage
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/util.php';
require_once __DIR__ . '/redis.php';

$action = $_GET['action'] ?? null; // 'get' | 'update' | 'logout'
$in = get_json_input();
$token = $in['token'] ?? '';

if ($action !== 'logout') {
    if ($token === '') {
        json_response(false, 'Missing token', [], 'AUTH');
        exit;
    }

    $uid = redis_get_user_id($token);
    if (!$uid) {
        json_response(false, 'Invalid or expired session', [], 'AUTH');
        exit;
    }
}

try {
    $pdo = getPDO();

    if ($action === 'get') {
        $stmt = $pdo->prepare('SELECT id, name, email, age, dob, contact FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([redis_get_user_id($token)]);
        $user = $stmt->fetch();
        json_response(true, '', ['user' => $user]);
        exit;
    }

    if ($action === 'update') {
        $age = isset($in['age']) && $in['age'] !== '' ? (int)$in['age'] : null;
        $dob = isset($in['dob']) && $in['dob'] !== '' ? $in['dob'] : null;
        $contact = isset($in['contact']) && $in['contact'] !== '' ? trim($in['contact']) : null;

        $stmt = $pdo->prepare('UPDATE users SET age = ?, dob = ?, contact = ? WHERE id = ?');
        $stmt->execute([$age, $dob, $contact, redis_get_user_id($token)]);

        $stmt2 = $pdo->prepare('SELECT id, name, email, age, dob, contact FROM users WHERE id = ? LIMIT 1');
        $stmt2->execute([redis_get_user_id($token)]);
        $user = $stmt2->fetch();
        json_response(true, 'Profile updated', ['user' => $user]);
        exit;
    }

    if ($action === 'logout') {
        if ($token) {
            redis_del_token($token);
        }
        json_response(true, 'Logged out');
        exit;
    }

    json_response(false, 'Unknown action');
} catch (PDOException $e) {
    json_response(false, 'Database error: ' . $e->getMessage());
}
