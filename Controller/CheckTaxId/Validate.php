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
     * @param Validator $validator
     * @param AutoCustomerGroup $autoCustomerGroup
     * @param RequestInterface $request
     * @param RedirectFactory $redirectFactory
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Validator $validator,
        AutoCustomerGroup $autoCustomerGroup,
        RequestInterface $request,
        RedirectFactory $redirectFactory,
        JsonFactory $jsonFactory
    ) {
        $this->validator = $validator;
        $this->autoCustomerGroup = $autoCustomerGroup;
        $this->request = $request;
        $this->redirectFactory = $redirectFactory;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * @return ResponseInterface|RedirectInterface|ResultInterface|void
     */
    public function execute()
    {
        $taxIdToCheck = $this->request->getParam('tax_id');
        $countryCode = $this->request->getParam('country_code');
        $storeId = (int)$this->request->getParam('store_id', 0);
        if (!$this->validator->validate($this->request)) {
            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('*/*/');
        }

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
        }
        $resultJson = $this->jsonFactory->create();
        return $resultJson->setData($responseData);
    }
}
