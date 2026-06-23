<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Controller\Adminhtml\BlacklistIp;

use Frodo\Antifraud\Model\ActionLogger;
use Frodo\Antifraud\Model\BlacklistIpRepository;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;

class Delete extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Frodo_Antifraud::lists';
    private BlacklistIpRepository $repository;
    private ActionLogger $actionLogger;

    public function __construct(Action\Context $context, BlacklistIpRepository $repository, ActionLogger $actionLogger)
    {
        parent::__construct($context);
        $this->repository = $repository;
        $this->actionLogger = $actionLogger;
    }

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
            $ip = $entity->getIpAddress();
            $this->repository->delete($entity);
            $this->actionLogger->log('ip_blacklist_remove', 'ip', $ip, null, 'Removed via admin grid');
            $this->messageManager->addSuccessMessage(__('The entry has been deleted.'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }
        return $redirect->setPath('*/*/index');
    }
}
