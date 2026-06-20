<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Model;

use Frodo\Antifraud\Model\ResourceModel\BlacklistIp as BlacklistIpResource;
use Frodo\Antifraud\Model\ResourceModel\BlacklistIp\CollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class BlacklistIpRepository
{
    /**
     * @var BlacklistIpResource
     */
    private BlacklistIpResource $resource;

    /**
     * @var BlacklistIpFactory
     */
    private BlacklistIpFactory $entityFactory;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * Initialize repository dependencies.
     *
     * @param BlacklistIpResource $resource
     * @param BlacklistIpFactory $entityFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        BlacklistIpResource $resource,
        BlacklistIpFactory $entityFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->resource = $resource;
        $this->entityFactory = $entityFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Save a blacklist IP entry.
     *
     * @param BlacklistIp $entity
     * @return BlacklistIp
     * @throws CouldNotSaveException
     */
    public function save(BlacklistIp $entity): BlacklistIp
    {
        try {
            $this->resource->save($entity);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__('Could not save blacklist IP entry: %1', $exception->getMessage()));
        }

        return $entity;
    }

    /**
     * Get blacklist IP entry by ID.
     *
     * @param int $entityId
     * @return BlacklistIp
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): BlacklistIp
    {
        $entity = $this->entityFactory->create();
        $this->resource->load($entity, $entityId);
        if (!$entity->getId()) {
            throw new NoSuchEntityException(__('Blacklist IP entry with ID "%1" does not exist.', $entityId));
        }

        return $entity;
    }

    /**
     * Get blocked IPs for the given store IDs.
     *
     * @param int[] $storeIds
     * @return BlacklistIp[]
     */
    public function getByStoreIds(array $storeIds): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('store_id', ['in' => $storeIds]);

        return $collection->getItems();
    }

    /**
     * Check whether the IP is blocked for any of the given store IDs.
     *
     * @param string $ip
     * @param int[] $storeIds
     * @return bool
     */
    public function ipExistsForStores(string $ip, array $storeIds): bool
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('ip_address', trim($ip));
        $collection->addFieldToFilter('store_id', ['in' => $storeIds]);
        $collection->setPageSize(1);

        return $collection->getSize() > 0;
    }

    /**
     * Delete a blacklist IP entry.
     *
     * @param BlacklistIp $entity
     * @return void
     * @throws CouldNotDeleteException
     */
    public function delete(BlacklistIp $entity): void
    {
        try {
            $this->resource->delete($entity);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete blacklist IP entry: %1', $exception->getMessage())
            );
        }
    }
}
