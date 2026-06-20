<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model;

use Magento\Framework\Model\AbstractModel;
use Frodo\Antifraud\Model\ResourceModel\BlacklistIp as BlacklistIpResource;

class BlacklistIp extends AbstractModel
{
    /**
     * Initialize resource model.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(BlacklistIpResource::class);
    }

    /**
     * Get IP address.
     *
     * @return string
     */
    public function getIpAddress(): string
    {
        return (string)$this->getData('ip_address');
    }

    /**
     * Set IP address.
     *
     * @param string $ipAddress
     * @return $this
     */
    public function setIpAddress(string $ipAddress): self
    {
        return $this->setData('ip_address', $ipAddress);
    }

    /**
     * Get store ID.
     *
     * @return int
     */
    public function getStoreId(): int
    {
        return (int)$this->getData('store_id');
    }

    /**
     * Set store ID.
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId(int $storeId): self
    {
        return $this->setData('store_id', $storeId);
    }

    /**
     * Get block reason.
     *
     * @return string|null
     */
    public function getReason(): ?string
    {
        return $this->getData('reason');
    }

    /**
     * Set block reason.
     *
     * @param string|null $reason
     * @return $this
     */
    public function setReason(?string $reason): self
    {
        return $this->setData('reason', $reason);
    }

    /**
     * Get created at timestamp.
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData('created_at');
    }

    /**
     * Get updated at timestamp.
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData('updated_at');
    }
}
