<?php

namespace Gw\AutoCustomerGroup\Observer;

use Gw\AutoCustomerGroup\Model\AutoCustomerGroup;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Magento\Tax\Model\TaxRuleRepository;
use Gw\AutoCustomerGroup\Api\Data\OrderTaxSchemeInterfaceFactory;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Log the tax scheme information to the sales_order_tax_scheme table
 */
class SalesModelServiceQuoteSubmitSuccess implements ObserverInterface
{
    /**
     * @var AutoCustomerGroup
     */
    private $autoCustomerGroup;

    /**
     * @var TaxRuleRepository
     */
    private $taxRuleRepository;

    /**
     * @var OrderTaxSchemeInterfaceFactory
     */
    private $otsFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param TaxRuleRepository $taxRuleRepository
     * @param OrderTaxSchemeInterfaceFactory $otsFactory
     * @param AutoCustomerGroup $autoCustomerGroup
     * @param LoggerInterface $logger
     */
    public function __construct(
        TaxRuleRepository $taxRuleRepository,
        OrderTaxSchemeInterfaceFactory $otsFactory,
        AutoCustomerGroup $autoCustomerGroup,
        LoggerInterface $logger
    ) {
        $this->taxRuleRepository = $taxRuleRepository;
        $this->otsFactory = $otsFactory;
        $this->autoCustomerGroup = $autoCustomerGroup;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        //Loop through the applied taxes on the order and extract the Tax Rule IDs that have been triggered
        /** @var Order $order */
        $order = $observer->getData('order');
        $storeId = $order->getStoreId();
        if (!$this->autoCustomerGroup->isSalesOrderTaxSchemeEnabled($storeId)) {
            return;
        }
        $orderEA = $order->getExtensionAttributes();
        $orderrules = [];
        if ($orderEA) {
            $appliedTaxes = $orderEA->getAppliedTaxes();
            if ($appliedTaxes) {
                foreach ($appliedTaxes as $appliedTax) {
                    $appliedTaxEA = $appliedTax->getExtensionAttributes();
                    if ($appliedTaxEA) {
                        $rates = $appliedTaxEA->getRates();
                        if ($rates) {
                            foreach ($rates as $rate) {
                                $ratesEA = $rate->getExtensionAttributes();
                                if ($ratesEA) {
                                    $orderrules = array_unique(
                                        array_merge($orderrules, $ratesEA->getTaxRuleIds() ?: [])
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }

        //Loop through the Tax Rules that have been triggered and extract the Tax Schemes Linked to those rules.
        $taxSchemes = [];
        foreach ($orderrules as $orderrule) {
            try {
                $taxRule = $this->taxRuleRepository->get($orderrule);
                $taxRuleEA = $taxRule->getExtensionAttributes();
                if ($taxRuleEA && $taxRuleEA->getTaxScheme()) {
                    $taxSchemes[] = $taxRuleEA->getTaxScheme();
                }
            } catch (Exception $e) {
                //Could not load Tax Rule
            }
        }
        $taxSchemes = array_unique($taxSchemes);

        //Save the tax scheme info in the sales_order_tax_scheme table.
        foreach ($taxSchemes as $taxScheme) {
            $storeId = $order->getStoreId();
            $baseToStore = 1 / ($order->getStoreToBaseRate() == 0.0 ? 1.0 : $order->getStoreToBaseRate());
            $thresholdInBaseCurrency = (float)$taxScheme->getThresholdInSchemeCurrency($storeId) * $taxScheme->getSchemeExchangeRate($storeId);
            $orderTaxScheme = $this->otsFactory->create();
            $orderTaxScheme->setOrderId((int)$order->getEntityId());
            $orderTaxScheme->setReference($taxScheme->getSchemeRegistrationNumber($storeId));
            $orderTaxScheme->setName($taxScheme->getSchemeName());
            $orderTaxScheme->setStoreCurrency($order->getOrderCurrencyCode());
            $orderTaxScheme->setBaseCurrency($order->getBaseCurrencyCode());
            $orderTaxScheme->setSchemeCurrency($taxScheme->getSchemeCurrencyCode());
            $orderTaxScheme->setExchangeRateBaseToStore((float)$baseToStore);
            $orderTaxScheme->setExchangeRateSchemeToBase((float)$taxScheme->getSchemeExchangeRate($storeId));
            $orderTaxScheme->setImportThresholdBase((float)$thresholdInBaseCurrency);
            $orderTaxScheme->setImportThresholdStore((float)$thresholdInBaseCurrency * $baseToStore);
            $orderTaxScheme->setImportThresholdScheme((float)$taxScheme->getThresholdInSchemeCurrency($storeId));
            $orderTaxScheme->save();
            $this->logger->info(
                "Gw/AutoCustomerGroup/Observer/SalesModelServiceQuoteSubmitSuccess::execute() : Saving Tax " .
                "Scheme to database " . $orderTaxScheme->getName()
            );
        }
    }
}
