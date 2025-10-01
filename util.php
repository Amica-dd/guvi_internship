<?php
// Simple helpers for JSON responses and token generation
function json_response(bool $success, string $message = '', array $data = [], ?string $code = null): void {
    header('Content-Type: application/json');
    $out = ['success' => $success, 'message' => $message, 'data' => $data];
    if ($code !== null) $out['code'] = $code;
    echo json_encode($out);
}

function get_json_input(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function generate_token(): string {
    return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
}
