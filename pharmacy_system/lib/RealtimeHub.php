<?php
/**
 * RealtimeHub — PHP web app side of the WebSocket pipeline.
 *
 *   - signToken()  produces a short-lived HMAC token that the browser
 *                  passes to the WS daemon to bind the connection to
 *                  the current user's pharmacy.
 *   - publish()    opens a 1-shot TCP connection to the WS daemon's
 *                  publish bridge (127.0.0.1:8081) and emits a JSON
 *                  event line. Best-effort: if the daemon is offline,
 *                  the request continues silently.
 *
 * Tenant isolation is enforced two ways:
 *   - Tokens carry pharmacy_id; the daemon binds the socket to that
 *     room and never delivers cross-tenant events.
 *   - publish() requires an explicit pharmacy_id; controllers pass
 *     currentPharmacyId() so events can never leak.
 */

class RealtimeHub {

    /** Issue a signed token for the logged-in user. */
    public static function signToken() {
        if (!function_exists('isLoggedIn') || !isLoggedIn()) return null;

        $payload = [
            'pid'      => (int)($_SESSION['pharmacy_id'] ?? 0),
            'uid'      => (int)($_SESSION['user_id'] ?? 0),
            'username' => $_SESSION['username'] ?? '',
            'exp'      => time() + 3600, // 1 hour
        ];
        $body = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        $sig  = hash_hmac('sha256', $body, self::secret());
        return "$body.$sig";
    }

    /**
     * Publish an event to a specific pharmacy.
     * Falls back silently if the daemon isn't reachable — the web request
     * must never break just because real-time is offline.
     */
    public static function publish($pharmacyId, $event, $data = []) {
        $pharmacyId = (int)$pharmacyId;
        if (!$pharmacyId || !$event) return false;

        $host = '127.0.0.1';
        $port = (int)(getenv('WS_TCP_PORT') ?: 8081);
        $msg  = json_encode([
            'pharmacy_id' => $pharmacyId,
            'event'       => $event,
            'data'        => $data,
        ]) . "\n";

        $errno = 0;
        $errstr = '';
        // Short timeout so a dead daemon never stalls the request.
        $sock = @fsockopen($host, $port, $errno, $errstr, 0.3);
        if (!$sock) {
            error_log("RealtimeHub: publish skipped ($errno $errstr)");
            return false;
        }
        @stream_set_timeout($sock, 1);
        @fwrite($sock, $msg);
        @fclose($sock);
        return true;
    }

    /**
     * Public WS URL used by the browser. Override the host via the
     * WS_PUBLIC_URL env var when deploying behind a reverse proxy.
     */
    public static function browserUrl() {
        $base = getenv('WS_PUBLIC_URL') ?: 'ws://' . self::detectHost() . ':' . (getenv('WS_PORT') ?: 8080);
        $tok  = self::signToken();
        return $tok ? "$base/?token=" . rawurlencode($tok) : null;
    }

    private static function detectHost() {
        $h = $_SERVER['HTTP_HOST'] ?? 'localhost';
        // Strip any :port from HTTP_HOST so we can append the WS port cleanly.
        return preg_replace('/:\d+$/', '', $h);
    }

    private static function secret() {
        return getenv('WS_SECRET') ?: 'dev-ws-secret-change-me';
    }
}
