<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model;

use Frodo\Antifraud\Model\ResourceModel\BlacklistEmail as BlacklistEmailResource;
use Frodo\Antifraud\Model\ResourceModel\BlacklistEmail\CollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class BlacklistEmailRepository
{
    /**
     * @var BlacklistEmailResource
     */
    private BlacklistEmailResource $resource;

    /**
     * @var BlacklistEmailFactory
     */
    private BlacklistEmailFactory $entityFactory;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * Initialize repository dependencies.
     *
     * @param BlacklistEmailResource $resource
     * @param BlacklistEmailFactory $entityFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        BlacklistEmailResource $resource,
        BlacklistEmailFactory $entityFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->resource = $resource;
        $this->entityFactory = $entityFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Save a blacklist email entry.
     *
     * @param BlacklistEmail $entity
     * @return BlacklistEmail
     * @throws CouldNotSaveException
     */
    public function save(BlacklistEmail $entity): BlacklistEmail
    {
        try {
            $this->resource->save($entity);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__('Could not save blacklist email entry: %1', $exception->getMessage()));
        }

        return $entity;
    }

    /**
     * Get blacklist email entry by ID.
     *
     * @param int $entityId
     * @return BlacklistEmail
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): BlacklistEmail
    {
        $entity = $this->entityFactory->create();
        $this->resource->load($entity, $entityId);
        if (!$entity->getId()) {
            throw new NoSuchEntityException(__('Blacklist email entry with ID "%1" does not exist.', $entityId));
        }

        return $entity;
    }

    /**
     * Find a blacklist email entry by email address.
     *
     * @param string $email
     * @return BlacklistEmail|null
     */
    public function getByEmail(string $email): ?BlacklistEmail
    {
        $entity = $this->entityFactory->create();
        $this->resource->load($entity, strtolower(trim($email)), 'email');

        return $entity->getId() ? $entity : null;
    }

    /**
     * Check whether the email is in the blacklist.
     *
     * @param string $email
     * @return bool
     */
    public function emailExists(string $email): bool
    {
        return $this->getByEmail($email) !== null;
    }

    /**
     * Delete a blacklist email entry.
     *
     * @param BlacklistEmail $entity
     * @return void
     * @throws CouldNotDeleteException
     */
    public function delete(BlacklistEmail $entity): void
    {
        try {
            $this->resource->delete($entity);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete blacklist email entry: %1', $exception->getMessage())
            );
        }
    }

    /**
     * Delete a blacklist email entry by email address.
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
