<?php

namespace Flashy\Integration\Block;

use Flashy\Integration\Helper\Data;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Tracking extends Template
{
    /**
     * @var Data
     */
    public $helper;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data    $helper,
        array   $data = []
    )
    {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Get flashy id.
     *
     * @return mixed
     */
    public function getFlashyId()
    {
        return $this->helper->getFlashyId();
    }

    public function getFlashyVersion()
    {
        return $this->helper->getFlashyVersion();
    }

	public function getFlashyJs()
    {
        return $this->helper->getFlashyJs();
    }

    /**
     * Get cart data.
     *
     * @return false|string
     */
    public function getCart()
    {
        return $this->helper->getCart();
    }

    /**
     * Get flashy cart cache
     *
     * @return null|string
     */
    public function getFlashyCartCache()
    {
        return $this->helper->getFlashyCartCache();
    }

    /**
     * Set flashy cart cache
     */
    public function setFlashyCartCache()
    {
        return $this->helper->setFlashyCartCache();
    }

    /**
     * Get flashy id from cookie.
     *
     * @return null|string
     */
    public function getFlashyIdCookie()
    {
        return $this->helper->getFlashyIdCookie();
    }

    /**
     * Check if customer is logged in.
     *
     * @return bool
     */
    public function customerIsLoggedIn()
    {
        return $this->helper->customerIsLoggedIn();
    }

    /**
     * Get customer email.
     *
     * @return string
     */
    public function getCustomerEmail()
    {
        return $this->helper->getCustomerEmail();
    }

    public function isCategoryPage()
    {
        return $this->helper->isCategoryPage();
    }

    public function getCategoryName()
    {
        return $this->helper->getCategoryName();
    }
}
