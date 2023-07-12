<?php

declare(strict_types=1);

namespace Flashy\Integration\Observer\Checkout;

use Flashy\Integration\Helper\Data as DataHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class calls the Flashy Purchase ivent and adds the additional information to the payment
 */
class OnePageControllerActionObserver implements ObserverInterface
{
    /**
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * @param DataHelper $dataHelper
     */
    public function __construct(
        DataHelper $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();;
        $this->dataHelper->orderPlace($order);
        $payment = $order->getPayment();
        $additionalData = $payment->getAdditionalInformation() ?? [];
        $additionalData['flashy_purchase_fired'] = 1;
        $payment->setAdditionalInformation($additionalData);
        $payment->save();
    }
}
