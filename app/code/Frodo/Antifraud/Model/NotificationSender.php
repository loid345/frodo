<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model;

use Frodo\Antifraud\Helper\Config;
use Magento\Framework\App\Area;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class NotificationSender
{
    /**
     * Cache tag prefix for notification cooldown entries.
     */
    private const CACHE_PREFIX = 'frodo_antifraud_notif_';

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var TransportBuilder
     */
    private TransportBuilder $transportBuilder;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var CacheInterface
     */
    private CacheInterface $cache;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Initialize notification sender dependencies.
     *
     * @param Config $config
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * Send a limit violation notification email to configured recipients.
     *
     * Respects the cooldown setting to avoid flooding recipients with duplicate
     * notifications for the same customer email within a short time period.
     *
     * @param int $storeId
     * @param string $violationType Human-readable violation description
     * @param string $customerEmail The customer email that triggered the limit
     * @param string $remoteIp The customer IP address
     * @param string $currentValue The current count or amount value
     * @param string $limitValue The configured limit value
     * @param bool $isBlocked Whether the order was blocked
     * @return void
     */
    public function send(
        int $storeId,
        string $violationType,
        string $customerEmail,
        string $remoteIp,
        string $currentValue,
        string $limitValue,
        bool $isBlocked
    ): void {
        if (!$this->config->isNotificationEnabled($storeId)) {
            return;
        }

        $recipients = $this->config->getNotificationEmails($storeId);
        if (empty($recipients)) {
            return;
        }

        if ($this->isOnCooldown($storeId, $customerEmail)) {
            return;
        }

        try {
            $store = $this->storeManager->getStore($storeId);
            $storeName = $store->getName();

            foreach ($recipients as $recipientEmail) {
                $this->transportBuilder
                    ->setTemplateIdentifier('frodo_antifraud_limit_notification')
                    ->setTemplateOptions([
                        'area'  => Area::AREA_FRONTEND,
                        'store' => $storeId,
                    ])
                    ->setTemplateVars([
                        'violation_type' => $violationType,
                        'customer_email' => $customerEmail,
                        'remote_ip'      => $remoteIp !== '' ? $remoteIp : (string)__('N/A'),
                        'current_value'  => $currentValue,
                        'limit_value'    => $limitValue,
                        'store_name'     => $storeName,
                        'is_blocked'     => $isBlocked ? (string)__('Yes') : (string)__('No'),
                    ])
                    ->setFromByScope('general', $storeId)
                    ->addTo($recipientEmail);

                $transport = $this->transportBuilder->getTransport();
                $transport->sendMessage();
            }

            $this->setCooldown($storeId, $customerEmail);
        } catch (\Exception $exception) {
            $this->logger->error('Frodo Antifraud: failed to send notification email', [
                'customer_email' => $customerEmail,
                'violation_type' => $violationType,
                'exception'      => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Check whether a notification for the given customer email is still on cooldown.
     *
     * @param int $storeId
     * @param string $customerEmail
     * @return bool
     */
    private function isOnCooldown(int $storeId, string $customerEmail): bool
    {
        $cooldownMinutes = $this->config->getNotificationCooldownMinutes($storeId);
        if ($cooldownMinutes <= 0) {
            return false;
        }

        $cacheKey = $this->getCacheKey($storeId, $customerEmail);

        return $this->cache->load($cacheKey) !== false;
    }

    /**
     * Set the cooldown cache entry after a notification has been sent.
     *
     * @param int $storeId
     * @param string $customerEmail
     * @return void
     */
    private function setCooldown(int $storeId, string $customerEmail): void
    {
        $cooldownMinutes = $this->config->getNotificationCooldownMinutes($storeId);
        if ($cooldownMinutes <= 0) {
            return;
        }

        $cacheKey = $this->getCacheKey($storeId, $customerEmail);
        $lifetime = $cooldownMinutes * 60;

        $this->cache->save('1', $cacheKey, [], $lifetime);
    }

    /**
     * Build a unique cache key for the notification cooldown.
     *
     * @param int $storeId
     * @param string $customerEmail
     * @return string
     */
    private function getCacheKey(int $storeId, string $customerEmail): string
    {
        return self::CACHE_PREFIX . md5($storeId . '_' . strtolower($customerEmail));
    }
}
