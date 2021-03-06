<?php
/**
 * Dutchento Vatfallback
 * Provides free VAT fallback mechanism
 * Copyright (C) 2018 Dutchento
 *
 * MIT license applies to this software
 */

namespace Dutchento\Vatfallback\Service\Validate;

use Exception;
use GuzzleHttp\Client;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\Information as StoreInformation;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Vies
 * @package Dutchento\Vatfallback\Service\Validate
 */
class Vies implements ValidationServiceInterface
{
    /** @var bool */
    protected $viesIsEnabled;

    /** @var float */
    protected $viesTimeout;

    /** @var ScopeConfigInterface  */
    protected $scopeConfig;

    /**
     * Vatlayer constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->viesIsEnabled = (bool)$scopeConfig->getValue(
            'customer/vatfallback/vies_validation',
            ScopeInterface::SCOPE_STORE
        );
        $this->viesTimeout = (float)$scopeConfig->getValue(
            'customer/vatfallback/vatlayer_timeout',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritdoc
     * @param string $vatNumber
     * @param string $countryIso2
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function validateVATNumber(string $vatNumber, string $countryIso2): bool
    {
        // check if service is enabled and configured
        if (!$this->viesIsEnabled) {
            return false;
        }

        // call API layer endpoint
        try {
            $client = new Client(['base_uri' => 'http://ec.europa.eu']);

            $response = $client->request('GET', '/taxation_customs/vies/viesquer.do', [
                'connect_timeout' => max(1, $this->viesTimeout),
                'query' => [
                    'ms' => $countryIso2,
                    'iso' => $countryIso2,
                    'vat' => $countryIso2 . $vatNumber,
                    'requesterMs' => $this->getMerchantCountryCode(),
                    'requesterIso' => $this->getMerchantCountryCode(),
                    'requesterVat' => $this->getMerchantVatNumber(),
                    'BtnSubmitVat' => 'Verify',
                ]
            ]);
        } catch (Exception $error) {
            throw new FailedValidationException("HTTP error {$error->getMessage()}");
        }

        // did we get a valid statuscode
        if ($response->getStatusCode() > 299) {
            throw new FailedValidationException(
                "Vatlayer API responded with status {$response->getStatusCode()}, 
                body {$response->getBody()->getContents()}"
            );
        }

        // body of API contains a valid flag
        return (false !== strpos($response->getBody()->getContents(), 'Yes, valid VAT number'));
    }

    /**
     * Get merchant country code from config
     *
     * @return string
     */
    public function getMerchantCountryCode(): string
    {
        return (string)$this->scopeConfig->getValue(StoreInformation::XML_PATH_STORE_INFO_COUNTRY_CODE);
    }

    /**
     * Get merchant VAT number from config
     *
     * @return string
     */
    public function getMerchantVatNumber(): string
    {
        return (string)$this->scopeConfig->getValue(StoreInformation::XML_PATH_STORE_INFO_VAT_NUMBER);
    }
}
