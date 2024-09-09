<?php

namespace Gw\AutoCustomerGroup\Model;

use Exception;
use Gw\AutoCustomerGroup\Api\Data\OrderTaxCollectedInterface;
use Gw\AutoCustomerGroup\Api\Data\OrderTaxSchemeInterface;
use Gw\AutoCustomerGroup\Model\ResourceModel\OrderTaxScheme\CollectionFactory;

class OrderTaxCollected implements OrderTaxCollectedInterface
{
    /**
     * @var CollectionFactory
     */
    private $orderTaxSchemeCollectionFactory;

    /**
     * @param CollectionFactory $orderTaxSchemeCollectionFactory
     */
    public function __construct(
        CollectionFactory $orderTaxSchemeCollectionFactory
    ) {
        $this->orderTaxSchemeCollectionFactory = $orderTaxSchemeCollectionFactory;
    }

    public function getTaxCollectedDetails(int $orderId): array
    {
        try {
            $taxDetails = [];
            $orderTaxSchemes = $this->orderTaxSchemeCollectionFactory->create()->loadByOrderId($orderId);
            foreach ($orderTaxSchemes->getItems() as $orderTaxScheme) {
                /** @var OrderTaxSchemeInterface $orderTaxScheme */
                $taxDetails[] = [
                    'name' => $orderTaxScheme->getName(),
                    'id' => $orderTaxScheme->getReference(),
                    'type' => null
                ];
            }
            return $taxDetails;
        } catch (Exception $e) {
            return [];
        }
    }
}
