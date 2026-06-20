<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model\ResourceModel\LimitedEmail;

use Frodo\Antifraud\Model\LimitedEmail as LimitedEmailModel;
use Frodo\Antifraud\Model\ResourceModel\LimitedEmail as LimitedEmailResource;
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
        $this->_init(LimitedEmailModel::class, LimitedEmailResource::class);
    }
}
