<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model\ResourceModel\BlacklistEmail;

use Frodo\Antifraud\Model\BlacklistEmail as BlacklistEmailModel;
use Frodo\Antifraud\Model\ResourceModel\BlacklistEmail as BlacklistEmailResource;
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
        $this->_init(BlacklistEmailModel::class, BlacklistEmailResource::class);
    }
}
