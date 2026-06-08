<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Block\Adminhtml\Customer\Edit\Button;

use Frodo\Antifraud\Model\CustomerStatusManager;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class LimitOrders implements ButtonProviderInterface
{
    private const ADMIN_RESOURCE = 'Frodo_Antifraud::config';

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var AuthorizationInterface
     */
    private AuthorizationInterface $authorization;

    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * @var CustomerStatusManager
     */
    private CustomerStatusManager $customerStatusManager;

    /**
     * Initialize button dependencies.
     *
     * @param RequestInterface $request
     * @param AuthorizationInterface $authorization
     * @param UrlInterface $urlBuilder
     * @param CustomerStatusManager $customerStatusManager
     */
    public function __construct(
        RequestInterface $request,
        AuthorizationInterface $authorization,
        UrlInterface $urlBuilder,
        CustomerStatusManager $customerStatusManager
    ) {
        $this->request = $request;
        $this->authorization = $authorization;
        $this->urlBuilder = $urlBuilder;
        $this->customerStatusManager = $customerStatusManager;
    }

    /**
     * Get temporary limit/remove limit button data.
     *
     * @return array<string,mixed>
     */
    public function getButtonData(): array
    {
        $customerId = (int)$this->request->getParam('id');
        if ($customerId <= 0 || !$this->authorization->isAllowed(self::ADMIN_RESOURCE)) {
            return [];
        }

        $isLimited = $this->customerStatusManager->isLimited($customerId);
        $url = $this->urlBuilder->getUrl('frodo_antifraud/customer/toggleLimit', [
            'customer_id' => $customerId,
        ]);

        return [
            'label' => $isLimited ? __('Remove Daily Limit') : __('Limit for 24 Hours'),
            'class' => 'secondary',
            'on_click' => sprintf("location.href = '%s';", $url),
            'sort_order' => 85,
        ];
    }
}
