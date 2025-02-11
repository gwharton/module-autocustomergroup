<?php

namespace Gw\AutoCustomerGroup\Plugin;

use Gw\AutoCustomerGroup\Model\AutoCustomerGroup;
use Magento\Quote\Model\CustomerManagement;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ShippingAddressManagement;

class PopulateCustomerInfoPlugin
{
    /**
     * @var ShippingAddressManagement
     */
    private $shippingAddressManagement;

    /**
     * @var AutoCustomerGroup
     */
    private $autoCustomerGroup;

    public function __construct(
        ShippingAddressManagement $shippingAddressManagement,
        AutoCustomerGroup $autoCustomerGroup
    ) {
        $this->shippingAddressManagement = $shippingAddressManagement;
        $this->autoCustomerGroup = $autoCustomerGroup;
    }

    /**
     * @param CustomerManagement $subject
     * @param null $result
     * @param Quote $quote
     * @return void
     */
    public function afterPopulateCustomerInfo(CustomerManagement $subject, $result, Quote $quote): void
    {
        if ($this->autoCustomerGroup->isModuleEnabled($quote->getStoreId())) {
            //Ensure that the shipping address is re-assigned to the quote. This causes it to be
            //saved, and in doing so, copies the vat validation data to the quote address object
            $this->shippingAddressManagement->assign($quote->getId(), $quote->getShippingAddress());
        }
    }
}
