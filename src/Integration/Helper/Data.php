<?php

namespace Flashy\Integration\Helper;

use Flashy\Flashy;
use Flashy\Helper;
use Flashy\Integration\Logger\Logger;
use Flashy\Integration\Model\CarthashFactory;
use Magento\Catalog\Helper\ImageFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\SalesRule\Model\Coupon;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\SalesRule\Model\Rule\Condition\Combine;
use Magento\SalesRule\Model\Rule\Condition\Product;
use Magento\SalesRule\Model\Rule\Condition\Product\Found;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const COOKIE_DURATION = 86400; // lifetime in seconds

    const FLASHY_ACTIVE_STRING_PATH = 'flashy/flashy/active';

    const FLASHY_ID_STRING_PATH = 'flashy/flashy/flashy_id';

    const FLASHY_CONNECTED_STRING_PATH = 'flashy/flashy/flashy_connected';

    const FLASHY_PURCHASE_STRING_PATH = 'flashy/flashy/purchase';

    const FLASHY_LOG_STRING_PATH = 'flashy/flashy/log';

    const FLASHY_KEY_STRING_PATH = 'flashy/flashy/flashy_key';

    const FLASHY_LIST_STRING_PATH = 'flashy/flashy_lists/flashy_list';

	const FLASHY_ENVIRONMET = 'flashy/flashy/env';

    /**
     * @var CookieManagerInterface
     */
    protected $_cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    protected $_cookieMetadataFactory;

    /**
     * @var Flashy
     */
    public $flashy;

    /**
     * @var mixed
     */
    protected $apiKey;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ProductMetadataInterface
     */
    protected $_productMetadata;

    /**
     * @var WriterInterface
     */
    protected $_configWriter;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var ProductCollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var CustomerCollectionFactory
     */
    protected $_customerCollectionFactory;

    /**
     * @var SubscriberCollectionFactory
     */
    protected $_subscriberCollectionFactory;

    /**
     * @var OrderCollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @var CarthashFactory
     */
    protected $_carthashFactory;

    /**
     * @var Cart
     */
    protected $_cartModel;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var FormKey
     */
    protected $_formKey;

    /**
     * @var ImageFactory
     */
    protected $_imageHelperFactory;

    /**
     * @var Logger
     */
    protected $_flashyLogger;

    /**
     * @var ObjectManager
     */
    protected $_objectManager;

    /**
     * @var Coupon
     */
    protected $_coupon;

    /**
     * @var DirectoryList
     */
    protected $_directorylist;

    /**
     * @var StockRegistryInterface
     */
    protected $_stockRegistry;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param ProductMetadataInterface $productMetadata
     * @param WriterInterface $configWriter
     * @param OrderFactory $orderFactory
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     * @param Registry $registry
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param SubscriberCollectionFactory $subscriberCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param CarthashFactory $carthashFactory
     * @param Cart $cartModel
     * @param ProductFactory $productFactory
     * @param FormKey $formKey
     * @param ImageFactory $imageHelperFactory
     * @param Logger $flashyLogger
     * @param Coupon $coupon
     * @param DirectoryList $directorylist
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        Context                     $context,
        StoreManagerInterface       $storeManager,
        ProductMetadataInterface    $productMetadata,
        WriterInterface             $configWriter,
        OrderFactory                $orderFactory,
        CheckoutSession             $checkoutSession,
        CustomerSession             $customerSession,
        Registry                    $registry,
        CookieManagerInterface      $cookieManager,
        CookieMetadataFactory       $cookieMetadataFactory,
        ProductCollectionFactory    $productCollectionFactory,
        CustomerCollectionFactory   $customerCollectionFactory,
        SubscriberCollectionFactory $subscriberCollectionFactory,
        OrderCollectionFactory      $orderCollectionFactory,
        CarthashFactory             $carthashFactory,
        Cart                        $cartModel,
        ProductFactory              $productFactory,
        FormKey                     $formKey,
        ImageFactory                $imageHelperFactory,
        Logger                      $flashyLogger,
        Coupon                      $coupon,
        DirectoryList               $directorylist,
        StockRegistryInterface      $stockRegistry,
        EventManager $eventManager
    )
    {
        $objectManager = ObjectManager::getInstance();
        $v = explode('.', $productMetadata->getVersion());
        if ($v[1] > 1) {
            $scopeConfig = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
        } else {
            $scopeConfig = $context->getScopeConfig();
        }
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_productMetadata = $productMetadata;
        $this->_configWriter = $configWriter;
        $this->_orderFactory = $orderFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->_registry = $registry;
        $this->_cookieManager = $cookieManager;
        $this->_cookieMetadataFactory = $cookieMetadataFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_customerCollectionFactory = $customerCollectionFactory;
        $this->_subscriberCollectionFactory = $subscriberCollectionFactory;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_carthashFactory = $carthashFactory;
        $this->_cartModel = $cartModel;
        $this->_productFactory = $productFactory;
        $this->_formKey = $formKey;
        $this->_imageHelperFactory = $imageHelperFactory;
        $this->_flashyLogger = $flashyLogger;
        $this->_coupon = $coupon;
        $this->_directorylist = $directorylist;
        $this->_stockRegistry = $stockRegistry;
        $this->eventManager = $eventManager;
        parent::__construct($context);

        $this->flashy = null;
        $this->apiKey = $this->getFlashyKey();

        if (isset($this->apiKey)) {
            $this->flashy = $this->setFlashyApi($this->apiKey);
        }
    }

    /**
     * Get base url.
     *
     * @param $scope_id
     * @return string
     */
    public function getBaseUrlByScopeId($scope_id)
    {
        $baseUrl = '';
        try {
            $baseUrl = $this->_storeManager->getStore($scope_id)->getBaseUrl();
        } catch (NoSuchEntityException $e) {
            $this->addLog($e->getMessage());
        }
        return $baseUrl;
    }

    /**
     * Get current currency code.
     *
     * @param $store_id
     * @return string
     */
    public function getCurrencyByStoreId($store_id)
    {
        $currentCurrencyCode = '';
        try {
            $currentCurrencyCode = $this->_storeManager->getStore($store_id)->getCurrentCurrencyCode();
        } catch (NoSuchEntityException $e) {
            $this->addLog($e->getMessage());
        }
        return $currentCurrencyCode;
    }

    /**
     * Get flashy config.
     *
     * @param $configPath
     * @return mixed
     */
    public function getFlashyConfig($configPath)
    {
        $flashyConfig = '';
        try {
            $flashyConfig = $this->_scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE, $this->_storeManager->getStore()->getId());
        } catch (NoSuchEntityException $e) {
            $this->addLog($e->getMessage());
        }
        return $flashyConfig;
    }

    /**
     * Get flashy active.
     *
     * @return mixed
     */
    public function getFlashyActive()
    {
        return $this->getFlashyConfig(self::FLASHY_ACTIVE_STRING_PATH);
    }

    /**
     * Get flashy id from Flashy.
     *
     * @return mixed
     */
    public function getFlashyId()
    {
        return $this->getFlashyConfig(self::FLASHY_ID_STRING_PATH);
    }

    /**
     * Get flashy purchase from Config.
     *
     * @return mixed
     */
    public function getFlashyPurchase()
    {
        return $this->getFlashyConfig(self::FLASHY_PURCHASE_STRING_PATH);
    }

    /**
     * Get flashy log from Config.
     *
     * @return mixed
     */
    public function getFlashyLog()
    {
        return $this->getFlashyConfig(self::FLASHY_LOG_STRING_PATH);
    }

    /**
     * Check if Flashy api key is valid.
     *
     * @param $api_key
     * @return mixed
     */
    public function checkApiKey($api_key)
    {
        try {
            $this->flashy = $this->setFlashyApi($api_key);

            $info = Helper::tryOrLog(function () {
                return $this->flashy->account->get();
            });

            if ($info) {
                return $info->success();
            }

        } catch (\Exception $e) {
            $this->addLog($e->getMessage());
        }
        return null;
    }

    /**
     * Get Flashy api key.
     *
     * @return mixed
     */
    public function getFlashyKey()
    {
        $store = $this->_request->getParam("website", 0);

        if ($store <= 0) {
            $store = $this->_request->getParam("store", 0);
        }

        return $this->_scopeConfig->getValue(self::FLASHY_KEY_STRING_PATH, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Get store name.
     *
     * @param $storeId
     * @return string
     */
    public function getStoreName($storeId)
    {
        $storeName = 'Default Store';
        try {
            $storeName = $this->_storeManager->getStore($storeId)->getName();
        } catch (NoSuchEntityException $e) {
            $this->addLog($e->getMessage());
        }
        return $storeName;
    }

    /**
     * Get general contact email address.
     *
     * @param $scope
     * @param $scopeId
     * @return mixed
     */
    public function getStoreEmail($scope, $scopeId)
    {
        return $this->_scopeConfig->getValue('trans_email/ident_general/email', $scope, $scopeId);
    }

    /**
     * Get Flashy Connected.
     *
     * @param $scope
     * @param $scopeId
     * @return bool
     */
    public function getFlashyConnected($scope, $scopeId)
    {
        return $this->_scopeConfig->getValue(self::FLASHY_CONNECTED_STRING_PATH, $scope, $scopeId) == '1';
    }

    /**
     * Get Flashy list.
     *
     * @param $storeId
     * @return mixed
     */
    public function getFlashyList($storeId)
    {
        return $this->_scopeConfig->getValue(
            self::FLASHY_LIST_STRING_PATH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get current order data.
     *
     * @return array
     */
    public function getOrderDetails()
    {
        $this->addLog('getOrderDetails');
        $data = array();
        try {
            $orderId = $this->_checkoutSession->getLastRealOrderId();

            $order = $this->_orderFactory->create()->loadByIncrementId($orderId);

            $contactData = $this->getContactData($order);

            $this->addLog('Contact Data ' . json_encode($contactData));

            $create = Helper::tryOrLog(function () use ($contactData) {
                return $this->flashy->contacts->create($contactData);
            });

            //$this->addLog('Flashy contact created: ' . $create);

            $total = (float)$order->getSubtotal();
            $this->addLog('Order total=' . $total);

            $items = $order->getAllItems();
            $this->addLog('Getting order items');

            $products = [];

            foreach ($items as $i):
                $products[] = $i->getProductId();
            endforeach;
            $this->addLog('Getting product ids');

            $data = array(
                "order_id" => $order->getIncrementId(),
                "value" => $total,
                "content_ids" => $products,
                "status" => $order->getStatus(),
                "email" => $contactData['email'],
                "currency" => $order->getOrderCurrencyCode()
            );
            $this->addLog('Data=' . json_encode($data));
        } catch (\Exception $e) {
            $this->addLog($e->getMessage());
        }
        return $data;
    }


    /**
     * Build and send Purchase API request
     * @param Order $order
     * @return void
     * @throws NoSuchEntityException
     */
    public function orderPlace(Order $order)
    {
        $this->addLog('salesOrderPlaceAfter');

        if ($this->getFlashyActive() && isset($this->apiKey) && $this->getFlashyPurchase()) {

            $account_id = $this->getFlashyId();

            $this->addLog('account_id=' . $account_id);

            $contactData = $this->getContactData($order);

            $this->addLog('Contact Data ' . json_encode($contactData));

            $create = Helper::tryOrLog(function () use ($contactData) {
                return $this->flashy->contacts->create($contactData, 'email', true, true );
            });

            $this->addLog('Flashy contact created: ' . json_encode($create));

            $total = (float)$order->getSubtotal();
            $this->addLog('Order total=' . $total);

            $items = $order->getAllItems();

            $this->addLog('Getting order items');

			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

			$productData = [];

            $products = [];

            foreach ($items as $i):
                $products[] = $i->getProductId();

				if ($i->getData()) {

					$product = $this->_productFactory->create()->load($i->getProductId());

					$store = $this->_storeManager->getStore();

					$productData[] = [
						"image_link" => $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA). 'catalog/product' . $product->getImage(),
						"title" => $i->getName(),
						"quantity" => $i->getQtyOrdered(),
						"total" => $i->getPrice(),
					];
				}
            endforeach;

            $this->addLog('Getting product ids');

            $currency = $order->getOrderCurrencyCode();
            $this->addLog('Currency=' . $currency);

            $data = array(
                "account_id" => $account_id,
                "email" => $contactData['email'],
                "order_id" => $order->getIncrementId(),
                "value" => $total,
                "content_ids" => $products,
                "status" => $order->getStatus(),
                "currency" => $currency
            );

			$data['context']['items'] = $productData;

			$data['context']['total'] = $total;

			$data['context']['order_id'] = $order->getIncrementId();

            if( !empty($order->getCouponCode()) )
            {
                $data['context']['coupon_code'] = $order->getCouponCode();
            }

			$billingData = $order->getBillingAddress()->getData();

			if( !empty($billingData['street']) )
			{
				$data['context']['billing']['address'] = $billingData['street'];
			}

			if( !empty($billingData['city']) )
			{
				$data['context']['billing']['city'] = $billingData['city'];
			}

			if( !empty($billingData['postcode']) )
			{
				$data['context']['billing']['postcode'] = $billingData['postcode'];
			}

			if( !empty($billingData['country_id']) )
			{
				$country = $objectManager->create('\Magento\Directory\Model\Country')->load($billingData['country_id'])->getName();

				$data['context']['billing']['country'] = $country;
			}

			if( !empty($billingData['region']) )
			{
				$data['context']['billing']['state'] = $billingData['region'];
			}

            // shipping address section
            if (!$order->getIsVirtual()) {
                $shippingData = $order->getShippingAddress()->getData();
            }
            else {
                $shippingData = $billingData;
            }

            if (!empty($shippingData['street'])) {
                $data['context']['shipping']['address'] = $shippingData['street'];
            }

            if (!empty($shippingData['city'])) {
                $data['context']['shipping']['city'] = $shippingData['city'];
            }

            if (!empty($shippingData['postcode'])) {
                $data['context']['shipping']['postcode'] = $shippingData['postcode'];
            }

            if (!empty($shippingData['country_id'])) {
                $country = $objectManager->create('\Magento\Directory\Model\Country')->load($shippingData['country_id'])->getName();

                $data['context']['shipping']['country'] = $country;
            }

            if (!empty($shippingData['region'])) {
                $data['context']['shipping']['state'] = $shippingData['region'];
            }

            if (!empty($order->getShippingDescription())) {
                $data['context']['shipping']['method'] = $order->getShippingDescription();
            }


            $this->addLog('Data=' . json_encode($data));
			#
            $track = Helper::tryOrLog(function () use ($data) {
                return $this->flashy->events->track("Purchase", $data);
            });

            $this->addLog('Purchase sent: ' . json_encode($track));
        }
    }


    /**
     * Get contact information from order
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getContactData($order)
    {

        // customer
        $data = [
            'email' => $order->getCustomerEmail(),
            'gender' => $order->getCustomerGender()
        ];
        $data['first_name']  = $order->getCustomerFirstname();
        $data['last_name']  = $order->getCustomerLastname();

        if ( !empty($order->getCustomerDob()) )
        {
            $data['birthday'] = $order->getCustomerDob();
        }

        // address
        if ($order->getIsVirtual()) {
            $address = $order->getBillingAddress();
        }
        else {
            $address = $order->getShippingAddress();
        }

        $data['phone'] = $address->getTelephone();

        $data['city'] = $address->getCity();

        $data['region'] = $address->getRegion();

        $data['address'] = $address->getStreetLine(1);

        if ( !empty($address->getStreetLine(2)) )
        {
            $data['address'] .= ' , ' . $address->getStreetLine(2);
        }

        if ( !empty($address->getStreetLine(3)) )
        {
            $data['address'] .= ' , ' . $address->getStreetLine(3);
        }

        $data = new DataObject($data);

        $this->eventManager->dispatch('flashyapp_contact_data_prepare_after', [
            'order' => $order, 'contact_data' => $data
        ]);

        return $data->toArray();
    }

    /**
     * Get current product data.
     *
     * @return array
     */
    public function getProductDetails()
    {
        $product = $this->_registry->registry('current_product');
        $products = [];
        $products[] = $product->getId();
        $data = array(
            "content_ids" => $products
        );
        return $data;
    }

    /**
    * Get product category name.
    *
    * @return string
    */
   public function getProductCategoryName($prdId)
   {

	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $product = $objectManager->create('Magento\Catalog\Model\Product')->load($prdId);

	$cats = $product->getCategoryIds();

        $catsName = "";

        foreach($cats as $counter => $cat)
        {
            $category = $objectManager->create('Magento\Catalog\Model\Category')->load($cat);

	        $parentCatName = $objectManager->create('Magento\Catalog\Model\Category')->load($category->getparent_id());

            $catsName .= "category:" . ($parentCatName->getName()) . ">" .$category->getName();

	    if($counter < (count($cats) - 1) )
               $catsName .= ", ";
        }

	return $catsName;

   }

    /**
     * Get cart data.
     *
     * @return false|string
     */
    public function getCart($cart = null)
    {
        try {
            if( $cart === null )
                $cart = $this->_checkoutSession->getQuote();

            $tracking = [];

            foreach ($cart->getAllVisibleItems() as $item) {
                $tracking['content_ids'][] = $item->getProductId();
            }

            $tracking['value'] = intval($cart->getGrandTotal());

            if ($tracking['value'] <= 0) return false;

            $tracking['currency'] = $cart->getQuoteCurrencyCode();

            $tracking = json_encode($tracking);

            return $tracking;
        } catch (\Exception $e) {
            $this->addLog($e->getMessage());
            return false;
        }
    }

    /**
     * Create API object
     *
     * @param $flashy_key
     * @return false|Flashy
     */
    public function setFlashyApi($flashy_key)
    {
        try {
            return new Flashy(array(
                'api_key' => $flashy_key,
                'log_path' => $this->_directorylist->getPath('var') . '\log\flashy.log'
            ));
        } catch (\Exception $e) {
            $this->addLog($e->getMessage());
            return false;
        }
    }

    /**
     * Set flashy cart cache in cookie.
     */
    public function setFlashyCartCache($cart = null)
    {
        try {
            $metadata = $this->_cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setDuration(self::COOKIE_DURATION*365)
                ->setHttpOnly(false)
                ->setPath('/');

            $this->_cookieManager->setPublicCookie(
                'flashy_cart_cache',
                base64_encode($this->getCart($cart)),
                $metadata
            );
        } catch (\Exception $e) {
            $this->addLog($e->getMessage());
        }
    }

    /**
     * Get flashy cart cache from cookie.
     *
     * @return null|string
     */
    public function getFlashyCartCache()
    {
        return $this->_cookieManager->getCookie('flashy_cart_cache');
    }

    /**
     * Get flashy id from cookie.
     *
     * @return null|string
     */
    public function getFlashyIdCookie()
    {
        return $this->_cookieManager->getCookie('flashy_id');
    }

    /**
     * Check if customer is logged in.
     *
     * @return bool
     */
    public function customerIsLoggedIn()
    {
        return $this->_customerSession->isLoggedIn();
    }

    /**
     * Get customer email.
     *
     * @return string
     */
    public function getCustomerEmail()
    {
        return $this->_customerSession->getCustomer()->getEmail();
    }

    /**
     * Get lists from Flashy.
     *
     * @return array
     */
    public function getFlashyListOptions()
    {
        $options = array();

        if ($this->flashy == null)
            return;

        try {
            $lists = Helper::tryOrLog(function () {
                return $this->flashy->lists->get();
            });

            if (isset($lists)) {
                foreach ($lists->getData() as $list) {
                    $options[] = array(
                        'value' => strval($list['id']),
                        'label' => $list['title']
                    );
                }
            }

            if (count($options) == 0) {
                $options[] = array(
                    'value' => strval(''),
                    'label' => 'Choose a list'
                );
            }
        } catch (\Exception $e) {
            $this->showMessage($e->getMessage());
        }
        return $options;
    }

    /**
     * Get lists as associative array from Flashy.
     *
     * @return array
     */
    public function getFlashyListOptionsArray()
    {
        $options = array();

        if ($this->flashy == null)
            return;

        try {
            $lists = Helper::tryOrLog(function () {
                return $this->flashy->lists->get();
            });

            foreach ($lists->getData() as $list) {
                $options[$list['id']] = $list['title'];
            }

            if (count($options) == 0) {
                $options[''] = 'Choose a list';
            }
        } catch (\Exception $e) {
            $this->showMessage($e->getMessage());
        }
        return $options;
    }

    /**
     * Send subscriber email to Flashy.
     *
     * @param $subscriberData
     * @param $storeId
     */
    public function subscriberSend($subscriberData, $storeId)
    {
        try {
            $list_id = $this->getFlashyList($storeId);

            if (!empty($list_id) && isset($this->flashy))
            {
				$subscriber = [];

				if( isset( $subscriberData['email'] ) )
                {
					$subscriber['email'] = $subscriberData['email'];
 				}

                if( isset( $subscriberData['telephone'] ) )
                {
					$subscriber['phone'] = $subscriberData['telephone'];
 				}

				if( isset( $subscriberData['firstname'] ) )
                {
					$subscriber['first_name'] = $subscriberData['firstname'];
 				}

				if( isset( $subscriberData['lastname'] ) )
                {
					$subscriber['last_name'] = $subscriberData['lastname'];
 				}

				if( isset( $subscriberData['dob'] ) )
                {
					$subscriber['birthday'] = $subscriberData['dob'];
 				}

				if( isset( $subscriberData['city'] ) )
                {
					$subscriber['city'] = $subscriberData['city'];
 				}

				if( isset( $subscriberData['street'] ) )
                {
					$street = is_array($subscriberData['street']) ? implode(', ', $subscriberData['street']) : $subscriberData['street'];
					$subscriber['address'] = $street;
 				}

                if ($list_id != '') {
                    $subscribe = Helper::tryOrLog(function () use ($subscriber, $list_id) {
                        return $this->flashy->contacts->subscribe($subscriber, $list_id);
                    });

                    $this->addLog('Newsletter new subscriber: ' . json_encode($subscribe));
                } else {
                    $this->addLog('Newsletter new subscriber: lists is not exists');
                }
            } else {
                $this->addLog('Newsletter new subscriber: Flashy API Key="' . $this->apiKey . '" list id="' . $list_id . '"');
            }
        } catch (\Exception $e) {
            $this->addLog($e->getMessage());
        }
    }

    /**
     * Send order data to Flashy.
     *
     * @param $order
     */
    public function orderSend($order)
    {
        try {
            $this->addLog('salesOrderChange');

            if ($this->getFlashyActive() && isset($this->flashy)) {

                $account_id = $this->getFlashyId();

                if ($order->getStatus() != $order->getOrigData('status')) {

                    $email = $order->getCustomerEmail();

                    $data = array(
                        "order_id" => $order->getIncrementId(),
                        "status" => $order->getStatus()
                    );

                    $shipmentCollection = $order->getShipmentsCollection();

                    foreach ($shipmentCollection as $shipment) {

                        foreach ($shipment->getAllTracks() as $track) {
                            $trackNumber = $track->getData()['track_number'];
                            $this->addLog("Adding track number: $trackNumber");
                            $data['tracking_id'] = $trackNumber;
                        }
                    }

                    $data = array_merge(array("account_id" => $account_id, "email" => $email), $data);

                    $track = Helper::tryOrLog(function () use ($data) {
                        return $this->flashy->events->track("PurchaseUpdated", $data);
                    });

                    $this->addLog("Purchase Updated with data for $account_id and $email:" . json_encode($track));
                }
            }
        } catch (\Exception $e) {
            $this->addLog($e->getMessage());
        }
    }

    /**
     * Get products total count
     *
     * @param $store_id
     * @return int
     */
    public function getProductsTotalCount($store_id)
    {
        $products = $this->_productCollectionFactory->create();
        $products->addAttributeToSelect('*');
        $products->addStoreFilter($store_id);

        return $products->getSize();
    }

    /**
     * Get exported products
     *
     * @param $store_id
     * @param $limit
     * @param $page
     * @return array
     */
    public function exportProducts($store_id, $limit, $page)
    {
        $products = $this->_productCollectionFactory->create();
        $products->addAttributeToSelect('*');
        $products->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        $products->addStoreFilter($store_id);

		if ($limit) {
            $products->setPageSize((int)$limit);
            if ($page) {
                $products->setCurPage((int)$page);
            }
        }

        $products->setFlag('has_stock_status_filter', true)->load();

        $export_products = array();

        $i = 0;

        $currency = $this->getCurrencyByStoreId($store_id);

        foreach ($products as $_product) {
            try {
                $product_id = $_product->getId();
                $productStock = $this->_stockRegistry->getStockItem($product_id);
                $availability = $productStock->getIsInStock() ? 'in stock' : 'out of stock';

                $export_products[$i] = array(
                    'id' => $product_id,
                    'link' => $_product->getProductUrl($_product),
                    'title' => $_product->getName(),
                    'description' => $_product->getShortDescription(),
                    'price' => $_product->getPrice(),
                    'final_price' => $_product->getFinalPrice(),
                    'sale_price' => $_product->getSpecialPrice(),
                    'sale_price_effective_date' => date('Y-m-d\TH:i:sO', strtotime($_product->getSpecialFromDate())) . '/' . date('Y-m-d\TH:i:sO', strtotime($_product->getSpecialToDate())),
                    'currency' => $currency,
                    'tags' => $_product->getMetaKeyword(),
                    'availability' => $availability
                );
                if ($_product->getImage() && $_product->getImage() != 'no_selection') {
					$store = $this->_storeManager->getStore();

                    $export_products[$i]['image_link'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA). 'catalog/product' . $_product->getImage();
                }

                $categoryCollection = $_product->getCategoryCollection()->addAttributeToSelect('name');

                $export_products[$i]['product_type'] = "";

                foreach ($categoryCollection as $_cat) {
                    $export_products[$i]['product_type'] .= $_cat->getName() . '>';
                }

                $export_products[$i]['product_type'] = substr($export_products[$i]['product_type'], 0, -1);

				$_objectManager = ObjectManager::getInstance();

				$is_parent = $_objectManager->get('Magento\ConfigurableProduct\Model\Product\Type\Configurable')->getParentIdsByChild($product_id);

				$export_products[$i]['variant'] = (empty($is_parent[0]) ? 0 : 1);

				$export_products[$i]['parent_id'] = (empty($is_parent[0]) ? 0 : $is_parent[0]);

				$export_products[$i]['created_at'] = date("Y-m-d", strtotime($_product->getCreatedAt()));

                $export_products[$i]['updated_at'] = date("Y-m-d", strtotime($_product->getUpdatedAt()));

                $i++;
            } catch (\Exception $e) {
                continue;
            }
        }

        $page_size = $products->getPageSize();
        $current_page = $products->getCurPage();
        $total = $this->getProductsTotalCount($store_id);
        $size = $products->getSize();

        $flashy_pagination = false;
        $next_url = null;

        if ($limit) {
            if (ceil($size / $page_size) > $current_page) {
                $base_url = $this->getBaseUrlByScopeId($store_id);
                $nextpage = $current_page + 1;
                $next_url = $base_url . "flashy?export=products&store_id=$store_id&limit=$limit&page=$nextpage&flashy_key=$this->apiKey";
            }
            if ($size > $limit) {
                $flashy_pagination = true;
            }
        }

        return array(
            "data" => $export_products,
            "store_id" => $store_id,
            "size" => $size,
            "page_size" => $page_size,
            "current_page" => $current_page,
            "count" => count($export_products),
            "total" => $total,
            "flashy_pagination" => $flashy_pagination,
            "next_page" => $next_url,
            "success" => true
        );
    }

    /**
     * get Customers Total Count
     *
     * @param $store_id
     * @return int
     */
    public function getCustomersTotalCount($store_id)
    {
        try {
            //get website id from  store id
            $websiteId = $this->_storeManager->getStore($store_id)->getWebsiteId();

            $customers = $this->_customerCollectionFactory->create();

            //get all attributes
            $customers->addAttributeToSelect('*');

            //filter by website
            if ($websiteId > 0) {
                $customers->addAttributeToFilter("website_id", array("eq" => $websiteId));
            }
            return $customers->getSize();
        } catch (\Exception $e) {
            $this->addLog($e->getMessage());
            return 0;
        }
    }

    /**
     * get Subscibers Total Count
     *
     * @param $store_id
     * @return int
     */
    public function getSubscibersTotalCount($store_id)
    {
        //get subscriber collection
        $subscribers = $this->_subscriberCollectionFactory->create();

        //filter by store id
        if ($store_id > 0) {
            $subscribers->addStoreFilter($store_id);
        }

        //get only guest subscribers as customers are included already
        $subscribers->addFieldToFilter('main_table.customer_id', ['eq' => 0]);
        return $subscribers->getSize();
    }

    /**
     * Get exported customers and subscribers
     *
     * @param $store_id
     * @param $limit
     * @param $page
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function exportContacts($store_id, $limit, $page)
    {
        $total1 = $this->getCustomersTotalCount($store_id);
        $total2 = $this->getSubscibersTotalCount($store_id);

        $c = true;
        $s = true;
        $offset = 0;
        $limit1 = $limit;
        if ($limit) {
            if (($page * $limit) <= $total1) {
                //we'll show only customers
                $s = false;
            } else {
                $offset = $page * $limit - $total1;
                if ($offset < $limit) {
                    //we'll show both customers and subscribers
                    $limit1 = $offset;
                    $offset = 0;
                } else {
                    //we'll show only subscribers
                    $c = false;
                    $offset -= $limit;
                }

            }
        }

        $i = 0;
        $export_customers = array();
        if ($c) {
            //get website id from  store id
            $websiteId = $this->_storeManager->getStore($store_id)->getWebsiteId();

            $customers = $this->_customerCollectionFactory->create();

            //get all attributes
            $customers->addAttributeToSelect('*');

            //filter by website
            if ($websiteId > 0) {
                $customers->addAttributeToFilter("website_id", array("eq" => $websiteId));
            }

            if ($limit) {
                $customers->setPageSize($limit);
                if ($page) {
                    $customers->setCurPage($page);
                }
            }

            foreach ($customers as $_customer) {
                //add customer fields
                $export_customers[$i] = array(
                    'email' => $_customer->getEmail(),
                    'first_name' => $_customer->getFirstname(),
                    'last_name' => $_customer->getLastname()
                );

                //get default shipping address of customer
                $address = $_customer->getDefaultShippingAddress();

                //add address fields
                if ($address) {
                    $export_customers[$i]['phone'] = $address->getTelephone();
                    $export_customers[$i]['city'] = $address->getCity();
                    $export_customers[$i]['country'] = $address->getCountry();
                }
                $i++;
            }
        }

        if ($s) {
            //get subscriber collection
            $subscribers = $this->_subscriberCollectionFactory->create();

            //filter by store id
            if ($store_id > 0) {
                $subscribers->addStoreFilter($store_id);
            }

            //get only guest subscribers as customers are included already
            $subscribers->addFieldToFilter('main_table.customer_id', ['eq' => 0]);

            if ($limit1) {
                $select = $subscribers->getSelect();

                $select->limit($limit1, $offset);
            }

            foreach ($subscribers as $subscriber) {
                //add subscriber email, no other fields are available by default
                $export_customers[$i]['email'] = $subscriber->getEmail();
                $i++;
            }
        }

        $page_size = $limit;
        $current_page = $page;
        $total = $total1 + $total2;

        $flashy_pagination = false;
        $next_url = null;
        if ($limit) {
            if (ceil($total / $page_size) > $current_page) {
                $base_url = $this->getBaseUrlByScopeId($store_id);
                $nextpage = $current_page + 1;
                $next_url = $base_url . "flashy?export=contacts&store_id=$store_id&limit=$limit&page=$nextpage&flashy_key=$this->apiKey";
            }
            if ($total > $limit) {
                $flashy_pagination = true;
            }
        }

        return array(
            "data" => $export_customers,
            "store_id" => $store_id,
            "size" => $total,
            "page_size" => $page_size,
            "current_page" => $current_page,
            "count" => count($export_customers),
            "total" => $total,
            "flashy_pagination" => $flashy_pagination,
            "next_page" => $next_url,
            "success" => true
        );
    }

    /**
     * get Orders Total Count
     *
     * @param $store_id
     * @return int
     */
    public function getOrdersTotalCount($store_id)
    {
        //get order collection
        $orders = $this->_orderCollectionFactory->create();

        //get all attributes
        $orders->addAttributeToSelect('*');

        //filter by store id
        if ($store_id > 0) {
            $orders->addFieldToFilter('main_table.store_id', ['eq' => $store_id]);
        }
        return $orders->getSize();
    }

    /**
     * Get exported orders
     *
     * @param $store_id
     * @param $limit
     * @param $page
     * @return array
     */
    public function exportOrders($store_id, $limit, $page)
    {
        //get order collection
        $orders = $this->_orderCollectionFactory->create();

        //get all attributes
        $orders->addAttributeToSelect('*');

        //filter by store id
        if ($store_id > 0) {
            $orders->addFieldToFilter('main_table.store_id', ['eq' => $store_id]);
        }

        if ($limit) {
            $orders->setPageSize($limit);
            if ($page) {
                $orders->setCurPage($page);
            }
        }

        $i = 0;
        $export_orders = array();
        foreach ($orders as $order) {
            $items = $order->getAllItems();

            $products = [];

            foreach ($items as $item):
                $products[] = $item->getProductId();
            endforeach;

            $export_orders[$i] = array(
                "email" => $order->getCustomerEmail(),
                "order_id" => $order->getId(),
                "order_increment_id" => $order->getIncrementId(),
                "value" => (float)$order->getGrandTotal(),
                "date" => strtotime($order->getCreatedAt()),
                "content_ids" => implode(',', $products),
                "currency" => $order->getOrderCurrencyCode()
            );
            $i++;
        }

        $page_size = $orders->getPageSize();
        $current_page = $orders->getCurPage();
        $total = $this->getOrdersTotalCount($store_id);

        $flashy_pagination = false;
        $next_url = null;
        if ($limit) {
            if (ceil($total / $page_size) > $current_page) {
                $base_url = $this->getBaseUrlByScopeId($store_id);
                $nextpage = $current_page + 1;
                $next_url = $base_url . "flashy?export=orders&store_id=$store_id&limit=$limit&page=$nextpage&flashy_key=$this->apiKey";
            }
            if ($total > $limit) {
                $flashy_pagination = true;
            }
        }

        return array(
            "data" => $export_orders,
            "store_id" => $store_id,
            "size" => $orders->getSize(),
            "page_size" => $page_size,
            "current_page" => $current_page,
            "count" => count($export_orders),
            "total" => $total,
            "flashy_pagination" => $flashy_pagination,
            "next_page" => $next_url,
            "success" => true
        );
    }

    /**
     * @param $store_id
     * @return array
     */
    public function exportCategories($store_id)
    {
        $objectManager = $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $categoryFactory = $objectManager->create('Magento\Catalog\Model\ResourceModel\Category\CollectionFactory');

        $categories = $categoryFactory->create()
            ->addAttributeToSelect('*')
            ->setStore($store_id);

        $export_categories = [];

        foreach( $categories as $category )
        {
            $export_categories[] = [
                "value" => $category->getId(),
                "title" => $category->getName()
            ];
        }

        return [
            "success" => true,
            "data" => $export_categories,
            "store_id" => $store_id
        ];
    }

    /**
     * Set Flashy Connected
     *
     * @param $value
     * @param $scope
     * @param $scope_id
     */
    public function setFlashyConnected($value, $scope, $scope_id)
    {
        $this->_configWriter->save(self::FLASHY_CONNECTED_STRING_PATH, $value ? 1 : 0, $scope, $scope_id);
        $this->_configWriter->save(self::FLASHY_ID_STRING_PATH, $value, $scope, $scope_id);
    }

    /**
     * Remove Flashy Connected
     *
     * @param $scope
     * @param $scope_id
     * @return string
     */
    public function removeFlashyConnected($scope = 0, $scope_id = 0)
    {
        $this->_configWriter->delete(self::FLASHY_CONNECTED_STRING_PATH, $scope, $scope_id);
        $this->_configWriter->delete(self::FLASHY_ID_STRING_PATH, $scope, $scope_id);

        return 'deleted';
    }

    /**
     * Do connection request to Flashy.
     *
     * @param $flashyKey
     * @param $scope
     * @param $scope_id
     * @return int
     */
    public function connectionRequest($flashyKey, $scope, $scope_id)
    {
        $store_email = $this->getStoreEmail($scope, $scope_id);
        $store_name = $this->getStoreName($scope_id);
        $base_url = $this->getBaseUrlByScopeId($scope_id);

        $data = array(
            "profile" => array(
                "from_name" => $store_name,
                "from_email" => $store_email,
                "reply_to" => $store_email,
            ),
            "store" => array(
                "platform" => "magento",
                "api_key" => $flashyKey,
                "store_name" => $store_name,
                "store" => $base_url,
                "debug" => array(
                    "magento" => $this->_productMetadata->getVersion(),
                    "php" => phpversion(),
                    "memory_limit" => ini_get('memory_limit'),
                ),
            )
        );
        $urls = array("contacts", "products", "orders");
        foreach ($urls as $url) {
            $data[$url] = array(
                "url" => $base_url . "flashy?export=$url&store_id=$scope_id&limit=100&page=1&flashy_pagination=true&flashy_key=" . $flashyKey,
                "format" => "json_url",
            );
        }

        try {
            $this->addLog("Connection Request Data => " . json_encode($data));

            Helper::tryOrLog(function () use ($data) {
                $this->flashy->platforms->connect($data);
            });

            $info = Helper::tryOrLog(function () {
                return $this->flashy->account->get();
            });

            return $info->getData()['id'];
        } catch (\Exception $e) {
            $this->showMessage($e->getMessage());
            return 0;
        }
    }

    /**
     * Update Flashy Cart Hash
     *
     * @param $cart
     */
    public function updateFlashyCartHash($cart)
    {
        $this->updateFlashyCache($cart);

        //cart hash will not be updated
        $updateCart = false;

        //get key from cookie
        $key = $this->getFlashyIdCookie();

        //if key exists
        if ($key) {
            //create flashy cart hash
            $cartHash = $this->_carthashFactory->create();

            //load cart hash by key
            $cartHash->load($key, 'key');

            //get quote from cart
            $quote = $cart->getQuote();

            //get all visible items of the cart
            $items = $quote->getAllVisibleItems();

            //cart items data
            $cartItems = array();

            //loop through cart visible items
            foreach ($items as $item) {
                //get product options
                $options = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());

                //update qty
                $options['info_buyRequest']['qty'] = $item->getQty();

                // unset uenc from cart item data
                unset($options['info_buyRequest']['uenc']);

                //add info to cart items
                $cartItems[] = $options['info_buyRequest'];

                //cart hash will be updated
                $updateCart = true;
            }

            //check if cart will be updated
            if ($updateCart) {
                try {
                    //save cart hash data
                    $cartHash->setKey($key);
                    $cartHash->setCart(json_encode($cartItems));
                    $cartHash->save();
                    $this->addLog("Saved cart hash, key=" . $cartHash->getKey() . " cart=" . $cartHash->getCart());

                } catch (\Exception $e) {
                    $this->addLog("Could not save flashy cart hash key=" . $cartHash->getKey() . " cart=" . $cartHash->getCart());
                }
            }
        }
    }

    /**
     * Update Flashy Cache
     */
    public function updateFlashyCache($cart)
    {
        $cart = $this->setFlashyCartCache($cart->getQuote());
    }

    /**
     * Restore Flashy Cart Hash
     *
     * @param $id
     * @return array
     */
    public function restoreFlashyCartHash($id)
    {
        //get flashy cart hash
        $cartHash = $this->_carthashFactory->create()->load($id, 'key');

        $messages = array();
        if ($cartHash) {
            try {
                //get cart data from hash
                $cart = json_decode($cartHash->getCart(), true);

                //empty the cart
                $this->_cartModel->truncate();

                //loop through cart items from hash
                foreach ($cart as $cart_item) {
                    //load product
                    $product = $this->_productFactory->create()->load($cart_item['product']);
                    try {
                        //add form key to cart item data
                        $cart_item['form_key'] = $this->_formKey->getFormKey();

                        //add product to cart
                        $this->_cartModel->addProduct($product, $cart_item);
                        $messages[] = array(
                            'message' => __('Success! %1 is restored successfully.', $product->getName()),
                            'success' => true
                        );

                    } catch (\Exception $e) {
                        $messages[] = array(
                            'message' => __('Error! %1 is not restored. %2', $product->getName(), $e->getMessage()),
                            'success' => false
                        );
                        $this->addLog($e->getMessage());
                    }
                }

                //save the cart
                $this->_cartModel->save();
            } catch (\Exception $e) {
                $messages[] = array(
                    'message' => __('Error! Cart is not restored.'),
                    'success' => false
                );
                $this->addLog("Could not restore flashy cart hash for id=$id cart=" . $cartHash->getCart());
            }
        }
        return $messages;
    }

    public function createJsonEncoded()
    {
        $default = array(
            'discount_type' => 'fixed_cart', // type: fixed_cart, percent, fixed_product, percent_product.
            'amount' => 0, //amount ?? string
            'usage_limit' => 1, // total usage ?? string
            'usage_limit_per_user' => 1, // total single user usage ?? string
            'expiry_date' => date('Y-m-d', strtotime('+371 days')), // date type example -> '25.05.21'
            'free_shipping' => false, // bool
            'product_ids' => null, // array of products id's
        );
        return base64_encode(json_encode($default));
    }

    /**
     * Create new coupon
     *
     * @param $args
     * @return array
     */
    public function createCoupon($args = array())
    {
        try {
            $this->addLog("Creating new coupon.");

            $ruleId = null;
            $couponCode = $this->generateCouponCode(12);

            if( isset($args['prefix']) )
            {
                $couponCode = $args['prefix'] . "_" . $couponCode;
            }

            $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();

            $default = array(
                'coupon_code' => $couponCode,
                'discount_type' => 'cart_fixed',    //String options - 'to_percent' 'by_percent' 'to_fixed' 'by_fixed' 'cart_fixed' 'buy_x_get_y'
                'amount' => 0,
                'usage_limit' => 1,
                'usage_limit_per_user' => 1,
                'expiry_date' => date('Y-m-d', strtotime('+371 days')),    //Date
                'product_ids' => null,

                // Only exists in Magento, for now we won't use them.
                'name' => 'Coupon',    //String
                'desc' => 'Coupon created by Flashy Platform',   //String
                'start' => date('Y-m-d'),   //Date
                'isActive' => 1,
                'includeShipping' => true,
            );

            $merged = array_merge($default, $args);

            switch ($merged['discount_type']) {
                case 'percent':
                    $merged['discount_type'] = 'by_percent';
                    break;
                case 'fixed_cart':
                    $merged['discount_type'] = 'cart_fixed';
                    break;
                case 'fixed_product':
                    $merged['discount_type'] = 'by_fixed';
                    break;
            }

            if (isset($args['coupon_code'])) {
                $ruleId = $this->_coupon->loadByCode($merged['coupon_code'])->getRuleId();
            }

            if ($ruleId != null) {
                $this->addLog("Coupon coupon_code already exists.");
                return array(
                    "data" => 'Unable to create coupon, check args.',
                    "success" => false
                );

            } else {
                $shoppingCartPriceRule = $this->_objectManager->create('Magento\SalesRule\Model\Rule');
                $shoppingCartPriceRule->setName($merged['name'])
                    ->setDescription($merged['desc'])
                    ->setFromDate($merged['start'])
                    ->setToDate($merged['expiry_date'])
                    ->setUsesPerCustomer($merged['usage_limit_per_user'])
                    ->setCustomerGroupIds(array('0','1','2','3',))
                    ->setWebsiteIds(array('1',))
                    ->setIsActive($merged['isActive'])
                    ->setSimpleAction($merged['discount_type'])
                    ->setDiscountAmount($merged['amount'])
                    ->setDiscountQty(1)
                    ->setApplyToShipping(0)
                    ->setUsesPerCoupon($merged['usage_limit'])
                    ->setProductIds($merged['product_ids'])
                    ->setCouponType(2)
                    ->setIsRss(0)
                    ->setCouponCode($merged['coupon_code']);

                    if( $args['minimum_amount'] > 0 )
                    {
                        $actions = $this->_objectManager->create('Magento\SalesRule\Model\Rule\Condition\Combine')
                            ->setType('Magento\SalesRule\Model\Rule\Condition\Address')
                            ->setAttribute('base_subtotal_with_discount')
                            ->setOperator('>')
                            ->setValue($args['minimum_amount']);

                        $shoppingCartPriceRule->getActions()->addCondition($actions);
                    }

                if( $args['free_shipping'] )
                {
                    $shoppingCartPriceRule->setSimpleFreeShipping(1);
                }

                if( isset($args['category']) )
                {
                    $shoppingCartPriceRule->getConditions()->loadArray(
                        [
                            'type' => Combine::class,
                            'attribute' => null,
                            'operator' => null,
                            'value' => '1',
                            'is_value_processed' => null,
                            'aggregator' => 'all',
                            'conditions' => [
                                    [
                                        'type' => Found::class,
                                        'attribute' => null,
                                        'operator' => null,
                                        'value' => 1,
                                        'is_value_processed' => null,
                                    ],
                                    [
                                        'type' => Product::class,
                                        'attribute' => 'category_ids',
                                        'operator' => '==',
                                        'value' => $merged['category'],
                                        'is_value_processed' => false,
                                        'attribute_scope' => ''
                                    ]
                            ],
                        ]
                    );
                }

                $shoppingCartPriceRule->save();

                $this->addLog("Coupon created successfully. " . $merged['coupon_code']);

                return array(
                    "data" => $merged['coupon_code'],
                    "success" => true
                );
            }
        } catch (\Exception $e) {
            $this->addLog("Coupon not created. " . $e);

            return array(
                "data" => "Coupon not created. " . $e,
                "success" => false
            );
        }
    }

    /**
     * Generate coupon code
     *
     * @param $length
     * @return string
     */
    public function generateCouponCode($length)
    {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $this->_objectManager->create('Magento\SalesRule\Model\Rule');
        $couponGenerator = $this->_objectManager->get('\Magento\SalesRule\Model\Coupon\Codegenerator');

        $couponHelper = $this->_objectManager->get('\Magento\SalesRule\Helper\Coupon');
        $couponGenerator->setFormat($couponHelper::COUPON_FORMAT_ALPHANUMERIC);

        $couponGenerator->setLength($length); // length of coupon code upto 32
        return $couponGenerator->generateCode();
    }

    /**
     * Get exported log file
     *
     * @param $store_id
     * @return array
     */
    public function exportLogFile($store_id)
    {
        try {
            $fileContent = array();
            $this->addLog("Log exported.");

            if ($this->getFlashyLog()) {
                $fileContent = explode("\n", file_get_contents($this->_directorylist->getPath('var') . '\log\flashy.log'));
            }

            return array(
                "data" => $fileContent,
                "store_id" => $store_id,
                "success" => true
            );
        } catch (\Exception $e) {
            $this->addLog($e->getMessage());
            return array(
                "data" => $e->getMessage(),
                "store_id" => $store_id,
                "success" => false
            );
        }
    }

    public function exportInfo($store_id)
    {
        return array(
            'store_name' => $this->getStoreName($store_id),
            'base_url' => $this->getBaseUrlByScopeId($store_id),
            'api_key' => $this->getFlashyKey(),
            "magento" => $this->_productMetadata->getVersion(),
            "php" => phpversion(),
            "memory_limit" => ini_get('memory_limit'),
        );
    }

    /**
     * Add log
     *
     * @param $m
     * @param $l
     */
    public function addLog($m, $l = 200)
    {
        if ($this->getFlashyLog()) {
            $this->_flashyLogger->log($l, $m);
        }
    }

    public function clearLogs()
    {
        unlink($this->_directorylist->getPath('var') . '\log\flashy.log');
        $this->addLog('Logs deleted.');
    }

    public function showMessage($m)
    {
        echo '<span class="flashy-exception">' . $m . '</span>';
        $this->addLog($m);

    }

    public function flashy_dd($content)
    {
        echo '<pre>';
        var_dump($content);
        die;
    }
}
