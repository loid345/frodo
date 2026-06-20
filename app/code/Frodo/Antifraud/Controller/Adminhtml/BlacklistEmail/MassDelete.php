<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Controller\Adminhtml\BlacklistEmail;

use Frodo\Antifraud\Model\ActionLogger;
use Frodo\Antifraud\Model\BlacklistEmailRepository;
use Frodo\Antifraud\Model\ResourceModel\BlacklistEmail\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends Action implements HttpPostActionInterface
{
    protected const ADMIN_RESOURCE = 'Frodo_Antifraud::lists';

    /**
     * @var Filter
     */
    private Filter $filter;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var BlacklistEmailRepository
     */
    private BlacklistEmailRepository $repository;

    /**
     * @var ActionLogger
     */
    private ActionLogger $actionLogger;

    /**
     * Initialize controller dependencies.
     *
     * @param Action\Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param BlacklistEmailRepository $repository
     * @param ActionLogger $actionLogger
     */
    public function __construct(
        Action\Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        BlacklistEmailRepository $repository,
        ActionLogger $actionLogger
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->repository = $repository;
        $this->actionLogger = $actionLogger;
    }

    /**
     * Mass delete blacklist email entries.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $redirect = $this->resultRedirectFactory->create();

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $count = 0;
            foreach ($collection as $entity) {
                $this->actionLogger->log('blacklist_remove', 'email', $entity->getEmail(), null, 'Mass delete');
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
