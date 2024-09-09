<?php

namespace Gw\AutoCustomerGroup\Api\Data;

use Magento\Quote\Model\Quote;

interface TaxSchemeInterface
{
    /**
     * @param string $countryCode
     * @param string|null $taxId
     * @return TaxIdCheckResponseInterface
     */
    public function checkTaxId(
        string $countryCode,
        ?string $taxId
    ): TaxIdCheckResponseInterface;

    /**
     * @param string $customerCountryCode
     * @param string|null $customerPostCode
     * @param bool $taxIdValidated
     * @param float $orderValue
     * @param int|null $storeId
     * @return int|null
     */
    public function getCustomerGroup(
        string $customerCountryCode,
        ?string $customerPostCode,
        bool $taxIdValidated,
        float $orderValue,
        ?int $storeId
    ): ?int;

    /**
     * @param Quote $quote
     * @return float
     */
    public function getOrderValue(
        Quote $quote
    ): float;

    /**
     * @return string
     */
    public function getSchemeName(): string;

    /**
     * @param int|null $storeId
     * @return string|null
     */
    public function getFrontEndPrompt(?int $storeId): ?string;

    /**
     * @return string
     */
    public function getSchemeCurrencyCode(): string;

    /**
     * @param int|null $storeId
     * @return float
     */
    public function getThresholdInSchemeCurrency(?int $storeId): float;

    /**
     * @param int|null $storeId
     * @return string|null
     */
    public function getSchemeRegistrationNumber(?int $storeId): ?string;

    /**
     * @return string
     */
    public function getSchemeId(): string;

    /**
     * @return array
     */
    public function getSchemeCountries(): array;

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId): bool;

    /**
     * @param int|null $storeId
     * @return float
     */
    public function getSchemeExchangeRate(?int $storeId): float;
}
