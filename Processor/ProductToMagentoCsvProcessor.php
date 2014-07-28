<?php

namespace Pim\Bundle\MagentoConnectorBundle\Processor;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Guesser\NormalizerGuesser;
use Pim\Bundle\MagentoConnectorBundle\Manager\LocaleManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\AttributeManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\AssociationTypeManager;
use Pim\Bundle\MagentoConnectorBundle\Merger\MagentoMappingMerger;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\AbstractNormalizer;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;

/**
 * Magento Csv product processor
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductToMagentoCsvProcessor extends AbstractProcessor
{
    /** @var string */
    protected $pimGrouped;

    /** @var string */
    protected $smallImageAttribute;

    /** @var string */
    protected $baseImageAttribute;

    /** @var string */
    protected $thumbnailAttribute;

    /** @var AttributeManager $attributeManager */
    protected $attributeManager;

    /** @var AssociationTypeManager $associationTypeManager */
    protected $associationTypeManager;

    /**
     * {@inheritDoc}
     * @param AttributeManager       $attributeManager
     * @param AssociationTypeManager $associationTypeManager
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        NormalizerGuesser $normalizerGuesser,
        LocaleManager $localeManager,
        MagentoMappingMerger $storeViewMappingMerger,
        MagentoSoapClientParametersRegistry $clientParametersRegistry,
        AttributeManager $attributeManager,
        AssociationTypeManager $associationTypeManager
    ) {
        parent::__construct(
            $webserviceGuesser,
            $normalizerGuesser,
            $localeManager,
            $storeViewMappingMerger,
            $clientParametersRegistry
        );

        $this->attributeManager       = $attributeManager;
        $this->associationTypeManager = $associationTypeManager;
    }

    /**
     * Function called before all process
     */
    protected function beforeExecute()
    {
        parent::beforeExecute();

        $this->globalContext['pimGrouped']          = $this->pimGrouped;
        $this->globalContext['smallImageAttribute'] = $this->smallImageAttribute;
        $this->globalContext['baseImageAttribute']  = $this->baseImageAttribute;
        $this->globalContext['thumbnailAttribute']  = $this->thumbnailAttribute;
        $this->globalContext['defaultStoreView']    = $this->getDefaultStoreView();
    }

    /**
     * Normalize the given product
     *
     * @param ProductInterface $product
     * @param array            $context
     *
     * @throws InvalidItemException If a normalization error occurs
     *
     * @return array                processed item
     */
    protected function normalizeProduct(ProductInterface $product, $context)
    {
        try {
            $processedItem = $this->normalizerGuesser->normalize(
                $product,
                AbstractNormalizer::MAGENTO_FORMAT,
                $context
            );
        } catch (NormalizeException $e) {
            throw new InvalidItemException(
                $e->getMessage(),
                [
                    'id'                                                 => $product->getId(),
                    $product->getIdentifier()->getAttribute()->getCode() => $product->getIdentifier()->getData(),
                    'label'                                              => $product->getLabel(),
                    'family'                                             => $product->getFamily()->getCode()
                ]
            );
        }

        return $processedItem;
    }

    /**
     * {@inheritDoc}
     */
    public function process($item)
    {
        $item = is_array($item) ? $item : [$item];

        $this->beforeExecute();

        $processedItems = [];

//        $magentoProducts = $this->webservice->getProductsStatus($item);


        printf('ITEM');
        die(var_dump($item));
    }

    /**
     * Get pim grouped
     * @return string
     */
    public function getPimGrouped()
    {
        return $this->pimGrouped;
    }

    /**
     * Set pim grouped
     * @param string $pimGrouped
     *
     * @return ProductProcessor
     */
    public function setPimGrouped($pimGrouped)
    {
        $this->pimGrouped = $pimGrouped;

        return $this;
    }

    /**
     * Get small image
     * @return string
     */
    public function getSmallImageAttribute()
    {
        return $this->smallImageAttribute;
    }

    /**
     * Set small image
     * @param string $smallImageAttribute
     *
     * @return ProductProcessor
     */
    public function setSmallImageAttribute($smallImageAttribute)
    {
        $this->smallImageAttribute = $smallImageAttribute;

        return $this;
    }

    /**
     * Get base image attribute
     * @return string
     */
    public function getBaseImageAttribute()
    {
        return $this->baseImageAttribute;
    }

    /**
     * Set base image attribute
     * @param string $baseImageAttribute
     *
     * @return ProductProcessor
     */
    public function setBaseImageAttribute($baseImageAttribute)
    {
        $this->baseImageAttribute = $baseImageAttribute;

        return $this;
    }

    /**
     * Get thumbnail attribute
     * @return string
     */
    public function getThumbnailAttribute()
    {
        return $this->thumbnailAttribute;
    }

    /**
     * Set thumbnail attribute
     * @param string $thumbnailAttribute
     *
     * @return ProductProcessor
     */
    public function setThumbnailAttribute($thumbnailAttribute)
    {
        $this->thumbnailAttribute = $thumbnailAttribute;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return array_merge(
            parent::getConfigurationFields(),
            [
                'smallImageAttribute' => [
                    'type' => 'choice',
                    'options' => [
                        'choices' => $this->attributeManager->getImageAttributeChoice(),
                        'help'    => 'pim_magento_connector.export.smallImageAttribute.help',
                        'label'   => 'pim_magento_connector.export.smallImageAttribute.label',
                        'attr' => [
                            'class' => 'select2'
                        ]
                    ]
                ],
                'baseImageAttribute' => [
                    'type' => 'choice',
                    'options' => [
                        'choices' => $this->attributeManager->getImageAttributeChoice(),
                        'help'    => 'pim_magento_connector.export.baseImageAttribute.help',
                        'label'   => 'pim_magento_connector.export.baseImageAttribute.label',
                        'attr' => [
                            'class' => 'select2'
                        ]
                    ]
                ],
                'thumbnailAttribute' => [
                    'type' => 'choice',
                    'options' => [
                        'choices' => $this->attributeManager->getImageAttributeChoice(),
                        'help'    => 'pim_magento_connector.export.thumbnailAttribute.help',
                        'label'   => 'pim_magento_connector.export.thumbnailAttribute.label',
                        'attr' => [
                            'class' => 'select2'
                        ]
                    ]
                ],
                'pimGrouped' => [
                    'type'    => 'choice',
                    'options' => [
                        'choices' => $this->associationTypeManager->getAssociationTypeChoices(),
                        'help'    => 'pim_magento_connector.export.pimGrouped.help',
                        'label'   => 'pim_magento_connector.export.pimGrouped.label',
                        'attr' => [
                            'class' => 'select2'
                        ]
                    ]
                ],
            ]
        );
    }
}