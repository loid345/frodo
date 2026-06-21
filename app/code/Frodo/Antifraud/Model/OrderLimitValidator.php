<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model;

use DateTimeZone;
use Frodo\Antifraud\Helper\Config;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class OrderLimitValidator
{
    private const UTC_TIMEZONE = 'UTC';

    /**
     * @var Config
     */
    private Config $config;

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
     * @var BlacklistIpRepository
     */
    private BlacklistIpRepository $blacklistIpRepo;

    /**
     * @var IpMatcher
     */
    private IpMatcher $ipMatcher;

    /**
     * @var OrderCollectionFactory
     */
    private OrderCollectionFactory $orderCollectionFactory;

    /**
     * @var RemoteAddress
     */
    private RemoteAddress $remoteAddress;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezone;

    /**
     * @var ActionLogger
     */
    private ActionLogger $actionLogger;

    /**
     * Initialize validator dependencies.
     *
     * @param Config $config
     * @param BlacklistEmailRepository $blacklistEmailRepo
     * @param WhitelistEmailRepository $whitelistEmailRepo
     * @param LimitedEmailRepository $limitedEmailRepo
     * @param BlacklistIpRepository $blacklistIpRepo
     * @param IpMatcher $ipMatcher
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param RemoteAddress $remoteAddress
     * @param StoreManagerInterface $storeManager
     * @param TimezoneInterface $timezone
     * @param ActionLogger $actionLogger
     */
    public function __construct(
        Config $config,
        BlacklistEmailRepository $blacklistEmailRepo,
        WhitelistEmailRepository $whitelistEmailRepo,
        LimitedEmailRepository $limitedEmailRepo,
        BlacklistIpRepository $blacklistIpRepo,
        IpMatcher $ipMatcher,
        OrderCollectionFactory $orderCollectionFactory,
        RemoteAddress $remoteAddress,
        StoreManagerInterface $storeManager,
        TimezoneInterface $timezone,
        ActionLogger $actionLogger
    ) {
        $this->config = $config;
        $this->blacklistEmailRepo = $blacklistEmailRepo;
        $this->whitelistEmailRepo = $whitelistEmailRepo;
        $this->limitedEmailRepo = $limitedEmailRepo;
        $this->blacklistIpRepo = $blacklistIpRepo;
        $this->ipMatcher = $ipMatcher;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->remoteAddress = $remoteAddress;
        $this->storeManager = $storeManager;
        $this->timezone = $timezone;
        $this->actionLogger = $actionLogger;
    }

    /**
     * Validate order placement against configured antifraud rules.
     *
     * @param Quote $quote
     * @param OrderInterface $order
     * @return void
     * @throws LocalizedException
     */
    public function validate(Quote $quote, OrderInterface $order): void
    {
        $storeId = (int)$quote->getStoreId();
        if (!$this->config->isEnabled($storeId)) {
            return;
        }

        $email = $this->normalizeEmail((string)($order->getCustomerEmail() ?: $quote->getCustomerEmail()));

        if ($this->blacklistEmailRepo->emailExists($email)) {
            $this->actionLogger->log('order_blocked', 'email', $email, null, 'Email is blacklisted');
            throw new LocalizedException(__('Order placement is blocked.'));
        }

        $remoteIp = $this->getRemoteIp($quote);
        $storeIds = $this->getWebsiteStoreIds($storeId);
        $storeIdsWithGlobal = array_merge([0], $storeIds);
        if ($remoteIp !== '' && $this->isIpBlocked($remoteIp, $storeIdsWithGlobal)) {
            $this->actionLogger->log('order_blocked', 'ip', $remoteIp, null, 'IP is blacklisted');
            throw new LocalizedException(__('Order placement is not available from this IP address.'));
        }

        $isWhitelisted = $this->whitelistEmailRepo->emailExists($email);
        if ($isWhitelisted || $email === '') {
            return;
        }

        if ($this->limitedEmailRepo->isActiveLimited($email)) {
            $this->actionLogger->log('order_blocked', 'email', $email, null, 'Email has active daily limit');
            throw new LocalizedException($this->getLimitMessage());
        }

        $countLimit = $this->config->getDailyOrderCountLimit($storeId);
        $amountLimit = $this->config->getDailyAmountLimit($storeId);
        if ($countLimit === 0 && $amountLimit <= 0.0) {
            return;
        }

        $dailyTotals = $this->getDailyTotals($quote, $email);
        $currentOrderAmount = (float)$order->getBaseGrandTotal();

        if ($countLimit > 0 && ((int)$dailyTotals['orders_count'] + 1) > $countLimit) {
            $this->actionLogger->log(
                'order_blocked',
                'email',
                $email,
                null,
                sprintf('Daily order count limit exceeded (%d/%d)', (int)$dailyTotals['orders_count'] + 1, $countLimit)
            );
            throw new LocalizedException($this->getLimitMessage());
        }

        if ($amountLimit > 0.0 && ((float)$dailyTotals['base_amount_total'] + $currentOrderAmount) > $amountLimit) {
            $this->actionLogger->log(
                'order_blocked',
                'email',
                $email,
                null,
                sprintf(
                    'Daily amount limit exceeded (%.2f/%.2f)',
                    (float)$dailyTotals['base_amount_total'] + $currentOrderAmount,
                    $amountLimit
                )
            );
            throw new LocalizedException($this->getLimitMessage());
        }
    }

    /**
     * Check whether the IP is blocked for any of the given store IDs using the IpMatcher.
     *
     * IP blacklist entries may contain CIDR ranges, so we still need the IpMatcher
     * for subnet matching rather than a simple DB lookup.
     *
     * @param string $ip
     * @param int[] $storeIds
     * @return bool
     */
    private function isIpBlocked(string $ip, array $storeIds): bool
    {
        $blockedIps = $this->blacklistIpRepo->getByStoreIds($storeIds);
        $ipAddresses = [];
        foreach ($blockedIps as $entry) {
            $ipAddresses[] = $entry->getIpAddress();
        }

        return $this->ipMatcher->contains($ip, $ipAddresses);
    }

    /**
     * Normalize an email address for storage comparison.
     *
     * @param string $email
     * @return string
     */
    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Get the quote remote IP or fallback to the current request remote address.
     *
     * @param Quote $quote
     * @return string
     */
    private function getRemoteIp(Quote $quote): string
    {
        $remoteIp = trim((string)$quote->getRemoteIp());
        if ($remoteIp !== '') {
            return $remoteIp;
        }

        $remoteAddress = $this->remoteAddress->getRemoteAddress();

        return is_string($remoteAddress) ? trim($remoteAddress) : '';
    }

    /**
     * Get existing daily order totals for the quote email and website stores.
     *
     * @param Quote $quote
     * @param string $email
     * @return array{orders_count:int, base_amount_total:float}
     */
    private function getDailyTotals(Quote $quote, string $email): array
    {
        [$startUtc, $endUtc] = $this->getStoreDayUtcRange((int)$quote->getStoreId());
        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToFilter(OrderInterface::STORE_ID, [
            'in' => $this->getWebsiteStoreIds((int)$quote->getStoreId())
        ]);
        $collection->addFieldToFilter(OrderInterface::STATE, [
            'nin' => [Order::STATE_CANCELED, Order::STATE_CLOSED]
        ]);

        $select = $collection->getSelect();
        $select->reset(Select::COLUMNS)
            ->columns([
                'orders_count' => new Expression('COUNT(*)'),
                'base_amount_total' => new Expression(
                    'COALESCE(SUM(main_table.base_grand_total), 0)'
                )
            ])
            ->where('LOWER(main_table.customer_email) = ?', $email)
            ->where('main_table.created_at >= ?', $startUtc)
            ->where('main_table.created_at < ?', $endUtc);

        $row = $collection->getConnection()->fetchRow($select) ?: [];

        return [
            'orders_count' => (int)($row['orders_count'] ?? 0),
            'base_amount_total' => (float)($row['base_amount_total'] ?? 0.0),
        ];
    }

    /**
     * Get store IDs that belong to the same website as the quote store.
     *
     * @param int $storeId
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
     * Get the current store day range converted to UTC timestamps.
     *
     * @param int $storeId
     * @return string[]
     */
    private function getStoreDayUtcRange(int $storeId): array
    {
        $currentDate = $this->timezone->scopeDate($storeId);
        $start = (clone $currentDate)->setTime(0, 0);
        $end = (clone $start)->modify('+1 day');
        $utcTimezone = new DateTimeZone(self::UTC_TIMEZONE);

        return [
            $start->setTimezone($utcTimezone)->format('Y-m-d H:i:s'),
            $end->setTimezone($utcTimezone)->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get the localized limit exception message.
     *
     * @return Phrase
     */
    private function getLimitMessage(): Phrase
    {
        return __('Daily limit reached.');
    }
}
