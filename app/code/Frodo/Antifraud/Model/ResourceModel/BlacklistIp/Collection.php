<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model\ResourceModel\BlacklistIp;

use Frodo\Antifraud\Model\BlacklistIp as BlacklistIpModel;
use Frodo\Antifraud\Model\ResourceModel\BlacklistIp as BlacklistIpResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Initialize model and resource model.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(BlacklistIpModel::class, BlacklistIpResource::class);
    }
}
