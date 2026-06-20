<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Block\Adminhtml;

use Magento\Backend\Block\Template;

class GenericAddForm extends Template
{
    /**
     * Get the form action URL.
     *
     * @return string
     */
    public function getSaveUrl(): string
    {
        return $this->getUrl($this->getData('save_url') ?: '');
    }

    /**
     * Get the back button URL.
     *
     * @return string
     */
    public function getBackUrl(): string
    {
        return $this->getUrl($this->getData('back_url') ?: '');
    }

    /**
     * Get configured form fields.
     *
     * @return array
     */
    public function getFields(): array
    {
        return $this->getData('fields') ?: [];
    }
}
