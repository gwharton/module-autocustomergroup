<?php

namespace Gw\AutoCustomerGroup\Model\Collector;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address\Total;
use Psr\Log\LoggerInterface;
use Gw\AutoCustomerGroup\Api\Data\TaxIdCheckResponseInterfaceFactory;
use Gw\AutoCustomerGroup\Model\AutoCustomerGroup as AutoCustomerGroupModel;

/**
 * Magento Vanilla Collectors sequence
 *
 * 100  Magento\Quote\Model\Quote\Address\Total\Subtotal
 * 200  Magento\Tax\Model\Sales\Total\Quote\Subtotal
 * 225  Magento\Weee\Model\Total\Quote\Weee
 * 300  Magento\SalesRule\Model\Quote\Discount
 * 325  <-- This is a good place for us to be. Product discounts already applied
 * 350  Magento\Quote\Model\Quote\Address\Total\Shipping
 * 375  Magento\Tax\Model\Sales\Total\Quote\Shipping
 * 400  Magento\SalesRule\Model\Quote\Address\Total\ShippingDiscount
 * 450  Magento\Tax\Model\Sales\Total\Quote\Tax
 * 460  Magento\Weee\Model\Total\Quote\WeeeTax
 * 550  Magento\Quote\Model\Quote\Address\Total\Grand
 * AutoCustomerGroup totals collector, configured from sales.xml
 * What happens if we switch groups to a group where discount no longer applies? Should we re-run
 * the discount collectors, what if that then changes the order total. We need to re-evaluate groups again.
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class AutoCustomerGroup extends AbstractTotal
{
    /**
     * @var AutoCustomerGroupModel
     */
    private $autoCustomerGroup;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TaxIdCheckResponseInterfaceFactory
     */
    private $ticrFactory;

    /**
     * @param AutoCustomerGroupModel $autoCustomerGroup
     * @param Session $customerSession
     * @param LoggerInterface $logger
     * @param TaxIdCheckResponseInterfaceFactory $ticrFactory
     */
    public function __construct(
        AutoCustomerGroupModel $autoCustomerGroup,
        Session $customerSession,
        LoggerInterface $logger,
        TaxIdCheckResponseInterfaceFactory $ticrFactory
    ) {
        $this->setCode('autocustomergroup');
        $this->autoCustomerGroup = $autoCustomerGroup;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
        $this->ticrFactory = $ticrFactory;
    }

    /**
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ): AutoCustomerGroup {
        parent::collect($quote, $shippingAssignment, $total);

        $items = $shippingAssignment->getItems();
        if (!count($items)) {
            return $this;
        }

        /** @var CustomerInterface $customer */
        $customer = $quote->getCustomer();
        $storeId = $quote->getStoreId();

        if (!($storeId && $this->autoCustomerGroup->isModuleEnabled($storeId))) {
            return $this;
        }

        if ($customer->getId()) {
            $this->logger->debug(
                __METHOD__ . " Existing Customer Group",
                [
                    'groupId' => $customer->getGroupId()
                ]
            );
        }

        if ($customer->getDisableAutoGroupChange()) {
            $this->logger->debug(
                __METHOD__ . " AutoGroupChange disabled for customer"
            );
            return $this;
        }

        $quoteAddress = $quote->getShippingAddress();

        if (empty($quoteAddress->getCountryId())) {
            $this->logger->debug(
                __METHOD__ . " Quote Country Id empty"
            );
            return $this;
        }
        //If we have a customer, start with their CustomerGroupId, otherwise use default group
        $customerGroupId = $customer->getId() ?
            $customer->getGroupId() :
            $this->autoCustomerGroup->getDefaultGroup($storeId);

        $this->logger->debug(
            __METHOD__ . " Starting Group",
            [
                'groupId' => $customerGroupId
            ]
        );

        $taxIdValidated = (bool)($quoteAddress->getData('vat_is_valid') ?? false);

        $this->logger->debug(
            __METHOD__ . " TaxID Validated",
            [
                'validated' => $taxIdValidated
            ]
        );

        //Get the auto assigned group for customer, returns null if group shouldn't be changed.
        $newGroup = $this->autoCustomerGroup->getCustomerGroup(
            $quoteAddress->getCountryId(),
            $taxIdValidated,
            $quote,
            $quoteAddress->getPostcode(),
            $storeId
        );

        if ($newGroup) {
            $this->logger->debug(
                __METHOD__ . " New Group Required",
                [
                    'groupId' => $newGroup
                ]
            );
        } else {
            $this->logger->debug(
                __METHOD__ . " No Group Change Required"
            );
        }

        //Set the group of the $quote object, so the collectTotals will be performed on the
        //correct group. Use newGroup if set, otherwise use $customerGroupId
        $this->updateGroup($newGroup ?: $customerGroupId, $quote, $customer, $shippingAssignment, $total);

        //Also store the group in the quote Extension Attribute. We will check in quote submit
        //observer and set group appropriately (Guest orders will reset group to NOT_LOGGED_IN)
        $extensionAttr = $quote->getExtensionAttributes();
        $extensionAttr->setAutocustomergroupNewId($newGroup ?: $customerGroupId);
        $quote->setExtensionAttributes($extensionAttr);

        return $this;
    }

    /**
     * Process Group Change
     * @param $newGroup
     * @param Quote $quote
     * @param $customer
     * @param $shippingAssignment
     * @param $total
     */
    private function updateGroup($newGroup, Quote $quote, $customer, $shippingAssignment, $total)
    {
        if ($newGroup != $quote->getCustomerGroupId()) {
            $this->customerSession->setCustomerGroupId($newGroup);
            $customer = $quote->getCustomer();
            if ($customer && $customer->getId() !== null) {
                $customer->setGroupId($newGroup);
                $quote->setCustomer($customer);
            }
            $this->logger->info(
                __METHOD__ . " Setting new quote Group",
                [
                    'groupId' => $newGroup
                ]
            );
            $quote->setCustomerGroupId($newGroup);
        }
    }
}
