<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Controller\Adminhtml\Customer;

use Frodo\Antifraud\Model\CustomerStatusManager;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class ToggleLimit extends Action implements HttpGetActionInterface
{
    protected const ADMIN_RESOURCE = 'Frodo_Antifraud::config';

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var CustomerStatusManager
     */
    private CustomerStatusManager $customerStatusManager;

    /**
     * Initialize controller dependencies.
     *
     * @param Context $context
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerStatusManager $customerStatusManager
     */
    public function __construct(
        Context $context,
        CustomerRepositoryInterface $customerRepository,
        CustomerStatusManager $customerStatusManager
    ) {
        parent::__construct($context);
        $this->customerRepository = $customerRepository;
        $this->customerStatusManager = $customerStatusManager;
    }

    /**
     * Toggle customer temporary daily-limit restriction.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $customerId = (int)$this->getRequest()->getParam('customer_id');
        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('customer/index/edit', ['id' => $customerId]);

        if ($customerId <= 0) {
            $this->messageManager->addErrorMessage(__('Customer is missing.'));
            return $redirect;
        }

        try {
            $customer = $this->customerRepository->getById($customerId);
            if ($this->customerStatusManager->isLimited($customer)) {
                $this->customerStatusManager->removeLimitAndWhitelist($customer);
                $this->messageManager->addSuccessMessage(
                    __('Daily limit has been removed and the customer email has been whitelisted.')
                );
            } else {
                $this->customerStatusManager->limitForOneDay($customer);
                $this->messageManager->addSuccessMessage(__('Daily limit has been applied for 24 hours.'));
            }
        } catch (NoSuchEntityException $exception) {
            $this->messageManager->addErrorMessage(__('The customer no longer exists.'));
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $redirect;
    }
}
