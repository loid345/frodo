<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Controller\Adminhtml\LimitedEmail;

use Frodo\Antifraud\Model\ActionLogger;
use Frodo\Antifraud\Model\LimitedEmailRepository;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;

class Delete extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Frodo_Antifraud::lists';

    /**
     * @var LimitedEmailRepository
     */
    private LimitedEmailRepository $repository;

    /**
     * @var ActionLogger
     */
    private ActionLogger $actionLogger;

    /**
     * Initialize controller dependencies.
     *
     * @param Action\Context $context
     * @param LimitedEmailRepository $repository
     * @param ActionLogger $actionLogger
     */
    public function __construct(
        Action\Context $context,
        LimitedEmailRepository $repository,
        ActionLogger $actionLogger
    ) {
        parent::__construct($context);
        $this->repository = $repository;
        $this->actionLogger = $actionLogger;
    }

    /**
     * Process the admin action.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $redirect = $this->resultRedirectFactory->create();
        $entityId = (int)$this->getRequest()->getParam('entity_id');
        if ($entityId <= 0) {
            $this->messageManager->addErrorMessage(__('Entry not found.'));
            return $redirect->setPath('*/*/index');
        }
        try {
            $entity = $this->repository->getById($entityId);
            $email = $entity->getEmail();
            $this->repository->delete($entity);
            $this->actionLogger->log('limit_remove', 'email', $email, null, 'Removed via admin grid');
            $this->messageManager->addSuccessMessage(__('The entry has been deleted.'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }
        return $redirect->setPath('*/*/index');
    }
}
