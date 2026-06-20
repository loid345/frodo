<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model\ResourceModel\WhitelistEmail;

use Frodo\Antifraud\Model\WhitelistEmail as WhitelistEmailModel;
use Frodo\Antifraud\Model\ResourceModel\WhitelistEmail as WhitelistEmailResource;
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
        $this->_init(WhitelistEmailModel::class, WhitelistEmailResource::class);
    }
}
