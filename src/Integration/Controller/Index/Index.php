<?php

namespace Flashy\Integration\Controller\Index;

use Flashy\Integration\Helper\Data;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;

class Index extends Action
{
    /**
     * @var Data
     */
    public $helper;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param Data $helper
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context     $context,
        Data        $helper,
        JsonFactory $resultJsonFactory
    )
    {
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * Execute index action
     *
     * @return ResponseInterface|Json|ResultInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $flashy_key = $this->getRequest()->getParam('flashy_key');
        $store_id = $this->getRequest()->getParam('store_id', 0);
        $export_type = $this->getRequest()->getParam('export', 'products');

        if ( trim($this->helper->getFlashyKey()) === $flashy_key ) {
            $limit = $this->getRequest()->getParam('limit');
            $page = $this->getRequest()->getParam('page');
            $total = 0;
            switch ($export_type) {
                case 'products':
                    $resultArray = $this->helper->exportProducts($store_id, $limit, $page);
                    break;
                case 'contacts':
                    $resultArray = $this->helper->exportContacts($store_id, $limit, $page);
                    break;
                case 'contactUnsubscribe':
                    $unsubscriberEmail = $this->getRequest()->getParam('email');
                    $subscription = $this->helper->unsubscribeContactByEmail(
                        $unsubscriberEmail,
                        $store_id != 0 ? $store_id : 1
                    );
                    $resultArray = array('success' => $subscription);
                    break;
                case 'orders':
                    $resultArray = $this->helper->exportOrders($store_id, $limit, $page);
                    break;
                case 'logs':
                    $resultArray = $this->helper->exportLogFile($store_id);
                    break;
                case 'resetLogs':
                    $this->helper->clearLogs();
                    $resultArray = 'Logs deleted';
                    break;
                case 'createCoupon':
                    $args = $this->getRequest()->getParam('args');
                    $args = json_decode(base64_decode($args), true);
                    $resultArray = $this->helper->createCoupon($args);
                    break;
                case 'info':
                    $resultArray = $this->helper->exportInfo($store_id);
                case 'reset':
                    //TODO add reset function
                    break;
                case 'categories':
                    $resultArray = $this->helper->exportCategories($store_id);
                    break;
                default:
                    $result->setStatusHeader(401);
                    $resultArray = array("success" => false, "error" => true, "message" => "Export type is not supported.");
                    return $result->setData($resultArray);
            }
            //$resultArray = array("data" => $export_data, "store_id" => $store_id, "total"=> $total, "success" => true);
        } else {
            $result->setStatusHeader(401);
            $resultArray = array("success" => false, "error" => true, "message" => "You are not authorized to view the content.");
        }
        return $result->setData($resultArray);
    }
}
