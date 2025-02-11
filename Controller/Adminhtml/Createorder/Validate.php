<?php

namespace Gw\AutoCustomerGroup\Controller\Adminhtml\Createorder;

use Gw\AutoCustomerGroup\Model\AutoCustomerGroup;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Customer\Controller\Adminhtml\System\Config\Validatevat\ValidateAdvanced;
use Magento\Backend\Model\Session\Quote as QuoteSession;
use Psr\Log\LoggerInterface;

/**
 * Controller to validate VAT number on Admin Create Order Page
 */
class Validate implements HttpPostActionInterface
{
    /**
     * @var AutoCustomerGroup
     */
    private $autoCustomerGroup;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var ValidateAdvanced
     */
    private $validateAdvanced;

    /**
     * @var QuoteSession
     */
    private $quoteSession;

    /**
     * @var LoggerInterface
     */
    private $logger;


    /**
     * @param AutoCustomerGroup $autoCustomerGroup
     * @param RequestInterface $request
     * @param JsonFactory $jsonFactory
     * @param ValidateAdvanced $validateAdvanced
     * @param QuoteSession $quoteSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        AutoCustomerGroup $autoCustomerGroup,
        RequestInterface $request,
        JsonFactory $jsonFactory,
        ValidateAdvanced $validateAdvanced,
        QuoteSession $quoteSession,
        LoggerInterface $logger
    ) {
        $this->autoCustomerGroup = $autoCustomerGroup;
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->validateAdvanced = $validateAdvanced;
        $this->quoteSession = $quoteSession;
        $this->logger = $logger;
    }

    /**
     * @return ResponseInterface|RedirectInterface|ResultInterface|void
     */
    public function execute()
    {
        $quote = $this->quoteSession->getQuote();
        $storeId = $quote->getStoreId();
        if ($this->autoCustomerGroup->isModuleEnabled($storeId)) {
            $taxIdToCheck = $this->request->getParam('tax');
            $countryCode = $this->request->getParam('country');
            $postcode = $this->request->getParam('postcode');
            $type = $this->request->getParam('type');

            $taxIdCheckResponse = null;
            if (!empty($countryCode) && !empty($taxIdToCheck)) {
                $taxIdCheckResponse = $this->autoCustomerGroup->checkTaxId(
                    $countryCode,
                    $taxIdToCheck,
                    $storeId
                );
            }
            $responseData = [
                'valid' => false,
                'group' => null,
                'message' => __('Error checking TAX Identifier'),
                'success' => false
            ];

            if ($taxIdCheckResponse) {
                if ($type === "both" || $type === "shippingAddress") {
                    $this->processAddress($quote->getShippingAddress(), $taxIdCheckResponse, $taxIdToCheck, $countryCode);
                }
                if ($type === "both" || $type === "billingAddress") {
                    $this->processAddress($quote->getBillingAddress(), $taxIdCheckResponse, $taxIdToCheck, $countryCode);
                }

                $groupId = $this->autoCustomerGroup->getCustomerGroup(
                    $countryCode,
                    $taxIdCheckResponse->getIsValid(),
                    $quote,
                    $postcode,
                    $storeId
                );
                $responseData = [
                    'valid' => $taxIdCheckResponse->getIsValid(),
                    'group' => (int)$groupId,
                    'message' => $taxIdCheckResponse->getRequestMessage(),
                    'success' => $taxIdCheckResponse->getRequestSuccess()
                ];
            }
        } else {
            $responseData = $this->validateAdvanced->execute();
        }
        $resultJson = $this->jsonFactory->create();
        return $resultJson->setData($responseData);
    }

    private function processAddress($address, $taxIdCheckResponse, $taxIdToCheck, $countryCode)
    {
        $address->setData('vat_is_valid', $taxIdCheckResponse->getIsValid());
        if ($taxIdCheckResponse->getIsValid() === true) {
            $address->setData('vat_request_id', $taxIdCheckResponse->getRequestIdentifier());
            $address->setData('vat_request_date', $taxIdCheckResponse->getRequestDate());
            $address->setData('validated_vat_number', $taxIdToCheck);
            $address->setData('validated_country_code', $countryCode);
        } else {
            $address->setData('vat_request_id', null);
            $address->setData('vat_request_date', null);
            $address->setData('validated_vat_number', null);
            $address->setData('validated_country_code', null);
        }
        $this->logger->debug(
            "Gw/AutoCustomerGroup/Controller/Adminhtml/CreateOrder/Validate::execute() : Saving TAX ID Validation to Quote Address",
            [
                'addressType' => $address->getAddressType(),
                'taxId' => $taxIdToCheck,
                'valid' => $taxIdCheckResponse->getIsValid()
            ]
        );
        $address->save();
    }
}
