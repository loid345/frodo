<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Observer;

use Frodo\Antifraud\Model\OrderLimitValidator;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;

class ValidateOrderPlacement implements ObserverInterface
{
    /**
     * @var OrderLimitValidator
     */
    private OrderLimitValidator $orderLimitValidator;

    /**
     * Initialize observer dependencies.
     *
     * @param OrderLimitValidator $orderLimitValidator
     */
    public function __construct(OrderLimitValidator $orderLimitValidator)
    {
        $this->orderLimitValidator = $orderLimitValidator;
    }

    /**
     * Validate quote and order before order placement.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();

        if (!$quote instanceof Quote || !$order instanceof OrderInterface) {
            return;
        }

        $this->orderLimitValidator->validate($quote, $order);
    }
}
