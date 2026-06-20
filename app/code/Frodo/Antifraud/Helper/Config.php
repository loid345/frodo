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
    public const XML_PATH_ENABLED = 'frodo_antifraud/general/enabled';
    public const XML_PATH_DAILY_ORDER_COUNT_LIMIT = 'frodo_antifraud/general/daily_order_count_limit';
    public const XML_PATH_DAILY_AMOUNT_LIMIT = 'frodo_antifraud/general/daily_amount_limit';

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
}
