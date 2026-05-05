<?php
/**
 * WebSocket daemon for PharmaCare Plus realtime updates.
 *
 *   Browsers connect to:    ws://<host>:8080/?token=<HMAC_TOKEN>
 *   PHP web app publishes:  TCP 127.0.0.1:8081 (line-delimited JSON)
 *
 * Each browser connection is bound to ONE pharmacy_id (decoded from the
 * signed token). Events broadcast to a pharmacy_id are delivered only to
 * sockets in that pharmacy's room — strict tenant isolation.
 *
 * Usage:
 *     php bin/ws-server.php
 *
 * Env vars (optional; sane defaults provided):
 *     WS_SECRET     HMAC secret shared with the web app    [dev-ws-secret-change-me]
 *     WS_PORT       Public WebSocket port                   [8080]
 *     WS_TCP_PORT   Internal publish port                   [8081]
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\MessageComponentInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\Socket\SocketServer;

class PharmacyHub implements MessageComponentInterface {
    /** @var SplObjectStorage[] keyed by pharmacy_id */
    private $rooms = [];
    private $secret;

    public function __construct($secret) {
        $this->secret = $secret;
    }

    public function onOpen(ConnectionInterface $conn) {
        // Tokens come in via ?token=... on the upgrade request
        $req   = $conn->httpRequest;
        $query = [];
        parse_str($req->getUri()->getQuery(), $query);
        $payload = $this->verifyToken($query['token'] ?? '');
        if (!$payload) {
            echo "[!] rejected: bad/expired token\n";
            $conn->send(json_encode(['event' => 'error', 'data' => 'unauthorized']));
            $conn->close();
            return;
        }

        $conn->pharmacyId = (int)$payload['pid'];
        $conn->userId     = (int)$payload['uid'];
        $conn->username   = $payload['username'] ?? '';

        if (!isset($this->rooms[$conn->pharmacyId])) {
            $this->rooms[$conn->pharmacyId] = new SplObjectStorage();
        }
        $this->rooms[$conn->pharmacyId]->attach($conn);

        $conn->send(json_encode([
            'event' => 'hello',
            'data'  => ['pharmacy_id' => $conn->pharmacyId, 'user' => $conn->username],
        ]));
        echo "[+] connect  pharmacy={$conn->pharmacyId} user={$conn->username} clients=" . count($this->rooms[$conn->pharmacyId]) . "\n";
    }

    public function onClose(ConnectionInterface $conn) {
        if (!isset($conn->pharmacyId)) return;
        $pid = $conn->pharmacyId;
        if (isset($this->rooms[$pid])) {
            $this->rooms[$pid]->detach($conn);
            if (count($this->rooms[$pid]) === 0) unset($this->rooms[$pid]);
        }
        echo "[-] disconnect pharmacy={$pid} user={$conn->username}\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "[x] error: " . $e->getMessage() . "\n";
        $conn->close();
    }

    public function onMessage(ConnectionInterface $conn, $msg) {
        // Browsers don't send anything meaningful — server-push only.
        // Could implement client->client features later (e.g. typing indicators).
    }

    /**
     * Broadcast to every browser bound to this pharmacy.
     * Called from the internal TCP listener.
     */
    public function broadcast($pharmacyId, $event, $data) {
        $pharmacyId = (int)$pharmacyId;
        if (!isset($this->rooms[$pharmacyId])) return 0;
        $msg = json_encode(['event' => $event, 'data' => $data, 'ts' => time()]);
        $count = 0;
        foreach ($this->rooms[$pharmacyId] as $client) {
            $client->send($msg);
            $count++;
        }
        return $count;
    }

    private function verifyToken($token) {
        if (!$token || strpos($token, '.') === false) return null;
        [$body, $sig] = explode('.', $token, 2);
        $expected = hash_hmac('sha256', $body, $this->secret);
        if (!hash_equals($expected, $sig)) return null;
        $json = base64_decode(strtr($body, '-_', '+/'));
        $payload = $json ? json_decode($json, true) : null;
        if (!is_array($payload) || empty($payload['exp']) || $payload['exp'] < time()) return null;
        return $payload;
    }
}

$secret  = getenv('WS_SECRET')   ?: 'dev-ws-secret-change-me';
$wsPort  = (int)(getenv('WS_PORT')     ?: 8080);
$tcpPort = (int)(getenv('WS_TCP_PORT') ?: 8081);

$loop = Loop::get();
$hub  = new PharmacyHub($secret);

// Public WebSocket server for browsers
new IoServer(
    new HttpServer(new WsServer($hub)),
    new SocketServer("0.0.0.0:$wsPort", [], $loop),
    $loop
);

// Internal publish bridge: PHP web app connects, writes a JSON line, hangs up.
// Bound to localhost only — never expose this port externally.
$tcp = new SocketServer("127.0.0.1:$tcpPort", [], $loop);
$tcp->on('connection', function ($conn) use ($hub) {
    $buffer = '';
    $conn->on('data', function ($data) use (&$buffer, $conn, $hub) {
        $buffer .= $data;
        while (false !== ($pos = strpos($buffer, "\n"))) {
            $line   = trim(substr($buffer, 0, $pos));
            $buffer = substr($buffer, $pos + 1);
            if ($line === '') continue;
            $msg = json_decode($line, true);
            if (!is_array($msg) || empty($msg['pharmacy_id']) || empty($msg['event'])) continue;
            $sent = $hub->broadcast($msg['pharmacy_id'], $msg['event'], $msg['data'] ?? []);
            echo "[*] event={$msg['event']} pharmacy={$msg['pharmacy_id']} delivered={$sent}\n";
        }
    });
    $conn->on('end',   function () use ($conn) { $conn->close(); });
    $conn->on('error', function () use ($conn) { $conn->close(); });
});

echo "PharmaCare WS daemon\n";
echo "  Browser endpoint: ws://0.0.0.0:$wsPort/?token=<JWT-like>\n";
echo "  Publish bridge:   tcp://127.0.0.1:$tcpPort  (line-delimited JSON)\n";
echo "  Secret length:    " . strlen($secret) . " bytes\n";
echo "Ready. Ctrl+C to stop.\n\n";

$loop->run();
