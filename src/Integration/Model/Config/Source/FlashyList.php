<?php

namespace Flashy\Integration\Model\Config\Source;

use Flashy\Integration\Helper\Data;
use Magento\Framework\Option\ArrayInterface;

class FlashyList implements ArrayInterface
{
    /**
     * @var Data
     */
    public $helper;

    /**
     * FlashyList constructor.
     *
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    )
    {
        $this->helper = $helper;
    }

    /**
     * Get lists as associative array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->helper->getFlashyListOptionsArray();

    }

    /**
     * Get lists.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $options[] = ['value' => '', 'label' => '-- Please Select --'];
        $flashyOptions = $this->helper->getFlashyListOptions();

        return $flashyOptions ? array_merge($options, $flashyOptions) : [];
    }
}
