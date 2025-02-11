<?php

namespace Gw\AutoCustomerGroup\Controller\Adminhtml\Customer;

use Exception;
use Gw\AutoCustomerGroup\Model\AutoCustomerGroup;
use Magento\Backend\App\Action;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Customer\Model\AddressRegistry;

class Validate extends Action implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AddressRegistry
     */
    protected $addressRegistry;

    /**
     * @var AutoCustomerGroup
     */
    private $autoCustomerGroup;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @param Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param LoggerInterface $logger
     * @param AutoCustomerGroup $autoCustomerGroup
     * @param AddressRegistry $addressRegistry
     * @param CustomerRepository $customerRepository
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        LoggerInterface $logger,
        AutoCustomerGroup $autoCustomerGroup,
        AddressRegistry $addressRegistry,
        CustomerRepository $customerRepository
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->logger = $logger;
        $this->autoCustomerGroup = $autoCustomerGroup;
        $this->addressRegistry = $addressRegistry;
        $this->customerRepository = $customerRepository;
    }


    /**
     * @return Json
     * @throws NoSuchEntityException
     */
    public function execute(): Json
    {
        $customerId = $this->getRequest()->getParam('parent_id', false);
        $addressId = $this->getRequest()->getParam('id', false);
        try {
            $addressModel = $this->addressRegistry->retrieve($addressId);
            $customer = $this->customerRepository->getById($customerId);
            $storeId = $customer->getStoreId();
            $error = false;
            $message = '';

            if ($addressModel->getCustomerId() === $customerId) {
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
        } catch (Exception $e) {
            $error = true;
            $message = $e->getMessage();
        }

        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData(
            [
                'message' => $message,
                'error' => $error,
            ]
        );

        return $resultJson;
    }
}
