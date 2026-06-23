<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Controller\Adminhtml\LimitedEmail;

use Frodo\Antifraud\Model\ActionLogger;
use Frodo\Antifraud\Model\LimitedEmailRepository;
use Frodo\Antifraud\Model\ResourceModel\LimitedEmail\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Frodo_Antifraud::lists';
    private Filter $filter;
    private CollectionFactory $collectionFactory;
    private LimitedEmailRepository $repository;
    private ActionLogger $actionLogger;

    public function __construct(Action\Context $context, Filter $filter, CollectionFactory $collectionFactory, LimitedEmailRepository $repository, ActionLogger $actionLogger)
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
                $this->actionLogger->log('limit_remove', 'email', $entity->getEmail(), null, 'Mass delete');
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
