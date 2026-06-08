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

class BlockOrders implements ButtonProviderInterface
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
     * Get block/unblock button data.
     *
     * @return array<string,mixed>
     */
    public function getButtonData(): array
    {
        $customerId = (int)$this->request->getParam('id');
        if ($customerId <= 0 || !$this->authorization->isAllowed(self::ADMIN_RESOURCE)) {
            return [];
        }

        $isBlocked = $this->customerStatusManager->isBlocked($customerId);
        $url = $this->urlBuilder->getUrl('frodo_antifraud/customer/toggleBlock', [
            'customer_id' => $customerId,
        ]);

        return [
            'label' => $isBlocked ? __('Unblock Orders') : __('Block Orders'),
            'class' => $isBlocked ? 'secondary' : 'secondary delete',
            'on_click' => sprintf("location.href = '%s';", $url),
            'sort_order' => 80,
        ];
    }
}
