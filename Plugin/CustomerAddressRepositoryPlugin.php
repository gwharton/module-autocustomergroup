<?php

namespace Gw\AutoCustomerGroup\Plugin;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\ResourceModel\AddressRepository;
use Magento\Customer\Model\AddressRegistry;

class CustomerAddressRepositoryPlugin
{
    /**
     * @var AddressRegistry
     */
    protected $addressRegistry;

    public function __construct(
        AddressRegistry $addressRegistry
    ) {
        $this->addressRegistry = $addressRegistry;
    }

    /**
     * @param AddressRepository $subject
     * @param AddressInterface $result
     * @param int $addressId
     * @return AddressInterface
     */
    public function afterGetById(AddressRepository $subject, AddressInterface $result, $addressId): AddressInterface
    {
        $address = $this->addressRegistry->retrieve($addressId);
        if ($address) {
            $extensionAttributes = $result->getExtensionAttributes();
            $extensionAttributes->setVatIsValid((bool)$address->getData('vat_is_valid'));
            $extensionAttributes->setVatRequestDate($address->getData('vat_request_date'));
            $extensionAttributes->setVatRequestId($address->getData('vat_request_id'));
            $extensionAttributes->setVatRequestSuccess($address->getData('vat_request_success'));
            $extensionAttributes->setVatRequestSuccess($address->getData('vat_request_success'));
            $extensionAttributes->setValidatedVatNumber($address->getData('validated_vat_number'));
            $extensionAttributes->setValidatedCountryCode($address->getData('validated_country_code'));
            $result->setExtensionAttributes($extensionAttributes);
        }
        return $result;
    }
}
