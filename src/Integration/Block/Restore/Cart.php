<?php

namespace Flashy\Integration\Block\Restore;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Cart extends Template
{

    /**
     * Constructor
     *
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array   $data = []
    )
    {
        parent::__construct($context, $data);
    }

    /**
     * Get key from request
     *
     * @return mixed
     */
    public function getKey()
    {
        return $this->getRequest()->getParam('flashy', 0);
    }

    public function getFlsidKey()
    {
        return $this->getRequest()->getParam('flsid', 0);
    }
}
