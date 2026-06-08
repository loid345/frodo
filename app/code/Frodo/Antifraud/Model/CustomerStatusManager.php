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
     * Check whether the customer is in the order blacklist.
     *
     * @param int $customerId
     * @return bool
     */
    public function isBlocked(int $customerId): bool
    {
        return in_array($customerId, $this->getBlacklistCustomerIds(), true);
    }

    /**
     * Add the customer to the order blacklist.
     *
     * @param int $customerId
     * @return void
     */
    public function block(int $customerId): void
    {
        $customerIds = $this->getBlacklistCustomerIds();
        $customerIds[] = $customerId;
        $this->saveList(Config::XML_PATH_BLACKLIST_CUSTOMER_IDS, $this->normalizeIds($customerIds));
    }

    /**
     * Remove the customer from the order blacklist.
     *
     * @param int $customerId
     * @return void
     */
    public function unblock(int $customerId): void
    {
        $customerIds = array_filter(
            $this->getBlacklistCustomerIds(),
            static function (int $existingCustomerId) use ($customerId): bool {
                return $existingCustomerId !== $customerId;
            }
        );
        $this->saveList(Config::XML_PATH_BLACKLIST_CUSTOMER_IDS, $this->normalizeIds($customerIds));
    }

    /**
     * Check whether the customer has an active temporary daily-limit restriction.
     *
     * @param int $customerId
     * @return bool
     */
    public function isLimited(int $customerId): bool
    {
        return array_key_exists($customerId, $this->getLimitedCustomerExpirations());
    }

    /**
     * Limit the customer for one day.
     *
     * @param int $customerId
     * @return void
     */
    public function limitForOneDay(int $customerId): void
    {
        $expirations = $this->getLimitedCustomerExpirations();
        $expirations[$customerId] = $this->getNow()->modify(self::LIMIT_INTERVAL)->format(DATE_ATOM);
        $this->saveList(Config::XML_PATH_LIMITED_CUSTOMER_IDS, $this->formatLimitedCustomerEntries($expirations));
    }

    /**
     * Remove a temporary limit and add the customer e-mail to the whitelist.
     *
     * @param CustomerInterface $customer
     * @return void
     */
    public function removeLimitAndWhitelist(CustomerInterface $customer): void
    {
        $customerId = (int)$customer->getId();
        $expirations = $this->getLimitedCustomerExpirations();
        unset($expirations[$customerId]);
        $this->saveList(Config::XML_PATH_LIMITED_CUSTOMER_IDS, $this->formatLimitedCustomerEntries($expirations));

        $email = strtolower(trim((string)$customer->getEmail()));
        if ($email === '') {
            return;
        }

        $emails = $this->getConfiguredList(Config::XML_PATH_WHITELIST_EMAILS);
        $emails[] = $email;
        $this->saveList(Config::XML_PATH_WHITELIST_EMAILS, $this->normalizeEmails($emails));
    }

    /**
     * Get configured customer IDs in the order blacklist.
     *
     * @return int[]
     */
    private function getBlacklistCustomerIds(): array
    {
        return $this->normalizeIds($this->getConfiguredList(Config::XML_PATH_BLACKLIST_CUSTOMER_IDS));
    }

    /**
     * Get active customer limit expiration entries keyed by customer ID.
     *
     * @return array<int,string>
     */
    private function getLimitedCustomerExpirations(): array
    {
        $now = $this->getNow();
        $expirations = [];

        foreach ($this->getConfiguredList(Config::XML_PATH_LIMITED_CUSTOMER_IDS) as $entry) {
            $parts = explode(':', $entry, 2);
            if (count($parts) !== 2 || !ctype_digit($parts[0])) {
                continue;
            }

            $customerId = (int)$parts[0];
            if ($customerId <= 0) {
                continue;
            }

            try {
                $expiresAt = new DateTimeImmutable($parts[1]);
            } catch (\Exception $exception) {
                continue;
            }

            if ($expiresAt > $now) {
                $expirations[$customerId] = $expiresAt->setTimezone(
                    new DateTimeZone(self::UTC_TIMEZONE)
                )->format(DATE_ATOM);
            }
        }

        return $expirations;
    }

    /**
     * Format temporary limit entries for config storage.
     *
     * @param array<int,string> $expirations
     * @return string[]
     */
    private function formatLimitedCustomerEntries(array $expirations): array
    {
        ksort($expirations);
        $entries = [];
        foreach ($expirations as $customerId => $expiresAt) {
            $entries[] = (int)$customerId . ':' . $expiresAt;
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
     * @param string[]|int[] $items
     * @return void
     */
    private function saveList(string $path, array $items): void
    {
        $this->configWriter->save($path, implode(PHP_EOL, $items));
        $this->cacheTypeList->cleanType(ConfigCache::TYPE_IDENTIFIER);
    }

    /**
     * Normalize customer IDs.
     *
     * @param mixed[] $customerIds
     * @return int[]
     */
    private function normalizeIds(array $customerIds): array
    {
        $normalizedIds = [];
        foreach ($customerIds as $customerId) {
            $customerId = (string)$customerId;
            if (!ctype_digit($customerId)) {
                continue;
            }

            $customerId = (int)$customerId;
            if ($customerId > 0) {
                $normalizedIds[] = $customerId;
            }
        }

        $normalizedIds = array_values(array_unique($normalizedIds));
        sort($normalizedIds);

        return $normalizedIds;
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
            $email = strtolower(trim((string)$email));
            if ($email !== '') {
                $normalizedEmails[] = $email;
            }
        }

        return array_values(array_unique($normalizedEmails));
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
