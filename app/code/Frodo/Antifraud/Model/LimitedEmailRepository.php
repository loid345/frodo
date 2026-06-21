<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model;

use DateTimeImmutable;
use DateTimeZone;
use Frodo\Antifraud\Model\ResourceModel\LimitedEmail as LimitedEmailResource;
use Frodo\Antifraud\Model\ResourceModel\LimitedEmail\CollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class LimitedEmailRepository
{
    private const UTC_TIMEZONE = 'UTC';

    /**
     * @var LimitedEmailResource
     */
    private LimitedEmailResource $resource;

    /**
     * @var LimitedEmailFactory
     */
    private LimitedEmailFactory $entityFactory;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * Initialize repository dependencies.
     *
     * @param LimitedEmailResource $resource
     * @param LimitedEmailFactory $entityFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        LimitedEmailResource $resource,
        LimitedEmailFactory $entityFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->resource = $resource;
        $this->entityFactory = $entityFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Save a limited email entry.
     *
     * @param LimitedEmail $entity
     * @return LimitedEmail
     * @throws CouldNotSaveException
     */
    public function save(LimitedEmail $entity): LimitedEmail
    {
        try {
            $this->resource->save($entity);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save limited email entry: %1', $exception->getMessage())
            );
        }

        return $entity;
    }

    /**
     * Get limited email entry by ID.
     *
     * @param int $entityId
     * @return LimitedEmail
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): LimitedEmail
    {
        $entity = $this->entityFactory->create();
        $this->resource->load($entity, $entityId);
        if (!$entity->getId()) {
            throw new NoSuchEntityException(__('Limited email entry with ID "%1" does not exist.', $entityId));
        }

        return $entity;
    }

    /**
     * Find a limited email entry by email address.
     *
     * @param string $email
     * @return LimitedEmail|null
     */
    public function getByEmail(string $email): ?LimitedEmail
    {
        $entity = $this->entityFactory->create();
        $this->resource->load($entity, strtolower(trim($email)), 'email');

        return $entity->getId() ? $entity : null;
    }

    /**
     * Check whether the email has an active (non-expired) temporary limit.
     *
     * @param string $email
     * @return bool
     */
    public function isActiveLimited(string $email): bool
    {
        $entity = $this->getByEmail($email);
        if ($entity === null) {
            return false;
        }

        try {
            $expiresAt = new DateTimeImmutable($entity->getExpiresAt(), new DateTimeZone(self::UTC_TIMEZONE));
            $now = new DateTimeImmutable('now', new DateTimeZone(self::UTC_TIMEZONE));

            return $expiresAt > $now;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * Delete a limited email entry.
     *
     * @param LimitedEmail $entity
     * @return void
     * @throws CouldNotDeleteException
     */
    public function delete(LimitedEmail $entity): void
    {
        try {
            $this->resource->delete($entity);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete limited email entry: %1', $exception->getMessage())
            );
        }
    }

    /**
     * Delete a limited email entry by email address.
     *
     * @param string $email
     * @return void
     * @throws CouldNotDeleteException
     */
    public function deleteByEmail(string $email): void
    {
        $entity = $this->getByEmail($email);
        if ($entity !== null) {
            $this->delete($entity);
        }
    }
}
