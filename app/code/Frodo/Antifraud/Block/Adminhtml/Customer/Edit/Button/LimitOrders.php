<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Block\Adminhtml\Customer\Edit\Button;

use Frodo\Antifraud\Model\CustomerStatusManager;
use Magento\Backend\Model\UrlInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class LimitOrders implements ButtonProviderInterface
{
    public const ADMIN_RESOURCE = 'Frodo_Antifraud::config';

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
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

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
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        RequestInterface $request,
        AuthorizationInterface $authorization,
        UrlInterface $urlBuilder,
        CustomerStatusManager $customerStatusManager,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->request = $request;
        $this->authorization = $authorization;
        $this->urlBuilder = $urlBuilder;
        $this->customerStatusManager = $customerStatusManager;
        $this->customerRepository = $customerRepository;
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

        $customer = $this->getCustomer($customerId);
        if (!$customer instanceof CustomerInterface) {
            return [];
        }

        $isLimited = $this->customerStatusManager->isLimited($customer);
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

    /**
     * Get customer by ID for button state lookup.
     *
     * @param int $customerId
     * @return CustomerInterface|null
     */
    private function getCustomer(int $customerId): ?CustomerInterface
    {
        try {
            return $this->customerRepository->getById($customerId);
        } catch (LocalizedException $exception) {
            return null;
        }
    }
}
