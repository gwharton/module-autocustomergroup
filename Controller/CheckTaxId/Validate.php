<?php

namespace Gw\AutoCustomerGroup\Controller\CheckTaxId;

use Gw\AutoCustomerGroup\Model\AutoCustomerGroup;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Checkout\Model\Session;
use Psr\Log\LoggerInterface;

/**
 * Controller to validate VAT number on frontend
 */
class Validate implements HttpPostActionInterface
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var AutoCustomerGroup
     */
    private $autoCustomerGroup;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var RedirectFactory
     */
    private $redirectFactory;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Validator $validator
     * @param AutoCustomerGroup $autoCustomerGroup
     * @param RequestInterface $request
     * @param RedirectFactory $redirectFactory
     * @param JsonFactory $jsonFactory
     * @param Session $checkoutSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        Validator $validator,
        AutoCustomerGroup $autoCustomerGroup,
        RequestInterface $request,
        RedirectFactory $redirectFactory,
        JsonFactory $jsonFactory,
        Session $checkoutSession,
        LoggerInterface $logger
    ) {
        $this->validator = $validator;
        $this->autoCustomerGroup = $autoCustomerGroup;
        $this->request = $request;
        $this->redirectFactory = $redirectFactory;
        $this->jsonFactory = $jsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
    }

    /**
     * @return ResponseInterface|RedirectInterface|ResultInterface|void
     */
    public function execute()
    {
        $taxIdToCheck = $this->request->getParam('tax_id');
        $countryCode = $this->request->getParam('country_code');
        $quote = $this->checkoutSession->getQuote();

        if (!$this->validator->validate($this->request) || $quote === null) {
            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('*/*/');
        }

        $storeId = $quote->getStoreId();
        $taxIdCheckResponse = null;
        if (!empty($countryCode) && !empty($taxIdToCheck) && $storeId) {
            $taxIdCheckResponse = $this->autoCustomerGroup->checkTaxId(
                $countryCode,
                $taxIdToCheck,
                $storeId
            );
        }

        $responseData = [
            'valid' => false,
            'message' => __('There was an error validating your Tax Id'),
            'success' => false
        ];
        if ($taxIdCheckResponse) {
            $responseData = [
                'valid' => $taxIdCheckResponse->getIsValid(),
                'message' => $taxIdCheckResponse->getRequestMessage(),
                'success' => $taxIdCheckResponse->getRequestSuccess()
            ];
            $address = $quote->getShippingAddress();
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
                __METHOD__ . " Saving TAX ID Validation to Quote Address",
                [
                    'addressType' => $address->getAddressType(),
                    'taxId' => $taxIdToCheck,
                    'valid' => $taxIdCheckResponse->getIsValid()
                ]
            );
            $address->save();
        }
        $resultJson = $this->jsonFactory->create();
        return $resultJson->setData($responseData);
    }
}
