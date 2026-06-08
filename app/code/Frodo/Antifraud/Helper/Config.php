<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Helper;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    public const XML_PATH_ENABLED = 'frodo_antifraud/general/enabled';
    public const XML_PATH_DAILY_ORDER_COUNT_LIMIT = 'frodo_antifraud/general/daily_order_count_limit';
    public const XML_PATH_DAILY_AMOUNT_LIMIT = 'frodo_antifraud/general/daily_amount_limit';
    public const XML_PATH_WHITELIST_EMAILS = 'frodo_antifraud/general/whitelist_emails';
    public const XML_PATH_BLACKLIST_EMAILS = 'frodo_antifraud/general/blacklist_emails';
    public const XML_PATH_BLACKLIST_CUSTOMER_IDS = 'frodo_antifraud/general/blacklist_customer_ids';
    public const XML_PATH_BLACKLIST_IPS = 'frodo_antifraud/general/blacklist_ips';
    public const XML_PATH_LIMITED_CUSTOMER_IDS = 'frodo_antifraud/general/limited_customer_ids';

    private const UTC_TIMEZONE = 'UTC';

    /**
     * Check whether antifraud validation is enabled for the store scope.
     *
     * @param int $storeId
     * @return bool
     */
    public function isEnabled(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get the daily order count limit for the store scope.
     *
     * @param int $storeId
     * @return int
     */
    public function getDailyOrderCountLimit(int $storeId): int
    {
        return max(0, (int)$this->scopeConfig->getValue(
            self::XML_PATH_DAILY_ORDER_COUNT_LIMIT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * Get the daily base amount limit for the store scope.
     *
     * @param int $storeId
     * @return float
     */
    public function getDailyAmountLimit(int $storeId): float
    {
        return max(0.0, (float)$this->scopeConfig->getValue(
            self::XML_PATH_DAILY_AMOUNT_LIMIT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * Get whitelist email entries for the store scope.
     *
     * @param int $storeId
     * @return string[]
     */
    public function getWhitelistEmails(int $storeId): array
    {
        return $this->parseList((string)$this->scopeConfig->getValue(
            self::XML_PATH_WHITELIST_EMAILS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * Get blacklist email entries for the store scope.
     *
     * @param int $storeId
     * @return string[]
     */
    public function getBlacklistEmails(int $storeId): array
    {
        return $this->parseList((string)$this->scopeConfig->getValue(
            self::XML_PATH_BLACKLIST_EMAILS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * Get positive numeric customer ID blacklist entries for the store scope.
     *
     * @param int $storeId
     * @return int[]
     */
    public function getBlacklistCustomerIds(int $storeId): array
    {
        $customerIds = [];
        foreach ($this->parseList((string)$this->scopeConfig->getValue(
            self::XML_PATH_BLACKLIST_CUSTOMER_IDS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        )) as $customerId) {
            if (!ctype_digit($customerId)) {
                continue;
            }

            $customerId = (int)$customerId;
            if ($customerId > 0) {
                $customerIds[] = $customerId;
            }
        }

        return array_values(array_unique($customerIds));
    }

    /**
     * Get active temporary limited customer IDs for the store scope.
     *
     * @param int $storeId
     * @return int[]
     */
    public function getLimitedCustomerIds(int $storeId): array
    {
        $customerIds = [];
        $now = new DateTimeImmutable('now', new DateTimeZone(self::UTC_TIMEZONE));

        foreach ($this->parseList((string)$this->scopeConfig->getValue(
            self::XML_PATH_LIMITED_CUSTOMER_IDS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        )) as $entry) {
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
            } catch (Exception $exception) {
                continue;
            }

            if ($expiresAt > $now) {
                $customerIds[] = $customerId;
            }
        }

        return array_values(array_unique($customerIds));
    }

    /**
     * Get IP blacklist entries for the store scope.
     *
     * @param int $storeId
     * @return string[]
     */
    public function getBlacklistIps(int $storeId): array
    {
        return $this->parseList((string)$this->scopeConfig->getValue(
            self::XML_PATH_BLACKLIST_IPS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * Parse a configured delimited list into unique non-empty items.
     *
     * @param string $value
     * @return string[]
     */
    private function parseList(string $value): array
    {
        $items = preg_split('/[\s,;]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $items = array_map('trim', $items);
        $items = array_filter($items, static function (string $item): bool {
            return $item !== '';
        });

        return array_values(array_unique($items));
    }
}
