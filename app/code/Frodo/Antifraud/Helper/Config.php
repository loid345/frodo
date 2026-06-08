<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    private const XML_PATH_ENABLED = 'frodo_antifraud/general/enabled';
    private const XML_PATH_DAILY_ORDER_COUNT_LIMIT = 'frodo_antifraud/general/daily_order_count_limit';
    private const XML_PATH_DAILY_AMOUNT_LIMIT = 'frodo_antifraud/general/daily_amount_limit';
    private const XML_PATH_WHITELIST_EMAILS = 'frodo_antifraud/general/whitelist_emails';
    private const XML_PATH_BLACKLIST_EMAILS = 'frodo_antifraud/general/blacklist_emails';
    private const XML_PATH_BLACKLIST_CUSTOMER_IDS = 'frodo_antifraud/general/blacklist_customer_ids';
    private const XML_PATH_BLACKLIST_IPS = 'frodo_antifraud/general/blacklist_ips';

    public function isEnabled(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getDailyOrderCountLimit(int $storeId): int
    {
        return max(0, (int)$this->scopeConfig->getValue(
            self::XML_PATH_DAILY_ORDER_COUNT_LIMIT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    public function getDailyAmountLimit(int $storeId): float
    {
        return max(0.0, (float)$this->scopeConfig->getValue(
            self::XML_PATH_DAILY_AMOUNT_LIMIT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
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
