<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model;

use DateTimeImmutable;
use DateTimeZone;
use Frodo\Antifraud\Helper\Config;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Cache\Type\Config as ConfigCache;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\ScopeInterface;

class CustomerStatusManager
{
    private const DEFAULT_SCOPE_ID = 0;
    private const LIMIT_INTERVAL = '+1 day';
    private const UTC_TIMEZONE = 'UTC';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var WriterInterface
     */
    private WriterInterface $configWriter;

    /**
     * @var TypeListInterface
     */
    private TypeListInterface $cacheTypeList;

    /**
     * Initialize customer status dependencies.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
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

        return $email !== '' && in_array($email, $this->getBlacklistEmails(), true);
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

        $emails = $this->getBlacklistEmails();
        $emails[] = $email;
        $this->saveList(Config::XML_PATH_BLACKLIST_EMAILS, $this->normalizeEmails($emails));
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

        $emails = array_filter(
            $this->getBlacklistEmails(),
            static function (string $existingEmail) use ($email): bool {
                return $existingEmail !== $email;
            }
        );
        $this->saveList(Config::XML_PATH_BLACKLIST_EMAILS, $this->normalizeEmails($emails));
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

        return $email !== '' && array_key_exists($email, $this->getLimitedEmailExpirations());
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

        $expirations = $this->getLimitedEmailExpirations();
        $expirations[$email] = $this->getNow()->modify(self::LIMIT_INTERVAL)->format(DATE_ATOM);
        $this->saveList(Config::XML_PATH_LIMITED_EMAILS, $this->formatLimitedEmailEntries($expirations));
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

        $expirations = $this->getLimitedEmailExpirations();
        unset($expirations[$email]);
        $this->saveList(Config::XML_PATH_LIMITED_EMAILS, $this->formatLimitedEmailEntries($expirations));

        $emails = $this->getConfiguredList(Config::XML_PATH_WHITELIST_EMAILS);
        $emails[] = $email;
        $this->saveList(Config::XML_PATH_WHITELIST_EMAILS, $this->normalizeEmails($emails));
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

        $this->syncEmailList(Config::XML_PATH_BLACKLIST_EMAILS, $oldEmail, $newEmail);
        $this->syncEmailList(Config::XML_PATH_WHITELIST_EMAILS, $oldEmail, $newEmail);
        $this->syncLimitedEmailList($oldEmail, $newEmail);
    }

    /**
     * Get configured emails in the order blacklist.
     *
     * @return string[]
     */
    private function getBlacklistEmails(): array
    {
        return $this->normalizeEmails($this->getConfiguredList(Config::XML_PATH_BLACKLIST_EMAILS));
    }

    /**
     * Replace an email in a plain email config list.
     *
     * @param string $path
     * @param string $oldEmail
     * @param string $newEmail
     * @return void
     */
    private function syncEmailList(string $path, string $oldEmail, string $newEmail): void
    {
        $emails = $this->normalizeEmails($this->getConfiguredList($path));
        if (!in_array($oldEmail, $emails, true)) {
            return;
        }

        $updatedEmails = array_map(
            static function (string $email) use ($oldEmail, $newEmail): string {
                return $email === $oldEmail ? $newEmail : $email;
            },
            $emails
        );

        $this->saveList($path, $this->normalizeEmails($updatedEmails));
    }

    /**
     * Replace an email in the temporary limit config list while keeping the expiration.
     *
     * @param string $oldEmail
     * @param string $newEmail
     * @return void
     */
    private function syncLimitedEmailList(string $oldEmail, string $newEmail): void
    {
        $expirations = $this->getLimitedEmailExpirations();
        if (!array_key_exists($oldEmail, $expirations)) {
            return;
        }

        $expiresAt = $expirations[$oldEmail];
        unset($expirations[$oldEmail]);
        $expirations[$newEmail] = $expiresAt;

        $this->saveList(Config::XML_PATH_LIMITED_EMAILS, $this->formatLimitedEmailEntries($expirations));
    }

    /**
     * Get active limited email expiration entries keyed by email.
     *
     * @return array<string,string>
     */
    private function getLimitedEmailExpirations(): array
    {
        $now = $this->getNow();
        $expirations = [];

        foreach ($this->getConfiguredList(Config::XML_PATH_LIMITED_EMAILS) as $entry) {
            $parts = explode(':', $entry, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $email = $this->normalizeEmail($parts[0]);
            if ($email === '') {
                continue;
            }

            try {
                $expiresAt = new DateTimeImmutable($parts[1]);
            } catch (\Exception $exception) {
                continue;
            }

            if ($expiresAt > $now) {
                $expirations[$email] = $expiresAt->setTimezone(
                    new DateTimeZone(self::UTC_TIMEZONE)
                )->format(DATE_ATOM);
            }
        }

        return $expirations;
    }

    /**
     * Format temporary limit entries for config storage.
     *
     * @param array<string,string> $expirations
     * @return string[]
     */
    private function formatLimitedEmailEntries(array $expirations): array
    {
        ksort($expirations);
        $entries = [];
        foreach ($expirations as $email => $expiresAt) {
            $entries[] = $email . ':' . $expiresAt;
        }

        return $entries;
    }

    /**
     * Get a raw delimited config list from the default scope.
     *
     * @param string $path
     * @return string[]
     */
    private function getConfiguredList(string $path): array
    {
        $value = (string)$this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_DEFAULT,
            self::DEFAULT_SCOPE_ID
        );
        $items = preg_split('/[\s,;]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $items = array_map('trim', $items);
        $items = array_filter($items, static function (string $item): bool {
            return $item !== '';
        });

        return array_values(array_unique($items));
    }

    /**
     * Save a config list into the default scope and clear config cache.
     *
     * @param string $path
     * @param string[] $items
     * @return void
     */
    private function saveList(string $path, array $items): void
    {
        $this->configWriter->save($path, implode(PHP_EOL, $items));
        $this->cacheTypeList->cleanType(ConfigCache::TYPE_IDENTIFIER);
    }

    /**
     * Normalize email entries.
     *
     * @param string[] $emails
     * @return string[]
     */
    private function normalizeEmails(array $emails): array
    {
        $normalizedEmails = [];
        foreach ($emails as $email) {
            $email = $this->normalizeEmail((string)$email);
            if ($email !== '') {
                $normalizedEmails[] = $email;
            }
        }

        $normalizedEmails = array_values(array_unique($normalizedEmails));
        sort($normalizedEmails);

        return $normalizedEmails;
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
     * Get the current UTC date and time.
     *
     * @return DateTimeImmutable
     */
    private function getNow(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone(self::UTC_TIMEZONE));
    }
}
