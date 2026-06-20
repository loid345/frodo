<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class BlacklistIp extends AbstractDb
{
    /**
     * Initialize table and primary key.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('frodo_antifraud_blacklist_ip', 'entity_id');
    }
}
