<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model;

use Frodo\Antifraud\Model\ResourceModel\WhitelistEmail as WhitelistEmailResource;
use Frodo\Antifraud\Model\ResourceModel\WhitelistEmail\CollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class WhitelistEmailRepository
{
    /**
     * @var WhitelistEmailResource
     */
    private WhitelistEmailResource $resource;

    /**
     * @var WhitelistEmailFactory
     */
    private WhitelistEmailFactory $entityFactory;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * Initialize repository dependencies.
     *
     * @param WhitelistEmailResource $resource
     * @param WhitelistEmailFactory $entityFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        WhitelistEmailResource $resource,
        WhitelistEmailFactory $entityFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->resource = $resource;
        $this->entityFactory = $entityFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Save a whitelist email entry.
     *
     * @param WhitelistEmail $entity
     * @return WhitelistEmail
     * @throws CouldNotSaveException
     */
    public function save(WhitelistEmail $entity): WhitelistEmail
    {
        try {
            $this->resource->save($entity);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save whitelist email entry: %1', $exception->getMessage())
            );
        }

        return $entity;
    }

    /**
     * Get whitelist email entry by ID.
     *
     * @param int $entityId
     * @return WhitelistEmail
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): WhitelistEmail
    {
        $entity = $this->entityFactory->create();
        $this->resource->load($entity, $entityId);
        if (!$entity->getId()) {
            throw new NoSuchEntityException(__('Whitelist email entry with ID "%1" does not exist.', $entityId));
        }

        return $entity;
    }

    /**
     * Find a whitelist email entry by email address.
     *
     * @param string $email
     * @return WhitelistEmail|null
     */
    public function getByEmail(string $email): ?WhitelistEmail
    {
        $entity = $this->entityFactory->create();
        $this->resource->load($entity, strtolower(trim($email)), 'email');

        return $entity->getId() ? $entity : null;
    }

    /**
     * Check whether the email is in the whitelist.
     *
     * @param string $email
     * @return bool
     */
    public function emailExists(string $email): bool
    {
        return $this->getByEmail($email) !== null;
    }

    /**
     * Delete a whitelist email entry.
     *
     * @param WhitelistEmail $entity
     * @return void
     * @throws CouldNotDeleteException
     */
    public function delete(WhitelistEmail $entity): void
    {
        try {
            $this->resource->delete($entity);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete whitelist email entry: %1', $exception->getMessage())
            );
        }
    }

    /**
     * Delete a whitelist email entry by email address.
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
