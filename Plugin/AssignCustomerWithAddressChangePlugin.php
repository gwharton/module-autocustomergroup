<?php

namespace Gw\AutoCustomerGroup\Plugin;

use Gw\AutoCustomerGroup\Model\AutoCustomerGroup;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\ShippingAddressManagement;

class AssignCustomerWithAddressChangePlugin
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
     * @param Quote $subject
     * @param $result
     * @param CustomerInterface $customer
     * @param Address|null $billingAddress
     * @param Address|null $shippingAddress
     */
    public function afterAssignCustomerWithAddressChange(Quote $subject, $result, CustomerInterface $customer, Address $billingAddress = null, Address $shippingAddress = null)
    {
        if ($this->autoCustomerGroup->isModuleEnabled($subject->getStoreId())) {
            //Ensure that the shipping address is re-assigned to the quote. This causes it to be
            //saved, and in doing so, copies the vat validation data to the quote address object
            $this->shippingAddressManagement->assign($result->getId(), $result->getShippingAddress());
        }
        return $result;
    }
}
