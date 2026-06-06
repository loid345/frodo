<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model;

use Frodo\Antifraud\Helper\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class OrderLimitValidator
{
    private Config $config;
    private EmailList $emailList;
    private IpMatcher $ipMatcher;
    private ResourceConnection $resourceConnection;
    private StoreManagerInterface $storeManager;
    private TimezoneInterface $timezone;

    public function __construct(
        Config $config,
        EmailList $emailList,
        IpMatcher $ipMatcher,
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager,
        TimezoneInterface $timezone
    ) {
        $this->config = $config;
        $this->emailList = $emailList;
        $this->ipMatcher = $ipMatcher;
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
        $this->timezone = $timezone;
    }

    /**
     * @throws LocalizedException
     */
    public function validate(Quote $quote, OrderInterface $order): void
    {
        $storeId = (int)$quote->getStoreId();
        if (!$this->config->isEnabled($storeId)) {
            return;
        }

        $email = (string)($order->getCustomerEmail() ?: $quote->getCustomerEmail());
        if ($this->emailList->contains($email, $this->config->getWhitelistEmails($storeId))) {
            return;
        }

        if ($this->emailList->contains($email, $this->config->getBlacklistEmails($storeId))) {
            throw new LocalizedException(__('Order placement is not available for this customer.'));
        }

        $customerId = (int)($order->getCustomerId() ?: $quote->getCustomerId());
        if ($customerId > 0 && in_array($customerId, $this->config->getBlacklistCustomerIds($storeId), true)) {
            throw new LocalizedException(__('Order placement is not available for this customer.'));
        }

        $remoteIp = (string)$quote->getRemoteIp();
        if ($this->ipMatcher->contains($remoteIp, $this->config->getBlacklistIps($storeId))) {
            throw new LocalizedException(__('Order placement is not available from this IP address.'));
        }

        $countLimit = $this->config->getDailyOrderCountLimit($storeId);
        $amountLimit = $this->config->getDailyAmountLimit($storeId);
        if ($countLimit === 0 && $amountLimit <= 0.0) {
            return;
        }

        $dailyTotals = $this->getDailyTotals($quote, $email);
        $currentOrderAmount = (float)$order->getBaseGrandTotal();

        if ($countLimit > 0 && ((int)$dailyTotals['orders_count'] + 1) > $countLimit) {
            throw new LocalizedException($this->getLimitMessage());
        }

        if ($amountLimit > 0.0 && ((float)$dailyTotals['base_amount_total'] + $currentOrderAmount) > $amountLimit) {
            throw new LocalizedException($this->getLimitMessage());
        }
    }

    /**
     * @return array{orders_count:int, base_amount_total:float}
     */
    private function getDailyTotals(Quote $quote, string $email): array
    {
        $connection = $this->resourceConnection->getConnection();
        $salesOrderTable = $this->resourceConnection->getTableName('sales_order');
        [$startUtc, $endUtc] = $this->getStoreDayUtcRange((int)$quote->getStoreId());

        $select = $connection->select()
            ->from(
                ['orders' => $salesOrderTable],
                [
                    'orders_count' => 'COUNT(*)',
                    'base_amount_total' => 'COALESCE(SUM(base_grand_total), 0)'
                ]
            )
            ->where('orders.customer_email = ?', $email)
            ->where('orders.store_id IN (?)', $this->getWebsiteStoreIds((int)$quote->getStoreId()))
            ->where('orders.created_at >= ?', $startUtc)
            ->where('orders.created_at < ?', $endUtc)
            ->where('orders.state NOT IN (?)', ['canceled', 'closed']);

        $row = $connection->fetchRow($select) ?: [];

        return [
            'orders_count' => (int)($row['orders_count'] ?? 0),
            'base_amount_total' => (float)($row['base_amount_total'] ?? 0.0),
        ];
    }

    /**
     * @return int[]
     */
    private function getWebsiteStoreIds(int $storeId): array
    {
        $website = $this->storeManager->getStore($storeId)->getWebsite();
        $storeIds = [];
        foreach ($website->getStores() as $store) {
            $storeIds[] = (int)$store->getId();
        }

        return $storeIds ?: [$storeId];
    }

    /**
     * @return string[]
     */
    private function getStoreDayUtcRange(int $storeId): array
    {
        $timezone = new \DateTimeZone($this->timezone->getConfigTimezone(ScopeInterface::SCOPE_STORE, $storeId));
        $start = new \DateTimeImmutable('today', $timezone);
        $end = $start->modify('+1 day');

        return [
            $start->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            $end->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
        ];
    }

    private function getLimitMessage(): Phrase
    {
        return __('Daily order limit has been exceeded. Please try again later.');
    }
}
