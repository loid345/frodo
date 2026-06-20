<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model;

use Magento\Framework\Model\AbstractModel;
use Frodo\Antifraud\Model\ResourceModel\LimitedEmail as LimitedEmailResource;

class LimitedEmail extends AbstractModel
{
    /**
     * Initialize resource model.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(LimitedEmailResource::class);
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
     * Get limit expiration timestamp.
     *
     * @return string|null
     */
    public function getExpiresAt(): ?string
    {
        return $this->getData('expires_at');
    }

    /**
     * Set limit expiration timestamp.
     *
     * @param string $expiresAt
     * @return $this
     */
    public function setExpiresAt(string $expiresAt): self
    {
        return $this->setData('expires_at', $expiresAt);
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
