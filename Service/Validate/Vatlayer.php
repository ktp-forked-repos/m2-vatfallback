<?php
/**
 * Dutchento Vatfallback
 * Provides free VAT fallback mechanism
 * Copyright (C) 2018 Dutchento
 *
 * MIT license applies to this software
 */

namespace Dutchento\Vatfallback\Service\Validate;

use Dutchento\Vatfallback\Service\Vatlayer\Client as VatlayerClient;
use Exception;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Vatlayer
 * @package Dutchento\Vatfallback\Service\Validate
 */
class Vatlayer implements ValidationServiceInterface
{
    /** @var bool */
    protected $vatlayerIsEnabled;

    /** @var string */
    protected $vatlayerApiKey;

    /** @var VatlayerClient  */
    protected $vatlayerClient;

    /**
     * Vatlayer constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        VatlayerClient $vatlayerClient
    ) {
        $this->vatlayerClient = $vatlayerClient;
        $this->vatlayerIsEnabled = (bool)$scopeConfig->getValue(
            'customer/vatfallback/vatlayer_validation',
            ScopeInterface::SCOPE_STORE
        );
        $this->vatlayerApiKey = (string)$scopeConfig->getValue(
            'customer/vatfallback/vatlayer_apikey',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritdoc
     * @throws FailedValidationException
     */
    public function validateVATNumber(string $vatNumber, string $countryIso2): bool
    {
        // check if service is enabled and configured
        if (!$this->vatlayerIsEnabled || '' === $this->vatlayerApiKey) {
            return false;
        }

        // call API layer endpoint
        try {
            $clientResponse = $this->vatlayerClient->retrieveVatnumberEndpoint($vatNumber, $countryIso2);
        } catch (Exception $error) {
            throw new FailedValidationException("HTTP error {$error->getMessage()}");
        }
        
        if (isset($clientResponse['error'])) {
            throw new FailedValidationException($clientResponse['error']['info']);
        }

        return (bool)$clientResponse['valid'];
    }
}
