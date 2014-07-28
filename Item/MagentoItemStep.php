<?php

namespace Pim\Bundle\MagentoConnectorBundle\Item;

use Symfony\Component\Validator\Constraints as Assert;
use Akeneo\Bundle\BatchBundle\Item\AbstractConfigurableStepElement;
use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParameters;
use Pim\Bundle\MagentoConnectorBundle\Webservice\Webservice;
use Pim\Bundle\MagentoConnectorBundle\Validator\Constraints\HasValidCredentials;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

/**
 * Magento item step
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @HasValidCredentials(groups={"Execution"})
 */
abstract class MagentoItemStep extends AbstractConfigurableStepElement implements StepExecutionAwareInterface
{
    /**
     * @var Webservice
     */
    protected $webservice;

    /**
     * @var StepExecution
     */
    protected $stepExecution;

    /**
     * @var WebserviceGuesser
     */
    protected $webserviceGuesser;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     */
    protected $defaultStoreView = Webservice::SOAP_DEFAULT_STORE_VIEW;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     */
    protected $soapUsername;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     */
    protected $wsdlUrl = MagentoSoapClientParameters::SOAP_WSDL_URL;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     * @Assert\Url(groups={"Execution"})
     */
    protected $magentoUrl;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     */
    protected $soapApiKey;

    /**
     * @var string Http login
     */
    protected $httpLogin;

    /**
     * @var string Http password
     */
    protected $httpPassword;

    /**
     * @var MagentoSoapClientParameters
     */
    protected $clientParameters;

    /**
     * @var MagentoSoapClientParametersRegistry
     */
    protected $clientParametersRegistry;

    /**
     * @var boolean
     */
    protected $beforeExecute = false;

    /**
     * @var boolean
     */
    protected $afterConfiguration = false;

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param MagentoSoapClientParametersRegistry $clientParametersRegistry
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        MagentoSoapClientParametersRegistry $clientParametersRegistry
    ) {
        $this->clientParametersRegistry = $clientParametersRegistry;
        $this->webserviceGuesser        = $webserviceGuesser;
    }

    /**
     * @param StepExecution $stepExecution
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfiguration(array $config)
    {
        parent::setConfiguration($config);

        if (!$this->afterConfiguration) {
            $this->afterConfigurationSet();

            $this->afterConfiguration = true;
        }
    }

    /**
     * Get fields for the twig
     *
     * @return array
     */
    public function getConfigurationFields()
    {
        return [
            'soapUsername' => [
                'options' => [
                    'required' => true,
                    'help'     => 'pim_magento_connector.export.soapUsername.help',
                    'label'    => 'pim_magento_connector.export.soapUsername.label'
                ]
            ],
            'soapApiKey'   => [
                //Should be replaced by a password formType but which doesn't
                //empty the field at each edit
                'type'    => 'text',
                'options' => [
                    'required' => true,
                    'help'     => 'pim_magento_connector.export.soapApiKey.help',
                    'label'    => 'pim_magento_connector.export.soapApiKey.label'
                ]
            ],
            'magentoUrl' => [
                'options' => [
                    'required' => true,
                    'help'     => 'pim_magento_connector.export.magentoUrl.help',
                    'label'    => 'pim_magento_connector.export.magentoUrl.label'
                ]
            ],
            'wsdlUrl' => [
                'options' => [
                    'required' => true,
                    'help'     => 'pim_magento_connector.export.wsdlUrl.help',
                    'label'    => 'pim_magento_connector.export.wsdlUrl.label',
                    'data'     => $this->getWsdlUrl()
                ]
            ],
            'httpLogin' => [
                'options' => [
                    'required' => false,
                    'help'     => 'pim_magento_connector.export.httpLogin.help',
                    'label'    => 'pim_magento_connector.export.httpLogin.label'
                ]
            ],
            'httpPassword' => [
                'options' => [
                    'required' => false,
                    'help'     => 'pim_magento_connector.export.httpPassword.help',
                    'label'    => 'pim_magento_connector.export.httpPassword.label'
                ]
            ],
            'defaultStoreView' => [
                'options' => [
                    'required' => false,
                    'help'     => 'pim_magento_connector.export.defaultStoreView.help',
                    'label'    => 'pim_magento_connector.export.defaultStoreView.label',
                    'data'     => $this->getDefaultStoreView()
                ]
            ]
        ];
    }

    /**
     * Get soap username
     *
     * @return string Soap magento soapUsername
     */
    public function getSoapUsername()
    {
        return $this->soapUsername;
    }

    /**
     * Set soap username
     *
     * @param string $soapUsername Soap magento soapUsername
     *
     * @return MagentoItemStep
     */
    public function setSoapUsername($soapUsername)
    {
        $this->soapUsername = $soapUsername;

        return $this;
    }

    /**
     * Get default store view
     *
     * @return string Default store view
     */
    public function getDefaultStoreView()
    {
        return $this->defaultStoreView;
    }

    /**
     * Set default store view
     *
     * @param string $defaultStoreView
     *
     * @return MagentoItemStep
     */
    public function setDefaultStoreView($defaultStoreView)
    {
        $this->defaultStoreView = $defaultStoreView;

        return $this;
    }

    /**
     * Get soap api key
     *
     * @return string Soap magento soapApiKey
     */
    public function getSoapApiKey()
    {
        return $this->soapApiKey;
    }

    /**
     * Set soap api key
     *
     * @param string $soapApiKey Soap magento soapApiKey
     *
     * @return MagentoItemStep
     */
    public function setSoapApiKey($soapApiKey)
    {
        $this->soapApiKey = $soapApiKey;

        return $this;
    }

    /**
     * Get wsdl url
     *
     * @return string magento wsdl relative url
     */
    public function getWsdlUrl()
    {
        return $this->wsdlUrl;
    }

    /**
     * Set wsdl url
     *
     * @param string $wsdlUrl wsdl relative url
     *
     * @return MagentoItemStep
     */
    public function setWsdlUrl($wsdlUrl)
    {
        $this->wsdlUrl = $wsdlUrl;

        return $this;
    }

    /**
     * Get magento url
     *
     * @return string Soap magento url
     */
    public function getMagentoUrl()
    {
        return $this->magentoUrl;
    }

    /**
     * Set magento url
     *
     * @param string $magentoUrl
     *
     * @return MagentoItemStep
     */
    public function setMagentoUrl($magentoUrl)
    {
        $this->magentoUrl = $magentoUrl;

        return $this;
    }

    /**
     * Get soap url
     *
     * @return string magento soap url
     */
    public function getSoapUrl()
    {
        return $this->magentoUrl . $this->wsdlUrl;
    }

    /**
     * Set http login
     *
     * @param string $httpLogin
     *
     * @return MagentoItemStep
     */
    public function setHttpLogin($httpLogin)
    {
        $this->httpLogin = $httpLogin;

        return $this;
    }

    /**
     * Get http login
     *
     * @return string Http login
     */
    public function getHttpLogin()
    {
        return $this->httpLogin;
    }

    /**
     * Set http password
     *
     * @param string $httpPassword
     *
     * @return MagentoItemStep
     */
    public function setHttpPassword($httpPassword)
    {
        $this->httpPassword = $httpPassword;

        return $this;
    }

    /**
     * Get http password
     *
     * @return string Http password
     */
    public function getHttpPassword()
    {
        return $this->httpPassword;
    }

    /**
     * Function called before all item step execution
     */
    protected function beforeExecute()
    {
        if ($this->beforeExecute) {
            return;
        }

        $this->beforeExecute = true;

        $this->webservice = $this->webserviceGuesser->getWebservice($this->getClientParameters());
    }

    /**
     * Called after configuration set
     */
    protected function afterConfigurationSet()
    {
    }

    /**
     * Get the magento soap client parameters
     *
     * @return MagentoSoapClientParametersRegistry
     */
    protected function getClientParameters()
    {
        if (!$this->clientParameters) {
            $this->clientParameters = $this->clientParametersRegistry->getInstance(
                $this->soapUsername,
                $this->soapApiKey,
                $this->magentoUrl,
                $this->wsdlUrl,
                $this->defaultStoreView,
                $this->httpLogin,
                $this->httpPassword
            );
        }

        return $this->clientParameters;
    }
}
