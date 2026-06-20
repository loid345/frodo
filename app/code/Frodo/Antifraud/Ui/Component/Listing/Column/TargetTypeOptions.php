<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Ui\Component\Listing\Column;

use Magento\Framework\Data\OptionSourceInterface;

class TargetTypeOptions implements OptionSourceInterface
{
    /**
     * Get target type options for the grid filter dropdown.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'email', 'label' => __('Email')],
            ['value' => 'ip', 'label' => __('IP Address')],
        ];
    }
}
