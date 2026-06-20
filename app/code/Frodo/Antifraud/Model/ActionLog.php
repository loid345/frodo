<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model;

use Magento\Framework\Model\AbstractModel;
use Frodo\Antifraud\Model\ResourceModel\ActionLog as ActionLogResource;

class ActionLog extends AbstractModel
{
    /**
     * Initialize resource model.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ActionLogResource::class);
    }

    /**
     * Get action type.
     *
     * @return string
     */
    public function getActionType(): string
    {
        return (string)$this->getData('action_type');
    }

    /**
     * Set action type.
     *
     * @param string $actionType
     * @return $this
     */
    public function setActionType(string $actionType): self
    {
        return $this->setData('action_type', $actionType);
    }

    /**
     * Get target type (email or ip).
     *
     * @return string
     */
    public function getTargetType(): string
    {
        return (string)$this->getData('target_type');
    }

    /**
     * Set target type.
     *
     * @param string $targetType
     * @return $this
     */
    public function setTargetType(string $targetType): self
    {
        return $this->setData('target_type', $targetType);
    }

    /**
     * Get target value (email address or IP).
     *
     * @return string
     */
    public function getTargetValue(): string
    {
        return (string)$this->getData('target_value');
    }

    /**
     * Set target value.
     *
     * @param string $targetValue
     * @return $this
     */
    public function setTargetValue(string $targetValue): self
    {
        return $this->setData('target_value', $targetValue);
    }

    /**
     * Get admin user ID.
     *
     * @return int|null
     */
    public function getAdminUserId(): ?int
    {
        $value = $this->getData('admin_user_id');

        return $value !== null ? (int)$value : null;
    }

    /**
     * Set admin user ID.
     *
     * @param int|null $adminUserId
     * @return $this
     */
    public function setAdminUserId(?int $adminUserId): self
    {
        return $this->setData('admin_user_id', $adminUserId);
    }

    /**
     * Get customer ID.
     *
     * @return int|null
     */
    public function getCustomerId(): ?int
    {
        $value = $this->getData('customer_id');

        return $value !== null ? (int)$value : null;
    }

    /**
     * Set customer ID.
     *
     * @param int|null $customerId
     * @return $this
     */
    public function setCustomerId(?int $customerId): self
    {
        return $this->setData('customer_id', $customerId);
    }

    /**
     * Get additional details.
     *
     * @return string|null
     */
    public function getDetails(): ?string
    {
        return $this->getData('details');
    }

    /**
     * Set additional details.
     *
     * @param string|null $details
     * @return $this
     */
    public function setDetails(?string $details): self
    {
        return $this->setData('details', $details);
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
}
