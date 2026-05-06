<?php
/**
 * Tiny dual-tier cache for PharmaCare Plus.
 *
 *   Primary:   filesystem (cache/ directory — always works)
 *   Fast-path: APCu, used opportunistically if the extension is loaded
 *
 * Tenant invalidation is done with a per-pharmacy version counter, NOT a
 * prefix scan. Every cache key for a pharmacy embeds the current version:
 *
 *     Cache::tenantKey(5, 'dashboard:kpis')   ->  'p:5:v3:dashboard:kpis'
 *
 * Calling Cache::bumpPharmacyVersion(5) increments v3 → v4 in one write,
 * which makes every prior cache entry for pharmacy 5 unreachable. Old
 * entries stay on disk until their own TTL expires; that's a deliberate
 * trade-off for O(1) invalidation on a vanilla-PHP stack.
 *
 * Keys may contain any printable ASCII; they're hashed before hitting disk.
 */

class Cache {
    private static $dir;
    private static $apcu;

    public static function init() {
        if (self::$dir !== null) return;
        self::$dir  = __DIR__ . '/../cache';
        self::$apcu = function_exists('apcu_fetch') && function_exists('apcu_store')
                      && (PHP_SAPI !== 'cli' || ini_get('apc.enable_cli'));
        if (!is_dir(self::$dir)) @mkdir(self::$dir, 0775, true);
    }

    public static function get($key) {
        self::init();

        if (self::$apcu) {
            $hit = false;
            $val = apcu_fetch(self::ns($key), $hit);
            if ($hit) return $val;
        }
        $f = self::filePath($key);
        if (!is_file($f)) return null;

        $blob = @file_get_contents($f);
        if ($blob === false || $blob === '') return null;

        $data = @unserialize($blob);
        if (!is_array($data) || !array_key_exists('value', $data)) return null;

        if (!empty($data['exp']) && $data['exp'] < time()) {
            @unlink($f);
            return null;
        }

        // Warm the APCu layer for subsequent hits in this process.
        if (self::$apcu) {
            $remaining = !empty($data['exp']) ? max(1, $data['exp'] - time()) : 0;
            apcu_store(self::ns($key), $data['value'], $remaining);
        }
        return $data['value'];
    }

    public static function set($key, $value, $ttl = 60) {
        self::init();
        if ($value === null) return; // nulls confuse get() — refuse to store

        if (self::$apcu) apcu_store(self::ns($key), $value, $ttl);

        $exp  = $ttl > 0 ? time() + $ttl : 0;
        $blob = serialize(['exp' => $exp, 'value' => $value]);
        @file_put_contents(self::filePath($key), $blob, LOCK_EX);
    }

    public static function forget($key) {
        self::init();
        if (self::$apcu) apcu_delete(self::ns($key));
        $f = self::filePath($key);
        if (is_file($f)) @unlink($f);
    }

    /**
     * Read-through: return the cached value, or compute, store, and return.
     * Computed value is only cached if non-null (so callers can use null
     * to signal "don't cache this one").
     */
    public static function remember($key, $ttl, callable $compute) {
        $val = self::get($key);
        if ($val !== null) return $val;
        $val = $compute();
        if ($val !== null) self::set($key, $val, $ttl);
        return $val;
    }

    /* ============================================================
     * Tenant versioning
     * ============================================================ */

    public static function pharmacyVersion($pid) {
        $pid = (int)$pid;
        if ($pid <= 0) return 0;
        $v = self::get("meta:p:$pid:version");
        if ($v === null) {
            $v = 1;
            self::set("meta:p:$pid:version", $v, 86400);
        }
        return (int)$v;
    }

    /**
     * Invalidate every cache entry tagged for this pharmacy.
     * O(1) — just bumps the version counter; old entries die naturally.
     */
    public static function bumpPharmacyVersion($pid) {
        $pid = (int)$pid;
        if ($pid <= 0) return 0;
        $v = self::pharmacyVersion($pid) + 1;
        self::set("meta:p:$pid:version", $v, 86400);
        return $v;
    }

    /**
     * Build a cache key scoped to one pharmacy's current version.
     */
    public static function tenantKey($pid, $rest) {
        return 'p:' . (int)$pid . ':v' . self::pharmacyVersion($pid) . ':' . $rest;
    }

    /* ============================================================ */

    private static function ns($key) {
        return 'pharmacare:' . $key;
    }

    private static function filePath($key) {
        return self::$dir . '/' . sha1($key) . '.cache';
    }
}
