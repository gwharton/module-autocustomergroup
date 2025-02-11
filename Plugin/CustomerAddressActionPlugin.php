<?php

namespace Gw\AutoCustomerGroup\Plugin;

use Magento\Customer\Ui\Component\Listing\Address\Column\Actions;
use Magento\Framework\UrlInterface;

class CustomerAddressActionPlugin
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        UrlInterface $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param Actions $subject
     * @param array $result
     * @param array $dataSource
     * @return array
     */
    public function afterPrepareDataSource(Actions $subject, array $result, array $dataSource): array
    {
        if (isset($result['data']['items'])) {
            foreach ($result['data']['items'] as &$item) {
                if (isset($item['entity_id'])) {
                    $item['actions']['validate'] = [
                        'href' => $this->urlBuilder->getUrl(
                            'autocustomergroup/customer/validate',
                            ['parent_id' => $item['parent_id'], 'id' => $item['entity_id']]
                        ),
                        'label' => __('Validate TAX ID'),
                        'isAjax' => true,
                        'confirm' => [
                            'title' => __('Validate TAX ID'),
                            'message' => __('Are you sure you want to validate the TAX ID?')
                        ]
                    ];
                }
            }
        }
        return $result;
    }
}
