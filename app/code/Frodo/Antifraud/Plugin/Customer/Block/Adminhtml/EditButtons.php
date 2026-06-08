<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Plugin\Customer\Block\Adminhtml;

use Frodo\Antifraud\Model\CustomerStatusManager;
use Magento\Customer\Block\Adminhtml\Edit;

class EditButtons
{
    /**
     * @var CustomerStatusManager
     */
    private CustomerStatusManager $customerStatusManager;

    /**
     * Initialize plugin dependencies.
     *
     * @param CustomerStatusManager $customerStatusManager
     */
    public function __construct(CustomerStatusManager $customerStatusManager)
    {
        $this->customerStatusManager = $customerStatusManager;
    }

    /**
     * Add antifraud action buttons to the customer edit page.
     *
     * @param Edit $subject
     * @param Edit $result
     * @return Edit
     */
    public function afterSetLayout(Edit $subject, Edit $result): Edit
    {
        $customerId = (int)$subject->getRequest()->getParam('id');
        if ($customerId <= 0) {
            return $result;
        }

        $this->addBlockButton($subject, $customerId);
        $this->addLimitButton($subject, $customerId);

        return $result;
    }

    /**
     * Add the block/unblock button.
     *
     * @param Edit $block
     * @param int $customerId
     * @return void
     */
    private function addBlockButton(Edit $block, int $customerId): void
    {
        $isBlocked = $this->customerStatusManager->isBlocked($customerId);
        $block->addButton(
            'frodo_antifraud_toggle_block',
            [
                'label' => $isBlocked ? __('Unblock Orders') : __('Block Orders'),
                'class' => $isBlocked ? 'action-secondary' : 'action-secondary scalable delete',
                'onclick' => sprintf(
                    "setLocation('%s')",
                    $block->getUrl('frodo_antifraud/customer/toggleBlock', ['customer_id' => $customerId])
                ),
            ],
            -1,
            20
        );
    }

    /**
     * Add the temporary limit/remove limit button.
     *
     * @param Edit $block
     * @param int $customerId
     * @return void
     */
    private function addLimitButton(Edit $block, int $customerId): void
    {
        $isLimited = $this->customerStatusManager->isLimited($customerId);
        $block->addButton(
            'frodo_antifraud_toggle_limit',
            [
                'label' => $isLimited ? __('Remove Daily Limit') : __('Limit for 24 Hours'),
                'class' => 'action-secondary',
                'onclick' => sprintf(
                    "setLocation('%s')",
                    $block->getUrl('frodo_antifraud/customer/toggleLimit', ['customer_id' => $customerId])
                ),
            ],
            -1,
            25
        );
    }
}
