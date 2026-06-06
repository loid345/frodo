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

            if (strpos($blockedIp, '/') !== false && $this->matchesCidr($ip, $blockedIp)) {
                return true;
            }

            if (strcasecmp($ip, $blockedIp) === 0) {
                return true;
            }
        }

        return false;
    }

    private function matchesCidr(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = array_pad(explode('/', $cidr, 2), 2, null);
        if ($subnet === null || $mask === null || !ctype_digit($mask)) {
            return false;
        }

        $maskBits = (int)$mask;
        if ($maskBits < 0 || $maskBits > 32) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        if ($maskBits === 0) {
            return true;
        }

        $maskLong = -1 << (32 - $maskBits);
        $subnetLong &= $maskLong;

        return ($ipLong & $maskLong) === $subnetLong;
    }
}
