<?php

namespace Flashy\Integration\Plugin;

use Magento\Config\Block\System\Config\Form;
use Magento\Framework\App\Config\ScopeConfigInterface;

class CheckApiKeyPlugin
{
    private const FLASHY_IP_KEY_CONFIG_PATH = 'flashy/flashy/flashy_key';
    private const FLASHY_LIST_CONFIG_PATH = 'flashy/flashy_lists/flashy_list';
    private const FLASHY_LIST_FORM_ELEMENT_ID = 'flashy_flashy_lists_flashy_list';

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @param ScopeConfigInterface $config
     */
    public function __construct(ScopeConfigInterface $config)
    {
        $this->config = $config;
    }

    public function afterInitForm(
        Form $subject
    ) {
        $apiKeyValue = $this->config->getValue(self::FLASHY_IP_KEY_CONFIG_PATH);
        $flashyList = $this->config->getValue(self::FLASHY_LIST_CONFIG_PATH);

        if (
            !empty($apiKeyValue)
            && empty($flashyList)
            && !empty($subject->getForm()->getElement(self::FLASHY_LIST_FORM_ELEMENT_ID)))
        {
            $subject->getForm()->getElement(self::FLASHY_LIST_FORM_ELEMENT_ID)->setRequired(true);
        }

        return [];
    }
}
