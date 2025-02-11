<?php

namespace Gw\AutoCustomerGroup\Controller\Adminhtml\Customer;

use Gw\AutoCustomerGroup\Model\AutoCustomerGroup;
use Magento\Backend\App\Action;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Customer\Model\ResourceModel\Address\CollectionFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultInterface;
use Magento\Customer\Model\AddressRegistry;

/**
 * Class MassDelete
 */
class MassValidate extends Action implements HttpPostActionInterface
{
    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var AutoCustomerGroup
     */
    private $autoCustomerGroup;

    /**
     * @var AddressRegistry
     */
    protected $addressRegistry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param AutoCustomerGroup $autoCustomerGroup
     * @param LoggerInterface $logger
     * @param JsonFactory $resultJsonFactory
     * @param AddressRegistry $addressRegistry
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        AutoCustomerGroup $autoCustomerGroup,
        LoggerInterface $logger,
        JsonFactory $resultJsonFactory,
        AddressRegistry $addressRegistry
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->autoCustomerGroup = $autoCustomerGroup;
        parent::__construct($context);
        $this->logger = $logger;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->addressRegistry = $addressRegistry;
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $customerData = $this->_session->getData('customer_data');
        $storeId = $customerData['account']['store_id'];
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collection->addFieldToFilter('parent_id', $customerData['customer_id']);

        /**
         * @var AddressInterface $address
         */
        foreach ($collection as $address) {
            $addressModel = $this->addressRegistry->retrieve($address->getId());
            $taxIdCheckResponse = null;
            $countryCode = $addressModel->getCountryId();
            $taxIdToCheck = $addressModel->getVatId();
            if (!empty($countryCode) && !empty($taxIdToCheck) && $storeId) {
                $taxIdCheckResponse = $this->autoCustomerGroup->checkTaxId(
                    $countryCode,
                    $taxIdToCheck,
                    $storeId
                );
            }
            if ($taxIdCheckResponse) {
                $addressModel->setData('vat_is_valid', $taxIdCheckResponse->getIsValid() ? "1" : "0");
                if ($taxIdCheckResponse->getIsValid() === true) {
                    $addressModel->setData('vat_request_id', $taxIdCheckResponse->getRequestIdentifier());
                    $addressModel->setData('vat_request_date', $taxIdCheckResponse->getRequestDate());
                    $addressModel->setData('validated_vat_number', $taxIdToCheck);
                    $addressModel->setData('validated_country_code', $countryCode);
                    $this->logger->debug(
                        "Gw/AutoCustomerGroup/Controller/Adminhtml/Customer/Validate::execute() : Validated Tax ID. Saving to customer address"
                    );
                } else {
                    $addressModel->setData('vat_request_id', null);
                    $addressModel->setData('vat_request_date', null);
                    $addressModel->setData('validated_vat_number', null);
                    $addressModel->setData('validated_country_code', null);
                    $this->logger->debug(
                        "Gw/AutoCustomerGroup/Controller/Adminhtml/Customer/Validate::execute() : Failed to Validate Tax ID. Saving to customer address"
                    );
                }
                $addressModel->save();
            }
        }
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData(
            [
                'error' => false,
            ]
        );
        return $resultJson;
    }
}
