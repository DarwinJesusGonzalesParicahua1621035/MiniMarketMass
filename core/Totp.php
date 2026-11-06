<?php
class Totp
{
    private const ABC = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function secreto(): string
    {
        $bits = '';
        foreach (str_split(random_bytes(20)) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $o = '';
        foreach (str_split($bits, 5) as $ch) {
            $o .= self::ABC[bindec($ch)];
        }
        return $o;
    }

    private static function binario(string $s): string
    {
        $bits = '';
        foreach (str_split(strtoupper($s)) as $c) {
            $p = strpos(self::ABC, $c);
            if ($p !== false) {
                $bits .= str_pad(decbin($p), 5, '0', STR_PAD_LEFT);
            }
        }
        $o = '';
        foreach (str_split($bits, 8) as $b) {
            if (strlen($b) === 8) {
                $o .= chr(bindec($b));
            }
        }
        return $o;
    }

    public static function codigo(string $secreto, int $paso): string
    {
        $h = hash_hmac('sha1', pack('J', $paso), self::binario($secreto), true);
        $o = ord($h[19]) & 15;
        $n = ((ord($h[$o]) & 0x7f) << 24) | ((ord($h[$o + 1]) & 0xff) << 16)
           | ((ord($h[$o + 2]) & 0xff) << 8) | (ord($h[$o + 3]) & 0xff);
        return str_pad((string)($n % 1000000), 6, '0', STR_PAD_LEFT);
    }

    public static function verificar(string $secreto, string $codigo): ?int
    {
        if (!preg_match('/^\d{6}$/', $codigo)) {
            return null;
        }
        $t = intdiv(time(), 30);
        for ($i = -1; $i <= 1; $i++) {
            if (hash_equals(self::codigo($secreto, $t + $i), $codigo)) {
                return $t + $i;
            }
        }
        return null;
    }

    public static function uri(string $secreto, string $cuenta): string
    {
        return 'otpauth://totp/' . rawurlencode('Tiendas Mass:' . $cuenta)
             . '?secret=' . $secreto . '&issuer=' . rawurlencode('Tiendas Mass') . '&digits=6&period=30';
    }
}
