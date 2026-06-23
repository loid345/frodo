<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Controller\Adminhtml\WhitelistEmail;

use Frodo\Antifraud\Model\ActionLogger;
use Frodo\Antifraud\Model\WhitelistEmailFactory;
use Frodo\Antifraud\Model\WhitelistEmailRepository;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Frodo_Antifraud::lists';

    /**
     * @var WhitelistEmailRepository
     */
    private WhitelistEmailRepository $repository;

    /**
     * @var ActionLogger
     */
    private ActionLogger $actionLogger;

    /**
     * @var WhitelistEmailFactory
     */
    private WhitelistEmailFactory $entityFactory;

    /**
     * Initialize controller dependencies.
     *
     * @param Action\Context $context
     * @param WhitelistEmailRepository $repository
     * @param ActionLogger $actionLogger
     * @param WhitelistEmailFactory $entityFactory
     */
    public function __construct(
        Action\Context $context,
        WhitelistEmailRepository $repository,
        ActionLogger $actionLogger,
        WhitelistEmailFactory $entityFactory
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
        if (empty($data['email'])) {
            $this->messageManager->addErrorMessage(__('Email address is required.'));
            return $redirect->setPath('*/*/index');
        }
        $email = strtolower(trim($data['email']));
        try {
            $entityId = isset($data['entity_id']) ? (int)$data['entity_id'] : 0;
            $entity = $entityId > 0
                ? $this->repository->getById($entityId)
                : $this->entityFactory->create();
            $entity->setEmail($email);
            $entity->setReason($data['reason'] ?? null);
            $this->repository->save($entity);
            $this->actionLogger->log(
                'whitelist_add',
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
