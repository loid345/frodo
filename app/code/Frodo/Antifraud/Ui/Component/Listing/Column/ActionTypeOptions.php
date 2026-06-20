<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Ui\Component\Listing\Column;

use Magento\Framework\Data\OptionSourceInterface;

class ActionTypeOptions implements OptionSourceInterface
{
    /**
     * Get action type options for the grid filter dropdown.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'blacklist_add', 'label' => __('Email Blacklisted')],
            ['value' => 'blacklist_remove', 'label' => __('Email Unblacklisted')],
            ['value' => 'whitelist_add', 'label' => __('Email Whitelisted')],
            ['value' => 'whitelist_remove', 'label' => __('Email Removed from Whitelist')],
            ['value' => 'limit_add', 'label' => __('Temporary Limit Applied')],
            ['value' => 'limit_remove', 'label' => __('Temporary Limit Removed')],
            ['value' => 'ip_blacklist_add', 'label' => __('IP Blacklisted')],
            ['value' => 'ip_blacklist_remove', 'label' => __('IP Unblacklisted')],
            ['value' => 'email_sync', 'label' => __('Email Synced')],
            ['value' => 'order_blocked', 'label' => __('Order Blocked')],
            ['value' => 'migration', 'label' => __('Data Migration')],
        ];
    }
}
