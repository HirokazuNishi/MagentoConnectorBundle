<?php

namespace Pim\Bundle\MagentoConnectorBundle\Processor;

use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Pim\Bundle\CatalogBundle\Model\Association;
use Pim\Bundle\MagentoConnectorBundle\Manager\AssociationTypeManager;
use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Guesser\NormalizerGuesser;
use Pim\Bundle\MagentoConnectorBundle\Webservice\SoapCallException;
use Pim\Bundle\MagentoConnectorBundle\Manager\LocaleManager;
use Pim\Bundle\MagentoConnectorBundle\Merger\MagentoMappingMerger;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;

/**
 * Magento product processor
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductAssociationProcessor extends AbstractProcessor
{
    const MAGENTO_UP_SELL    = 'up_sell';
    const MAGENTO_CROSS_SELL = 'cross_sell';
    const MAGENTO_RELATED    = 'related';
    const MAGENTO_GROUPED    = 'grouped';

    /**
     * @var AssociationTypeManager
     */
    protected $associationTypeManager;

    /**
     * @var string
     */
    protected $pimUpSell;

    /**
     * @var string
     */
    protected $pimCrossSell;

    /**
     * @var string
     */
    protected $pimRelated;

    /**
     * @var string
     */
    protected $pimGrouped;

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param ProductNormalizerGuesser            $normalizerGuesser
     * @param LocaleManager                       $localeManager
     * @param MagentoMappingMerger                $storeViewMappingMerger
     * @param AssociationTypeManager              $associationTypeManager
     * @param MagentoSoapClientParametersRegistry $clientParametersRegistry
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        NormalizerGuesser $normalizerGuesser,
        LocaleManager $localeManager,
        MagentoMappingMerger $storeViewMappingMerger,
        AssociationTypeManager $associationTypeManager,
        MagentoSoapClientParametersRegistry $clientParametersRegistry
    ) {
        parent::__construct(
            $webserviceGuesser,
            $normalizerGuesser,
            $localeManager,
            $storeViewMappingMerger,
            $clientParametersRegistry
        );

        $this->associationTypeManager = $associationTypeManager;
    }

    /**
     * Get pimUpSell
     * @return string
     */
    public function getPimUpSell()
    {
        return $this->pimUpSell;
    }

    /**
     * Set pimUpSell
     * @param string $pimUpSell
     *
     * @return ProductAssociationProcessor
     */
    public function setPimUpSell($pimUpSell)
    {
        $this->pimUpSell = $pimUpSell;

        return $this;
    }

    /**
     * Get pimCrossSell
     * @return string
     */
    public function getPimCrossSell()
    {
        return $this->pimCrossSell;
    }

    /**
     * Set pimCrossSell
     * @param string $pimCrossSell
     *
     * @return ProductAssociationProcessor
     */
    public function setPimCrossSell($pimCrossSell)
    {
        $this->pimCrossSell = $pimCrossSell;

        return $this;
    }

    /**
     * Get pimRelated
     * @return string
     */
    public function getPimRelated()
    {
        return $this->pimRelated;
    }

    /**
     * Set pimRelated
     * @param string $pimRelated
     *
     * @return ProductAssociationProcessor
     */
    public function setPimRelated($pimRelated)
    {
        $this->pimRelated = $pimRelated;

        return $this;
    }

    /**
     * Get pimGrouped
     * @return string
     */
    public function getPimGrouped()
    {
        return $this->pimGrouped;
    }

    /**
     * Set pimGrouped
     * @param string $pimGrouped
     *
     * @return ProductAssociationProcessor
     */
    public function setPimGrouped($pimGrouped)
    {
        $this->pimGrouped = $pimGrouped;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function process($items)
    {
        $items = is_array($items) ? $items : [$items];

        $this->beforeExecute();

        $productAssociationCalls = ['remove' => [], 'create' => []];

        foreach ($items as $product) {
            try {
                $associationsStatus = $this->webservice->getAssociationsStatus($product);
            } catch (SoapCallException $e) {
                throw new InvalidItemException($e->getMessage(), [$product->getIdentifier()]);
            }

            $productAssociationCalls['remove'] = array_merge(
                $productAssociationCalls['remove'],
                $this->getRemoveCallsForProduct($product, $associationsStatus)
            );
            $productAssociationCalls['create'] = array_merge(
                $productAssociationCalls['create'],
                $this->getCreateCallsForProduct($product)
            );
        }

        return $productAssociationCalls;
    }

    /**
     * Get create calls for a given product
     * @param ProductInterface $product
     *
     * @return array
     */
    protected function getCreateCallsForProduct(ProductInterface $product)
    {
        $createAssociationCalls = [];

        foreach ($product->getAssociations() as $productAssociation) {
            $createAssociationCalls = array_merge(
                $createAssociationCalls,
                $this->getCreateCallsForAssociation($product, $productAssociation)
            );
        }

        return $createAssociationCalls;
    }

    /**
     * Get create calls
     * @param ProductInterface $product
     * @param Association      $association
     *
     * @return array
     */
    protected function getCreateCallsForAssociation(ProductInterface $product, Association $association)
    {
        $createAssociationCalls = [];

        $associationType = $association->getAssociationType()->getCode();

        if (in_array($associationType, array_keys($this->getAssociationCodeMapping()))) {
            foreach ($association->getProducts() as $associatedProduct) {
                $createAssociationCalls[] = [
                    'type'           => $this->getAssociationCodeMapping()[$associationType],
                    'product'        => (string) $product->getIdentifier(),
                    'linkedProduct'  => (string) $associatedProduct->getIdentifier(),
                    'identifierType' => 'sku'
                ];
            }
        }

        return $createAssociationCalls;
    }

    /**
     * Get remove association calls for a given product
     * @param ProductInterface $product
     * @param array            $associationStatus
     *
     * @return return array
     */
    protected function getRemoveCallsForProduct(ProductInterface $product, array $associationStatus)
    {
        $removeAssociationCalls = [];

        foreach ($associationStatus as $associationType => $associatedProducts) {
            foreach ($associatedProducts as $associatedProduct) {
                $removeAssociationCalls[] = [
                    'type'           => $associationType,
                    'product'        => (string) $product->getIdentifier(),
                    'linkedProduct'  => (string) $associatedProduct['sku'],
                    'identifierType' => 'sku'
                ];
            }
        }

        return $removeAssociationCalls;
    }

    /**
     * Get association code mapping
     * @return array
     */
    protected function getAssociationCodeMapping()
    {
        $associationCodeMapping = [];

        if ($this->getPimUpSell()) {
            $associationCodeMapping[$this->getPimUpSell()] = self::MAGENTO_UP_SELL;
        }

        if ($this->getPimCrossSell()) {
            $associationCodeMapping[$this->getPimCrossSell()] = self::MAGENTO_CROSS_SELL;
        }

        if ($this->getPimRelated()) {
            $associationCodeMapping[$this->getPimRelated()] = self::MAGENTO_RELATED;
        }

        if ($this->getPimGrouped()) {
            $associationCodeMapping[$this->getPimGrouped()] = self::MAGENTO_GROUPED;
        }

        return $associationCodeMapping;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return array_merge(
            parent::getConfigurationFields(),
            [
                'pimUpSell' => [
                    'type'    => 'choice',
                    'options' => [
                        'choices'  => $this->associationTypeManager->getAssociationTypeChoices(),
                        'help'     => 'pim_magento_connector.export.pimUpSell.help',
                        'label'    => 'pim_magento_connector.export.pimUpSell.label',
                        'attr' => [
                            'class' => 'select2'
                        ]
                    ]
                ],
                'pimCrossSell' => [
                    'type'    => 'choice',
                    'options' => [
                        'choices'  => $this->associationTypeManager->getAssociationTypeChoices(),
                        'help'     => 'pim_magento_connector.export.pimCrossSell.help',
                        'label'    => 'pim_magento_connector.export.pimCrossSell.label',
                        'attr' => [
                            'class' => 'select2'
                        ]
                    ]
                ],
                'pimRelated' => [
                    'type'    => 'choice',
                    'options' => [
                        'choices'  => $this->associationTypeManager->getAssociationTypeChoices(),
                        'help'     => 'pim_magento_connector.export.pimRelated.help',
                        'label'    => 'pim_magento_connector.export.pimRelated.label',
                        'attr' => [
                            'class' => 'select2'
                        ]
                    ]
                ],
                'pimGrouped' => [
                    'type'    => 'choice',
                    'options' => [
                        'choices'  => $this->associationTypeManager->getAssociationTypeChoices(),
                        'help'     => 'pim_magento_connector.export.pimGrouped.help',
                        'label'    => 'pim_magento_connector.export.pimGrouped.label',
                        'attr' => [
                            'class' => 'select2'
                        ]
                    ]
                ]
            ]
        );
    }
}
