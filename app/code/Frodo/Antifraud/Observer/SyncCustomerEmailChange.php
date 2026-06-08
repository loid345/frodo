<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Observer;

use Frodo\Antifraud\Model\CustomerStatusManager;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SyncCustomerEmailChange implements ObserverInterface
{
    /**
     * @var CustomerStatusManager
     */
    private CustomerStatusManager $customerStatusManager;

    /**
     * Initialize observer dependencies.
     *
     * @param CustomerStatusManager $customerStatusManager
     */
    public function __construct(CustomerStatusManager $customerStatusManager)
    {
        $this->customerStatusManager = $customerStatusManager;
    }

    /**
     * Sync antifraud email lists when a customer email changes.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        [$oldEmail, $newEmail] = $this->getEmailChange($observer);
        $this->customerStatusManager->syncEmailChange($oldEmail, $newEmail);
    }

    /**
     * Get old and new customer emails from supported customer save events.
     *
     * @param Observer $observer
     * @return string[]
     */
    private function getEmailChange(Observer $observer): array
    {
        $event = $observer->getEvent();
        $customerData = $event->getData('customer_data_object');
        $origCustomerData = $event->getData('orig_customer_data_object');

        if ($customerData instanceof CustomerInterface && $origCustomerData instanceof CustomerInterface) {
            return [
                (string)$origCustomerData->getEmail(),
                (string)$customerData->getEmail(),
            ];
        }

        $customer = $event->getCustomer();
        if (!is_object($customer) || !method_exists($customer, 'getEmail')) {
            return ['', ''];
        }

        $oldEmail = method_exists($customer, 'getOrigData') ? (string)$customer->getOrigData('email') : '';

        return [$oldEmail, (string)$customer->getEmail()];
    }
}
