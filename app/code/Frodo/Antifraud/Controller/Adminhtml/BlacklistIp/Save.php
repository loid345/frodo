<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Controller\Adminhtml\BlacklistIp;

use Frodo\Antifraud\Model\ActionLogger;
use Frodo\Antifraud\Model\BlacklistIpFactory;
use Frodo\Antifraud\Model\BlacklistIpRepository;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Frodo_Antifraud::lists';

    /**
     * @var BlacklistIpRepository
     */
    private BlacklistIpRepository $repository;

    /**
     * @var ActionLogger
     */
    private ActionLogger $actionLogger;

    /**
     * @var BlacklistIpFactory
     */
    private BlacklistIpFactory $entityFactory;

    /**
     * Initialize controller dependencies.
     *
     * @param Action\Context $context
     * @param BlacklistIpRepository $repository
     * @param ActionLogger $actionLogger
     * @param BlacklistIpFactory $entityFactory
     */
    public function __construct(
        Action\Context $context,
        BlacklistIpRepository $repository,
        ActionLogger $actionLogger,
        BlacklistIpFactory $entityFactory
    ) {
        parent::__construct($context);
        $this->repository = $repository;
        $this->actionLogger = $actionLogger;
        $this->entityFactory = $entityFactory;
    }

    /**
     * Process the admin action.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $redirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if (empty($data['ip_address'])) {
            $this->messageManager->addErrorMessage(__('IP address is required.'));
            return $redirect->setPath('*/*/index');
        }
        try {
            $entityId = isset($data['entity_id']) ? (int)$data['entity_id'] : 0;
            $entity = $entityId > 0
                ? $this->repository->getById($entityId)
                : $this->entityFactory->create();
            $entity->setIpAddress(trim($data['ip_address']));
            $entity->setStoreId((int)($data['store_id'] ?? 0));
            $entity->setReason($data['reason'] ?? null);
            $this->repository->save($entity);
            $this->actionLogger->log(
                'ip_blacklist_add',
                'ip',
                trim($data['ip_address']),
                null,
                $data['reason'] ?? null
            );
            $this->messageManager->addSuccessMessage(__('The IP address has been saved.'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }
        return $redirect->setPath('*/*/index');
    }
}
