<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model;

class EmailList
{
    /**
     * Check whether an email exists in the configured list.
     *
     * @param string $email
     * @param array $emails
     * @return bool
     */
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

    /**
     * Normalize an email address for comparison.
     *
     * @param string $email
     * @return string
     */
    private function normalize(string $email): string
    {
        return strtolower(trim($email));
    }
}
