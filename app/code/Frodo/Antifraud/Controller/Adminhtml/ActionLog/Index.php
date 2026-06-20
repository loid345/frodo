<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Controller\Adminhtml\ActionLog;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action implements HttpGetActionInterface
{
    protected const ADMIN_RESOURCE = 'Frodo_Antifraud::action_log';

    /**
     * @var PageFactory
     */
    private PageFactory $pageFactory;

    /**
     * Initialize controller dependencies.
     *
     * @param Action\Context $context
     * @param PageFactory $pageFactory
     */
    public function __construct(Action\Context $context, PageFactory $pageFactory)
    {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
    }

    /**
     * Render action log grid.
     *
     * @return Page
     */
    public function execute(): Page
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('Frodo_Antifraud::action_log');
        $page->getConfig()->getTitle()->prepend(__('Antifraud Action Log'));

        return $page;
    }
}
