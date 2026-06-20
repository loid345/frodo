<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class LimitedEmail extends AbstractDb
{
    /**
     * Initialize table and primary key.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('frodo_antifraud_limited_email', 'entity_id');
    }
}
