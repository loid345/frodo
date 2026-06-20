<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model;

use Magento\Framework\Model\AbstractModel;
use Frodo\Antifraud\Model\ResourceModel\WhitelistEmail as WhitelistEmailResource;

class WhitelistEmail extends AbstractModel
{
    /**
     * Initialize resource model.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(WhitelistEmailResource::class);
    }

    /**
     * Get email address.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return (string)$this->getData('email');
    }

    /**
     * Set email address.
     *
     * @param string $email
     * @return $this
     */
    public function setEmail(string $email): self
    {
        return $this->setData('email', $email);
    }

    /**
     * Get whitelist reason.
     *
     * @return string|null
     */
    public function getReason(): ?string
    {
        return $this->getData('reason');
    }

    /**
     * Set whitelist reason.
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
