<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model\ResourceModel\ActionLog;

use Frodo\Antifraud\Model\ActionLog as ActionLogModel;
use Frodo\Antifraud\Model\ResourceModel\ActionLog as ActionLogResource;
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
        $this->_init(ActionLogModel::class, ActionLogResource::class);
    }
}
