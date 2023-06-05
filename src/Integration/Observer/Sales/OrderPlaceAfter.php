<?php

namespace Flashy\Integration\Observer\Sales;

use Flashy\Integration\Helper\Data;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\CartRepositoryInterface;

class OrderPlaceAfter implements ObserverInterface
{
    /**
     * @var Data
     */
    public $helper;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * OrderPlaceAfter constructor.
     *
     * @param CartRepositoryInterface $cartRepository
     * @param Data $helper
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        Data $helper
    ) {
        $this->cartRepository = $cartRepository;
        $this->helper = $helper;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $quote = $this->cartRepository->get($order->getQuoteId());
        $this->helper->updateFlashyCartHash($quote);
    }
}
