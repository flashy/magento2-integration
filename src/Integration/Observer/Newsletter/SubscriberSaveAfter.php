<?php
declare(strict_types=1);

namespace Flashy\Integration\Observer\Newsletter;

use Flashy\Integration\Helper\Data;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\ResourceModel\CustomerRepositoryFactory;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Sales\Api\OrderRepositoryInterfaceFactory;

class SubscriberSaveAfter implements ObserverInterface
{
    /**
     * @var CustomerRepositoryFactory
     */
    private $customerRepositoryFactory;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var OrderRepositoryInterfaceFactory
     */
    private $orderRepositoryFactory;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @param Data $helper
     * @param CustomerRepositoryFactory $customerRepositoryFactory
     * @param OrderRepositoryInterfaceFactory $orderRepositoryFactory
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     */
    public function __construct(
        Data $helper,
        CustomerRepositoryFactory $customerRepositoryFactory,
        OrderRepositoryInterfaceFactory $orderRepositoryFactory,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
    ) {
        $this->helper = $helper;
        $this->customerRepositoryFactory = $customerRepositoryFactory;
        $this->orderRepositoryFactory = $orderRepositoryFactory;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
    }

    /**
     * @inheritdoc
     *
     * @event newsletter_subscriber_save_after
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->getFlashyActive()) {
            return;
        }

        /** @var Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getSubscriber();
        if ($subscriber->getStatus() !== Subscriber::STATUS_SUBSCRIBED) {
            return;
        }

        $subscriberData = [
            'email' => $subscriber->getEmail(),
        ];

        try {
            if ($subscriber->getCustomerId()) {
                /** @var CustomerRepositoryInterface $customerRepository */
                $customerRepository = $this->customerRepositoryFactory->create();
                $customerDataModel = $customerRepository->getById((int)$subscriber->getCustomerId());

                $subscriberData['dob'] = $customerDataModel->getDob();
                $subscriberData['firstname'] = $customerDataModel->getFirstname();
                $subscriberData['lastname'] = $customerDataModel->getLastname();

                $defaultBillingAddress = null;

                /** @var AddressInterface $address */
                foreach ($customerDataModel->getAddresses() ?: [] as $address) {
                    if ($address->getId() === $customerDataModel->getDefaultBilling()) {
                        $defaultBillingAddress = $address;
                        break;
                    }
                }

                if ($defaultBillingAddress !== null) {
                    $subscriberData['telephone'] = $defaultBillingAddress->getTelephone();
                    $subscriberData['city'] = $defaultBillingAddress->getCity();
                    $subscriberData['street'] = $defaultBillingAddress->getStreet();
                }
            } else {
                $searchCriteria = $this->searchCriteriaBuilderFactory->create();
                $searchCriteria->addFilter('customer_email', $subscriber->getEmail());
                $result = $this->orderRepositoryFactory->create()->getList($searchCriteria->create());

                if ($result->getSize()) {
                    $order = $result->getFirstItem();
                    $billingAddress = $order->getBillingAddress();
                    $subscriberData['firstname'] = $billingAddress->getFirstname();
                    $subscriberData['lastname'] = $billingAddress->getLastname();
                    $subscriberData['telephone'] = $billingAddress->getTelephone();
                }
            }

        } catch (\Exception $e) {
            // TODO: implement logic to handle Exceptions
        }

        $this->helper->subscriberSend($subscriberData, $subscriber->getStoreId());
    }
}
