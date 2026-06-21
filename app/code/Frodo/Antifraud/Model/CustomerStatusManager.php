<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model;

use DateTimeImmutable;
use DateTimeZone;
use Magento\Customer\Api\Data\CustomerInterface;

class CustomerStatusManager
{
    private const LIMIT_INTERVAL = '+1 day';
    private const UTC_TIMEZONE = 'UTC';

    /**
     * @var BlacklistEmailRepository
     */
    private BlacklistEmailRepository $blacklistEmailRepo;

    /**
     * @var WhitelistEmailRepository
     */
    private WhitelistEmailRepository $whitelistEmailRepo;

    /**
     * @var LimitedEmailRepository
     */
    private LimitedEmailRepository $limitedEmailRepo;

    /**
     * @var ActionLogger
     */
    private ActionLogger $actionLogger;

    /**
     * Initialize customer status dependencies.
     *
     * @param BlacklistEmailRepository $blacklistEmailRepo
     * @param WhitelistEmailRepository $whitelistEmailRepo
     * @param LimitedEmailRepository $limitedEmailRepo
     * @param ActionLogger $actionLogger
     */
    public function __construct(
        BlacklistEmailRepository $blacklistEmailRepo,
        WhitelistEmailRepository $whitelistEmailRepo,
        LimitedEmailRepository $limitedEmailRepo,
        ActionLogger $actionLogger
    ) {
        $this->blacklistEmailRepo = $blacklistEmailRepo;
        $this->whitelistEmailRepo = $whitelistEmailRepo;
        $this->limitedEmailRepo = $limitedEmailRepo;
        $this->actionLogger = $actionLogger;
    }

    /**
     * Check whether the customer email is in the order blacklist.
     *
     * @param CustomerInterface $customer
     * @return bool
     */
    public function isBlocked(CustomerInterface $customer): bool
    {
        $email = $this->getCustomerEmail($customer);

        return $email !== '' && $this->blacklistEmailRepo->emailExists($email);
    }

    /**
     * Add the customer email to the order blacklist.
     *
     * @param CustomerInterface $customer
     * @return void
     */
    public function block(CustomerInterface $customer): void
    {
        $email = $this->getCustomerEmail($customer);
        if ($email === '') {
            return;
        }

        if ($this->blacklistEmailRepo->emailExists($email)) {
            return;
        }

        $entity = new BlacklistEmail();
        $entity->setEmail($email);
        $entity->setReason('Blocked via admin customer page');
        $this->blacklistEmailRepo->save($entity);

        $this->actionLogger->log(
            'blacklist_add',
            'email',
            $email,
            $this->getCustomerId($customer),
            'Blocked via admin customer page'
        );
    }

    /**
     * Remove the customer email from the order blacklist.
     *
     * @param CustomerInterface $customer
     * @return void
     */
    public function unblock(CustomerInterface $customer): void
    {
        $email = $this->getCustomerEmail($customer);
        if ($email === '') {
            return;
        }

        $this->blacklistEmailRepo->deleteByEmail($email);

        $this->actionLogger->log(
            'blacklist_remove',
            'email',
            $email,
            $this->getCustomerId($customer),
            'Unblocked via admin customer page'
        );
    }

    /**
     * Check whether the customer email has an active temporary daily-limit restriction.
     *
     * @param CustomerInterface $customer
     * @return bool
     */
    public function isLimited(CustomerInterface $customer): bool
    {
        $email = $this->getCustomerEmail($customer);

        return $email !== '' && $this->limitedEmailRepo->isActiveLimited($email);
    }

    /**
     * Limit the customer email for one day.
     *
     * @param CustomerInterface $customer
     * @return void
     */
    public function limitForOneDay(CustomerInterface $customer): void
    {
        $email = $this->getCustomerEmail($customer);
        if ($email === '') {
            return;
        }

        $expiresAt = (new DateTimeImmutable('now', new DateTimeZone(self::UTC_TIMEZONE)))
            ->modify(self::LIMIT_INTERVAL)
            ->format('Y-m-d H:i:s');

        $entity = $this->limitedEmailRepo->getByEmail($email);
        if ($entity === null) {
            $entity = new LimitedEmail();
            $entity->setEmail($email);
        }
        $entity->setExpiresAt($expiresAt);
        $this->limitedEmailRepo->save($entity);

        $this->actionLogger->log(
            'limit_add',
            'email',
            $email,
            $this->getCustomerId($customer),
            sprintf('Limited until %s', $expiresAt)
        );
    }

    /**
     * Remove a temporary limit and add the customer e-mail to the whitelist.
     *
     * @param CustomerInterface $customer
     * @return void
     */
    public function removeLimitAndWhitelist(CustomerInterface $customer): void
    {
        $email = $this->getCustomerEmail($customer);
        if ($email === '') {
            return;
        }

        $customerId = $this->getCustomerId($customer);

        $this->limitedEmailRepo->deleteByEmail($email);
        $this->actionLogger->log('limit_remove', 'email', $email, $customerId, 'Limit removed via admin');

        if (!$this->whitelistEmailRepo->emailExists($email)) {
            $entity = new WhitelistEmail();
            $entity->setEmail($email);
            $entity->setReason('Auto-whitelisted on limit removal');
            $this->whitelistEmailRepo->save($entity);

            $this->actionLogger->log(
                'whitelist_add',
                'email',
                $email,
                $customerId,
                'Auto-whitelisted on limit removal'
            );
        }
    }

    /**
     * Replace an old customer email with a new one in all email-based lists.
     *
     * @param string $oldEmail
     * @param string $newEmail
     * @return void
     */
    public function syncEmailChange(string $oldEmail, string $newEmail): void
    {
        $oldEmail = $this->normalizeEmail($oldEmail);
        $newEmail = $this->normalizeEmail($newEmail);
        if ($oldEmail === '' || $newEmail === '' || $oldEmail === $newEmail) {
            return;
        }

        $this->syncEmailInRepo($this->blacklistEmailRepo, $oldEmail, $newEmail);
        $this->syncEmailInRepo($this->whitelistEmailRepo, $oldEmail, $newEmail);
        $this->syncLimitedEmail($oldEmail, $newEmail);

        $this->actionLogger->log(
            'email_sync',
            'email',
            $newEmail,
            null,
            sprintf('%s → %s', $oldEmail, $newEmail)
        );
    }

    /**
     * Sync an email change in a blacklist or whitelist repository.
     *
     * @param BlacklistEmailRepository|WhitelistEmailRepository $repo
     * @param string $oldEmail
     * @param string $newEmail
     * @return void
     */
    private function syncEmailInRepo($repo, string $oldEmail, string $newEmail): void
    {
        $entity = $repo->getByEmail($oldEmail);
        if ($entity === null) {
            return;
        }

        $existingNew = $repo->getByEmail($newEmail);
        if ($existingNew !== null) {
            $repo->delete($entity);
            return;
        }

        $entity->setEmail($newEmail);
        $repo->save($entity);
    }

    /**
     * Sync an email change in the limited email repository.
     *
     * @param string $oldEmail
     * @param string $newEmail
     * @return void
     */
    private function syncLimitedEmail(string $oldEmail, string $newEmail): void
    {
        $entity = $this->limitedEmailRepo->getByEmail($oldEmail);
        if ($entity === null) {
            return;
        }

        $existingNew = $this->limitedEmailRepo->getByEmail($newEmail);
        if ($existingNew !== null) {
            try {
                $oldExpires = new DateTimeImmutable($entity->getExpiresAt(), new DateTimeZone(self::UTC_TIMEZONE));
                $newExpires = new DateTimeImmutable($existingNew->getExpiresAt(), new DateTimeZone(self::UTC_TIMEZONE));
                if ($newExpires > $oldExpires) {
                    $this->limitedEmailRepo->delete($entity);
                    return;
                }
            } catch (\Exception $exception) {
                $this->limitedEmailRepo->delete($entity);
                return;
            }

            $this->limitedEmailRepo->delete($existingNew);
        }

        $entity->setEmail($newEmail);
        $this->limitedEmailRepo->save($entity);
    }

    /**
     * Normalize one email entry.
     *
     * @param string $email
     * @return string
     */
    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Get the normalized customer email.
     *
     * @param CustomerInterface $customer
     * @return string
     */
    private function getCustomerEmail(CustomerInterface $customer): string
    {
        return $this->normalizeEmail((string)$customer->getEmail());
    }

    /**
     * Get the customer ID if available.
     *
     * @param CustomerInterface $customer
     * @return int|null
     */
    private function getCustomerId(CustomerInterface $customer): ?int
    {
        $id = $customer->getId();

        return $id !== null ? (int)$id : null;
    }
}
