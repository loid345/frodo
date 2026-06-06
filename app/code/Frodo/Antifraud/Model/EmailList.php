<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model;

class EmailList
{
    public function contains(string $email, array $emails): bool
    {
        $normalizedEmail = $this->normalize($email);
        if ($normalizedEmail === '') {
            return false;
        }

        foreach ($emails as $listedEmail) {
            if ($normalizedEmail === $this->normalize((string)$listedEmail)) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $email): string
    {
        return strtolower(trim($email));
    }
}
