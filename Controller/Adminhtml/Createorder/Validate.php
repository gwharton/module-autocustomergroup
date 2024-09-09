<?php
namespace Gw\AutoCustomerGroup\Controller\Adminhtml\Createorder;

use Gw\AutoCustomerGroup\Model\AutoCustomerGroup;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Customer\Controller\Adminhtml\System\Config\Validatevat\ValidateAdvanced;
use Magento\Backend\Model\Session\Quote as QuoteSession;

/**
 * Controller to validate VAT number on Admin Create Order Page
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
     * @var ValidateAdvanced
     */
    private $validateAdvanced;

    /**
     * @var QuoteSession
     */
    private $quoteSession;

    /**
     * @param Validator $validator
     * @param AutoCustomerGroup $autoCustomerGroup
     * @param RequestInterface $request
     * @param RedirectFactory $redirectFactory
     * @param JsonFactory $jsonFactory
     * @param ValidateAdvanced $validateAdvanced
     * @param QuoteSession $quoteSession
     */
    public function __construct(
        Validator $validator,
        AutoCustomerGroup $autoCustomerGroup,
        RequestInterface $request,
        RedirectFactory $redirectFactory,
        JsonFactory $jsonFactory,
        ValidateAdvanced $validateAdvanced,
        QuoteSession $quoteSession
    ) {
        $this->validator = $validator;
        $this->autoCustomerGroup = $autoCustomerGroup;
        $this->request = $request;
        $this->redirectFactory = $redirectFactory;
        $this->jsonFactory = $jsonFactory;
        $this->validateAdvanced = $validateAdvanced;
        $this->quoteSession = $quoteSession;
    }

    /**
     * @return ResponseInterface|RedirectInterface|ResultInterface|void
     */
    public function execute()
    {

        $storeId = (int)$this->request->getParam('store_id', 0);
        if ($this->autoCustomerGroup->isModuleEnabled($storeId)) {
            $taxIdToCheck = $this->request->getParam('tax');
            $countryCode = $this->request->getParam('country');
            $postcode = $this->request->getParam('postcode');
            $quote = $this->quoteSession->getQuote();
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
                'group' => null,
                'message' => __('Error checking TAX Identifier'),
                'success' => false
            ];

            if ($taxIdCheckResponse && $quote) {

                $groupId = $this->autoCustomerGroup->getCustomerGroup(
                    $countryCode,
                    $postcode,
                    $taxIdCheckResponse->getIsValid(),
                    $quote,
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
}
