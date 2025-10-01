<?php
require_once __DIR__ . '/util.php';

// Minimal pure-PHP Redis client (RESP) fallback
class SimpleRedisClient {
    private string $host;
    private int $port;
    private float $timeout;
    private $socket = null;

    public function __construct(string $host = '127.0.0.1', int $port = 6379, float $timeout = 2.0) {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
        $this->connect();
    }

    private function connect(): void {
        $errno = 0; $errstr = '';
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
        if (!$this->socket) {
            throw new RuntimeException("Redis connect failed: $errstr ($errno)");
        }
        stream_set_timeout($this->socket, (int)ceil($this->timeout));
    }

    private function write(string $data): void {
        $len = strlen($data);
        $sent = 0;
        while ($sent < $len) {
            $n = fwrite($this->socket, substr($data, $sent));
            if ($n === false) throw new RuntimeException('Redis write failed');
            $sent += $n;
        }
    }

    private function readLine(): string {
        $line = fgets($this->socket);
        if ($line === false) throw new RuntimeException('Redis read failed');
        return rtrim($line, "\r\n");
    }

    private function send(array $parts) {
        $cmd = "*" . count($parts) . "\r\n";
        foreach ($parts as $p) {
            $p = (string)$p;
            $cmd .= "$" . strlen($p) . "\r\n$p\r\n";
        }
        $this->write($cmd);
        return $this->parseResponse();
    }

    private function parseResponse() {
        $line = $this->readLine();
        $type = $line[0] ?? '';
        $payload = substr($line, 1);
        switch ($type) {
            case '+':
                return $payload; // simple string
            case '-':
                throw new RuntimeException('Redis error: ' . $payload);
            case ':':
                return (int)$payload; // integer
            case '$':
                $len = (int)$payload;
                if ($len === -1) return null; // nil
                $data = '';
                while (strlen($data) < $len) {
                    $chunk = fread($this->socket, $len - strlen($data));
                    if ($chunk === false) throw new RuntimeException('Redis bulk read failed');
                    $data .= $chunk;
                }
                // consume CRLF
                fread($this->socket, 2);
                return $data;
            case '*':
                $count = (int)$payload;
                $arr = [];
                for ($i = 0; $i < $count; $i++) {
                    $arr[] = $this->parseResponse();
                }
                return $arr;
            default:
                throw new RuntimeException('Redis unknown response: ' . $line);
        }
    }

    // Public ops we need
    public function setex(string $key, int $ttl, string $value) {
        return $this->send(['SETEX', $key, $ttl, $value]);
    }

    public function get(string $key) {
        return $this->send(['GET', $key]);
    }

    public function del($keys) {
        if (is_array($keys)) {
            return $this->send(array_merge(['DEL'], $keys));
        }
        return $this->send(['DEL', $keys]);
    }
}

// Returns an instance of Redis (phpredis), Predis, or SimpleRedisClient
function getRedisClient() {
    static $client = null;
    if ($client) return $client;

    $host = getenv('REDIS_HOST') ?: '127.0.0.1';
    $port = (int)(getenv('REDIS_PORT') ?: 6379);

    if (class_exists('Redis')) {
        $r = new Redis();
        $r->connect($host, $port, 2.0);
        $client = $r;
        return $client;
    }

    $vendor = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($vendor)) {
        require_once $vendor;
        if (class_exists('Predis\\Client')) {
            $client = new Predis\Client([
                'scheme' => 'tcp',
                'host' => $host,
                'port' => $port,
            ]);
            return $client;
        }
    }

    // Fallback to minimal pure-PHP client
    $client = new SimpleRedisClient($host, $port, 2.0);
    return $client;
}

function redis_set_token(string $token, int $user_id, int $ttl = 3600): void {
    $r = getRedisClient();
    if (class_exists('Redis') && $r instanceof Redis) {
        $r->setex("sess:$token", $ttl, (string)$user_id);
    } else {
        $r->setex("sess:$token", $ttl, (string)$user_id);
    }
}

function redis_get_user_id(string $token): ?int {
    $r = getRedisClient();
    $val = $r->get("sess:$token");
    return $val !== false && $val !== null ? (int)$val : null;
}

function redis_del_token(string $token): void {
    $r = getRedisClient();
    if (class_exists('Redis') && $r instanceof Redis) {
        $r->del(["sess:$token"]);
    } else {
        $r->del("sess:$token");
    }
}
