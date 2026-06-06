<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model;

class IpMatcher
{
    public function contains(string $ip, array $blockedIps): bool
    {
        $ip = trim($ip);
        if ($ip === '') {
            return false;
        }

        foreach ($blockedIps as $blockedIp) {
            $blockedIp = trim((string)$blockedIp);
            if ($blockedIp === '') {
                continue;
            }

            if (strpos($blockedIp, '/') !== false) {
                if ($this->matchesCidr($ip, $blockedIp)) {
                    return true;
                }

                continue;
            }

            if ($this->matchesExactIp($ip, $blockedIp)) {
                return true;
            }
        }

        return false;
    }

    private function matchesExactIp(string $ip, string $blockedIp): bool
    {
        $ipBinary = inet_pton($ip);
        $blockedIpBinary = inet_pton($blockedIp);

        if ($ipBinary !== false && $blockedIpBinary !== false) {
            return hash_equals($ipBinary, $blockedIpBinary);
        }

        return strcasecmp($ip, $blockedIp) === 0;
    }

    private function matchesCidr(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = array_pad(explode('/', $cidr, 2), 2, null);
        if ($subnet === null || $mask === null || !ctype_digit($mask)) {
            return false;
        }

        $ipBinary = inet_pton($ip);
        $subnetBinary = inet_pton($subnet);
        if ($ipBinary === false || $subnetBinary === false || strlen($ipBinary) !== strlen($subnetBinary)) {
            return false;
        }

        $maskBits = (int)$mask;
        $totalBits = strlen($ipBinary) * 8;
        if ($maskBits < 0 || $maskBits > $totalBits) {
            return false;
        }

        $fullBytes = intdiv($maskBits, 8);
        $remainingBits = $maskBits % 8;

        if ($fullBytes > 0 && substr($ipBinary, 0, $fullBytes) !== substr($subnetBinary, 0, $fullBytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $maskByte = (0xff << (8 - $remainingBits)) & 0xff;
        $ipByte = ord($ipBinary[$fullBytes]) & $maskByte;
        $subnetByte = ord($subnetBinary[$fullBytes]) & $maskByte;

        return $ipByte === $subnetByte;
    }
}
