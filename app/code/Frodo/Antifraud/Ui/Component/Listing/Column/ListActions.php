<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class ListActions extends Column
{
    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * Initialize column dependencies.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Prepare action links for each row.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $deleteUrlPath = $this->getData('config/deleteUrlPath') ?: '';
        $idFieldName = $this->getData('config/idFieldName') ?: 'entity_id';

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item[$idFieldName])) {
                continue;
            }

            $item[$this->getData('name')] = [
                'delete' => [
                    'href' => $this->urlBuilder->getUrl($deleteUrlPath, [
                        $idFieldName => $item[$idFieldName],
                    ]),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete'),
                        'message' => __('Are you sure you want to delete this entry?'),
                    ],
                ],
            ];
        }

        return $dataSource;
    }
}
