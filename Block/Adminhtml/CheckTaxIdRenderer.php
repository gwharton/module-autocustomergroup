<?php

namespace Gw\AutoCustomerGroup\Block\Adminhtml;

use Gw\AutoCustomerGroup\Model\AutoCustomerGroup;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Customer\Block\Adminhtml\Sales\Order\Address\Form\Renderer\Vat;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Json\EncoderInterface;

class CheckTaxIdRenderer extends Vat
{
    /**
     * @var SecureHtmlRenderer
     */
    private $secureRenderer;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var AutoCustomerGroup
     */
    private $autoCustomerGroup;

    /**
     * @param Context $context
     * @param EncoderInterface $jsonEncoder
     * @param SecureHtmlRenderer $secureRenderer
     * @param ScopeConfigInterface $scopeConfig
     * @param AutoCustomerGroup $autoCustomerGroup
     * @param array $data
     */
    public function __construct(
        Context $context,
        EncoderInterface $jsonEncoder,
        SecureHtmlRenderer $secureRenderer,
        ScopeConfigInterface $scopeConfig,
        AutoCustomerGroup $autoCustomerGroup,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $jsonEncoder,
            $data,
            $secureRenderer
        );
        $this->secureRenderer = $secureRenderer;
        $this->scopeConfig = $scopeConfig;
        $this->autoCustomerGroup = $autoCustomerGroup;
    }

    /**
     * @return Button
     */
    public function getValidateButton()
    {
        if ( $this->autoCustomerGroup->isModuleEnabled()) {
            if ($this->_validateButton === null) {
                $form = $this->_element->getForm();
                $taxValidateOptions = $this->_jsonEncoder->encode(
                    [
                        'taxIdElementId' => $this->_element->getHtmlId(),
                        'countryElementId' => $form->getElement('country_id')->getHtmlId(),
                        'postcodeElementId' => $form->getElement('postcode')->getHtmlId(),
                        'groupIdHtmlId' => 'group_id',
                        'validateUrl' => $this->_urlBuilder->getUrl('autocustomergroup/createorder/validate')
                    ]
                );
                $optionsVarName = $this->getJsVariablePrefix() . 'TaxParameters';
                $scriptString = 'var ' . $optionsVarName . ' = ' . $taxValidateOptions . ';';
                $beforeHtml = $this->secureRenderer->renderTag('script', [], $scriptString, false);
                $beforeHtml .= '<div class="admin__field-note-vat_id-note">';
                $beforeHtml .= '<div>We can validate the Tax Identifer and set the Customer Group automatically using the AutoCustomerGroup module.</div>';
                $beforeHtml .= '<div><i>(For UK Orders going to NI, ensure postcode is set before validating)</i>.</div>';
                $beforeHtml .= '</div>';

                $this->_validateButton = $this->getLayout()->createBlock(
                    Button::class
                )->setData(
                    [
                        'label' => __('Validate TAX Identifier'),
                        'before_html' => $beforeHtml,
                        'onclick' => 'order.validateTaxId(' . $optionsVarName . ')'
                    ]
                );
            }
            return $this->_validateButton;
        } else {
            return parent::getValidateButton();
        }
    }
}
