<?php

namespace Flashy\Integration\Controller\Cart;

use Flashy\Integration\Helper\Data;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;

$objectManager = ObjectManager::getInstance();
$productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
$v = explode('.', $productMetadata->getVersion());

if ($v[1] > 2) {
    class Restore extends Action implements CsrfAwareActionInterface
    {
        /**
         * @var Data
         */
        public $helper;

		/**
         * @var CookieManagerInterface
         */
        public $_cookieManager;

        /**
         * Restore constructor.
         *
         * @param Context $context
         * @param Data $helper
		 * @param CookieManagerInterface $_cookieManager
         */
        public function __construct(
            Context $context,
            Data    $helper,
			CookieManagerInterface $_cookieManager
        )
        {
            $this->helper = $helper;
			$this->_cookieManager = $_cookieManager;
            parent::__construct($context);
        }

        public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
        {
            return null;
        }

        public function validateForCsrf(RequestInterface $request): ?bool
        {
            return true;
        }

        /**
         * Execute restore action
         *
         * @return ResponseInterface|ResultInterface|void
         */
        public function execute()
        {
			$key = $this->getRequest()->getParam('flsid', 0);

            if( empty($key) )
				$key = $this->_cookieManager->getCookie('fls_id');

			if( empty($key) )
				$key = $this->getRequest()->getParam('flashy', 0);

			if( empty($key) )
				$key = $this->_cookieManager->getCookie('flashy_id');

			if( empty($key) )
			{
				$key = $this->getRequest()->getParam('email', 0);

				$key = base64_encode(urldecode($key));
			}

            $this->helper->restoreFlashyCartHash($key);

            $this->getResponse()->setRedirect('/checkout/cart/index');
        }
    }
} else {
    class Restore extends Action
    {
        /**
         * @var Data
         */
        public $helper;

		/**
         * @var CookieManagerInterface
         */
        public $_cookieManager;

        /**
         * Restore constructor.
         *
         * @param Context $context
         * @param Data $helper
		 * @param CookieManagerInterface $_cookieManager
         */
        public function __construct(
            Context $context,
            Data    $helper,
			CookieManagerInterface $_cookieManager
        )
        {
            $this->helper = $helper;
			$this->_cookieManager = $_cookieManager;
            parent::__construct($context);
        }

        /**
         * Execute restore action
         *
         * @return ResponseInterface|ResultInterface|void
         */
        public function execute()
        {
            $key = $this->getRequest()->getParam('flsid', 0);

            if( empty($key) )
				$key = $this->_cookieManager->getCookie('fls_id');

            if( empty($key) )
				$key = $this->_cookieManager->getCookie('flashy_id');

			if( empty($key) )
				$key = $this->getRequest()->getParam('flashy', 0);

			if( empty($key) )
				$key = $this->_cookieManager->getCookie('flashy_id');

			if( empty($key) )
			{
				$key = $this->getRequest()->getParam('email', 0);

				$key = base64_encode(urldecode($key));
			}

            $this->helper->restoreFlashyCartHash($key);

            $this->getResponse()->setRedirect('/checkout/cart/index');
        }
    }
}
