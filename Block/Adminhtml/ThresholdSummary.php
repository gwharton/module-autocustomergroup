<?php

namespace Gw\AutoCustomerGroup\Block\Adminhtml;

use Gw\AutoCustomerGroup\Api\Data\TaxSchemeInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class ThresholdSummary extends Field
{
    /**
     * @var TaxSchemeInterface
     */
    private $taxScheme;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context $context
     * @param TaxSchemeInterface $taxScheme
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        TaxSchemeInterface $taxScheme,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
        $this->taxScheme = $taxScheme;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Retrieve element HTML markup
     *
     * @param AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $storeId = $this->getRequest()->getParam('store', null);
        $baseCurrency = $this->scopeConfig->getValue(
            "currency/options/base",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $thresholdInBaseCurrency = $this->taxScheme->getSchemeExchangeRate($storeId) * $this->taxScheme->getThresholdInSchemeCurrency($storeId);

        return '<div class="thresholdsummary-wrapper">' .
            '<div>' . sprintf("%.2f", $thresholdInBaseCurrency) . ' ' . $baseCurrency . '</div>' .
            '</div>';
    }
}
