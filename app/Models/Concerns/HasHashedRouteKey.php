<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Config;

/**
 * Exposes a SHORT, opaque, URL-safe route key instead of the raw (sequential)
 * primary key. URLs become e.g. /aduan/show/9fQk2mД so the numeric id and
 * record count are not exposed and cannot be trivially guessed or enumerated.
 *
 * Encoding: the id is passed through a keyed reversible transform (a small
 * Feistel network seeded from APP_KEY) then base62-encoded. This is
 * obfuscation, not cryptographic secrecy — real access control is enforced by
 * the model's policy (defence in depth). Invalid/foreign tokens resolve to
 * null (404).
 */
trait HasHashedRouteKey
{
    private const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /** The value placed into generated URLs (route('...', $model)). */
    public function getRouteKey()
    {
        return static::encodeRouteId((int) $this->getKey());
    }

    /** Resolve an incoming route token back to the model. */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field !== null) {
            return $this->where($field, $value)->first();
        }

        $id = static::decodeRouteToken((string) $value);

        return $id === null ? null : $this->where($this->getKeyName(), $id)->first();
    }

    /** For implicit binding without an explicit field. */
    public function getRouteKeyName()
    {
        return $this->getKeyName();
    }

    // ---- keyed reversible transform (Feistel over 48 bits) ----

    private static function seed(): int
    {
        // Derive a stable 32-bit round key from the app key.
        return crc32('route-key:'.Config::get('app.key'));
    }

    private static function feistel(int $value, bool $decrypt = false): int
    {
        // 48-bit block split into two 24-bit halves.
        $mask = 0xFFFFFF;
        $left = ($value >> 24) & $mask;
        $right = $value & $mask;
        $seed = self::seed();

        $rounds = [0, 1, 2, 3];
        if ($decrypt) {
            $rounds = array_reverse($rounds);
        }

        foreach ($rounds as $r) {
            $f = (crc32($seed.':'.$r.':'.($decrypt ? $left : $right)) & $mask);
            if ($decrypt) {
                // invert
                $newRight = $left;
                $newLeft = $right ^ $f;
                $left = $newLeft;
                $right = $newRight;
            } else {
                $newLeft = $right;
                $newRight = $left ^ $f;
                $left = $newLeft;
                $right = $newRight;
            }
        }

        return (($left & $mask) << 24) | ($right & $mask);
    }

    private static function encodeRouteId(int $id): string
    {
        $n = self::feistel($id, false);

        // base62
        if ($n === 0) {
            return '0';
        }
        $alphabet = self::ALPHABET;
        $base = strlen($alphabet);
        $out = '';
        while ($n > 0) {
            $out = $alphabet[$n % $base].$out;
            $n = intdiv($n, $base);
        }

        return $out;
    }

    private static function decodeRouteToken(string $token): ?int
    {
        if ($token === '' || preg_match('/[^0-9A-Za-z]/', $token)) {
            return null;
        }

        $alphabet = self::ALPHABET;
        $base = strlen($alphabet);
        $n = 0;
        foreach (str_split($token) as $ch) {
            $pos = strpos($alphabet, $ch);
            if ($pos === false) {
                return null;
            }
            $n = $n * $base + $pos;
        }

        $id = self::feistel($n, true);

        return $id > 0 ? $id : null;
    }
}
