<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model;

use Frodo\Antifraud\Model\ResourceModel\ActionLog as ActionLogResource;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Psr\Log\LoggerInterface;

class ActionLogger
{
    /**
     * @var ActionLogResource
     */
    private ActionLogResource $resource;

    /**
     * @var ActionLogFactory
     */
    private ActionLogFactory $actionLogFactory;

    /**
     * @var AdminSession
     */
    private AdminSession $adminSession;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Initialize action logger dependencies.
     *
     * @param ActionLogResource $resource
     * @param ActionLogFactory $actionLogFactory
     * @param AdminSession $adminSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        ActionLogResource $resource,
        ActionLogFactory $actionLogFactory,
        AdminSession $adminSession,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->actionLogFactory = $actionLogFactory;
        $this->adminSession = $adminSession;
        $this->logger = $logger;
    }

    /**
     * Record an antifraud action in the audit log.
     *
     * Automatically resolves the current admin user ID from the backend session.
     * If no admin user is logged in (e.g. observer on storefront), admin_user_id will be null.
     *
     * @param string $actionType
     * @param string $targetType
     * @param string $targetValue
     * @param int|null $customerId
     * @param string|null $details
     * @return void
     */
    public function log(
        string $actionType,
        string $targetType,
        string $targetValue,
        ?int $customerId = null,
        ?string $details = null
    ): void {
        try {
            $adminUserId = null;
            $adminUser = $this->adminSession->getUser();
            if ($adminUser !== null) {
                $adminUserId = (int)$adminUser->getId();
            }

            $entry = $this->actionLogFactory->create();
            $entry->setActionType($actionType);
            $entry->setTargetType($targetType);
            $entry->setTargetValue($targetValue);
            $entry->setAdminUserId($adminUserId);
            $entry->setCustomerId($customerId);
            $entry->setDetails($details);

            $this->resource->save($entry);
        } catch (\Exception $exception) {
            $this->logger->error('Frodo Antifraud: failed to write action log', [
                'action_type' => $actionType,
                'target_value' => $targetValue,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
