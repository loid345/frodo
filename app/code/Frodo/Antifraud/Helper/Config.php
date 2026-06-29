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
    public const XML_PATH_NOTIFICATION_ENABLED = 'frodo_antifraud/notifications/notification_enabled';
    public const XML_PATH_NOTIFICATION_EMAILS = 'frodo_antifraud/notifications/notification_emails';
    public const XML_PATH_NOTIFY_ONLY_MODE = 'frodo_antifraud/notifications/notify_only_mode';
    public const XML_PATH_NOTIFICATION_COOLDOWN = 'frodo_antifraud/notifications/notification_cooldown';

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
     * Check whether email notifications are enabled for the store scope.
     *
     * @param int $storeId
     * @return bool
     */
    public function isNotificationEnabled(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_NOTIFICATION_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get the list of notification recipient email addresses for the store scope.
     *
     * @param int $storeId
     * @return string[]
     */
    public function getNotificationEmails(int $storeId): array
    {
        $value = (string)$this->scopeConfig->getValue(
            self::XML_PATH_NOTIFICATION_EMAILS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($value === '') {
            return [];
        }

        $emails = array_map('trim', explode(',', $value));

        return array_values(array_filter($emails, static function (string $email): bool {
            return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        }));
    }

    /**
     * Check whether notify-only mode is enabled for the store scope.
     *
     * When enabled, orders are not blocked on limit violations — only notifications are sent.
     *
     * @param int $storeId
     * @return bool
     */
    public function isNotifyOnlyMode(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_NOTIFY_ONLY_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get the notification cooldown period in minutes for the store scope.
     *
     * Prevents sending duplicate notifications for the same customer email
     * within the specified number of minutes.
     *
     * @param int $storeId
     * @return int
     */
    public function getNotificationCooldownMinutes(int $storeId): int
    {
        return max(0, (int)$this->scopeConfig->getValue(
            self::XML_PATH_NOTIFICATION_COOLDOWN,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }
}
