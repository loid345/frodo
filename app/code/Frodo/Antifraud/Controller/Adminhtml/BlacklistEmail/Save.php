<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Controller\Adminhtml\BlacklistEmail;

use Frodo\Antifraud\Model\ActionLogger;
use Frodo\Antifraud\Model\BlacklistEmailFactory;
use Frodo\Antifraud\Model\BlacklistEmailRepository;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Frodo_Antifraud::lists';

    /**
     * @var BlacklistEmailRepository
     */
    private BlacklistEmailRepository $repository;

    /**
     * @var ActionLogger
     */
    private ActionLogger $actionLogger;

    /**
     * @var BlacklistEmailFactory
     */
    private BlacklistEmailFactory $entityFactory;

    /**
     * Initialize controller dependencies.
     *
     * @param Action\Context $context
     * @param BlacklistEmailRepository $repository
     * @param ActionLogger $actionLogger
     * @param BlacklistEmailFactory $entityFactory
     */
    public function __construct(
        Action\Context $context,
        BlacklistEmailRepository $repository,
        ActionLogger $actionLogger,
        BlacklistEmailFactory $entityFactory
    ) {
        parent::__construct($context);
        $this->repository = $repository;
        $this->actionLogger = $actionLogger;
        $this->entityFactory = $entityFactory;
    }

    /**
     * Save a blacklist email entry.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $redirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if (empty($data['email'])) {
            $this->messageManager->addErrorMessage(__('Email address is required.'));
            return $redirect->setPath('*/*/index');
        }

        $email = strtolower(trim($data['email']));

        try {
            $entityId = isset($data['entity_id']) ? (int)$data['entity_id'] : 0;
            if ($entityId > 0) {
                $entity = $this->repository->getById($entityId);
            } else {
                $entity = $this->entityFactory->create();
            }

            $entity->setEmail($email);
            $entity->setReason($data['reason'] ?? null);
            $this->repository->save($entity);

            $this->actionLogger->log(
                $entityId > 0 ? 'blacklist_add' : 'blacklist_add',
                'email',
                $email,
                null,
                $data['reason'] ?? null
            );

            $this->messageManager->addSuccessMessage(__('The email has been saved.'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $redirect->setPath('*/*/index');
    }
}
