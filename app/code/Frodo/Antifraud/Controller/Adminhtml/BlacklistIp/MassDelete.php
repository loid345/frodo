<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Controller\Adminhtml\BlacklistIp;

use Frodo\Antifraud\Model\ActionLogger;
use Frodo\Antifraud\Model\BlacklistIpRepository;
use Frodo\Antifraud\Model\ResourceModel\BlacklistIp\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends Action implements HttpPostActionInterface
{
    protected const ADMIN_RESOURCE = 'Frodo_Antifraud::lists';
    private Filter $filter;
    private CollectionFactory $collectionFactory;
    private BlacklistIpRepository $repository;
    private ActionLogger $actionLogger;

    public function __construct(Action\Context $context, Filter $filter, CollectionFactory $collectionFactory, BlacklistIpRepository $repository, ActionLogger $actionLogger)
    {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->repository = $repository;
        $this->actionLogger = $actionLogger;
    }

    public function execute(): Redirect
    {
        $redirect = $this->resultRedirectFactory->create();
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $count = 0;
            foreach ($collection as $entity) {
                $this->actionLogger->log('ip_blacklist_remove', 'ip', $entity->getIpAddress(), null, 'Mass delete');
                $this->repository->delete($entity);
                $count++;
            }
            $this->messageManager->addSuccessMessage(__('A total of %1 entry(ies) have been deleted.', $count));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }
        return $redirect->setPath('*/*/index');
    }
}
