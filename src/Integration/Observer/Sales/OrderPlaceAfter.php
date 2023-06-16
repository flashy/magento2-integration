<?php

namespace Flashy\Integration\Observer\Sales;

use Flashy\Integration\Helper\Data;
use Flashy\Integration\Service\IsOrderPlaceService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\CartRepositoryInterface;

class OrderPlaceAfter implements ObserverInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var IsOrderPlaceService
     */
    private $isOrderPlaceService;

    /**
     * @var Data
     */
    public $helper;

    /**
     * OrderPlaceAfter constructor.
     *
     * @param CartRepositoryInterface $cartRepository
     * @param Data $helper
     * @param IsOrderPlaceService $isOrderPlaceService
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        Data $helper,
        IsOrderPlaceService $isOrderPlaceService
    ) {
        $this->cartRepository = $cartRepository;
        $this->helper = $helper;
        $this->isOrderPlaceService = $isOrderPlaceService;
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
        $this->isOrderPlaceService->setIsOrderPlaced(true);
    }
}
