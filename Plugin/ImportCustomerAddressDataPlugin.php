<?php

namespace Gw\AutoCustomerGroup\Plugin;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote\Address;

class ImportCustomerAddressDataPlugin
{
    /**
     * @param Address $subject
     * @param $result
     * @param AddressInterface $address
     */
    public function afterImportCustomerAddressData(Address $subject, $result, AddressInterface $address)
    {
        $result->setData('vat_is_valid', $address->getExtensionAttributes()->getVatIsValid());
        $result->setData('vat_request_id', $address->getExtensionAttributes()->getVatRequestId());
        $result->setData('vat_request_success', $address->getExtensionAttributes()->getVatRequestSuccess());
        $result->setData('vat_request_date', $address->getExtensionAttributes()->getVatRequestDate());
        $result->setData('validated_vat_number', $address->getExtensionAttributes()->getValidatedVatNumber());
        $result->setData('validated_country_code', $address->getExtensionAttributes()->getValidatedCountryCode());
        return $result;
    }
}
