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

class ToggleBlock extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Frodo_Antifraud::config';

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
     * Toggle customer order blocking.
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
            if ($this->customerStatusManager->isBlocked($customer)) {
                $this->customerStatusManager->unblock($customer);
                $this->messageManager->addSuccessMessage(__('Order placement has been unblocked.'));
            } else {
                $this->customerStatusManager->block($customer);
                $this->messageManager->addSuccessMessage(__('Order placement has been blocked.'));
            }
        } catch (NoSuchEntityException $exception) {
            $this->messageManager->addErrorMessage(__('The customer no longer exists.'));
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $redirect;
    }
}
