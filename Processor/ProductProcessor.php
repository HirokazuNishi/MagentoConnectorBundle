<?php

namespace Pim\Bundle\MagentoConnectorBundle\Processor;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\MagentoConnectorBundle\Guesser\NormalizerGuesser;
use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Manager\AssociationTypeManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\AttributeManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\CurrencyManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\LocaleManager;
use Pim\Bundle\MagentoConnectorBundle\Merger\MagentoMappingMerger;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\AbstractNormalizer;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\Exception\NormalizeException;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;
use Pim\Bundle\TransformBundle\Converter\MetricConverter;

/**
 * Magento product processor
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductProcessor extends AbstractProductProcessor
{
    /**
     * @var metricConverter
     */
    protected $metricConverter;

    /**
     * @var AssociationTypeManager
     */
    protected $associationTypeManager;

    /**
     * @var string
     */
    protected $pimGrouped;

    /**
     * @var string
     */
    protected $smallImageAttribute;

    /**
     * @var string
     */
    protected $baseImageAttribute;

    /**
     * @var string
     */
    protected $thumbnailAttribute;

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param NormalizerGuesser                   $normalizerGuesser
     * @param LocaleManager                       $localeManager
     * @param MagentoMappingMerger                $storeViewMappingMerger
     * @param CurrencyManager                     $currencyManager
     * @param ChannelManager                      $channelManager
     * @param MagentoMappingMerger                $categoryMappingMerger
     * @param MagentoMappingMerger                $attributeMappingMerger
     * @param MetricConverter                     $metricConverter
     * @param AssociationTypeManager              $associationTypeManager
     * @param MagentoSoapClientParametersRegistry $clientParametersRegistry
     * @param AttributeManager                    $attributeManager
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        NormalizerGuesser $normalizerGuesser,
        LocaleManager $localeManager,
        MagentoMappingMerger $storeViewMappingMerger,
        CurrencyManager $currencyManager,
        ChannelManager $channelManager,
        MagentoMappingMerger $categoryMappingMerger,
        MagentoMappingMerger $attributeMappingMerger,
        MetricConverter $metricConverter,
        AssociationTypeManager $associationTypeManager,
        MagentoSoapClientParametersRegistry $clientParametersRegistry,
        AttributeManager $attributeManager
    ) {
        parent::__construct(
            $webserviceGuesser,
            $normalizerGuesser,
            $localeManager,
            $storeViewMappingMerger,
            $currencyManager,
            $channelManager,
            $categoryMappingMerger,
            $attributeMappingMerger,
            $clientParametersRegistry
        );

        $this->metricConverter        = $metricConverter;
        $this->associationTypeManager = $associationTypeManager;
        $this->attributeManager       = $attributeManager;
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
     * {@inheritdoc}
     */
    public function process($items)
    {
        $items = is_array($items) ? $items : [$items];

        $this->beforeExecute();

        $processedItems = [];

        $magentoProducts = $this->webservice->getProductsStatus($items);

        $channel = $this->channelManager->getChannelByCode($this->channel);

        foreach ($items as $product) {
            $context = array_merge(
                $this->globalContext,
                ['attributeSetId' => $this->getAttributeSetId($product->getFamily()->getCode(), $product)]
            );

            if ($this->magentoProductExists($product, $magentoProducts)) {
                if ($this->attributeSetChanged($product, $magentoProducts)) {
                    throw new InvalidItemException(
                        'The product family has changed of this product. This modification cannot be applied to ' .
                        'magento. In order to change the family of this product, please manualy delete this product ' .
                        'in magento and re-run this connector.',
                        [
                            'id'                                                 => $product->getId(),
                            $product->getIdentifier()->getAttribute()->getCode() => $product->getIdentifier()->getData(),
                            'family'                                             => $product->getFamily()->getCode()
                        ]
                    );
                }

                $context['create'] = false;
            } else {
                $context['create'] = true;
            }

            $this->metricConverter->convert($product, $channel);

            $processedItems[] = $this->normalizeProduct($product, $context);
        }

        return $processedItems;
    }

    /**
     * Normalize the given product
     *
     * @param ProductInterface $product [description]
     * @param array            $context The context
     *
     * @throws InvalidItemException If a normalization error occurs
     *
     * @return array                processed item
     */
    protected function normalizeProduct(ProductInterface $product, $context)
    {
        try {
            $processedItem = $this->productNormalizer->normalize(
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
     * Test if a product already exists on magento platform
     *
     * @param ProductInterface $product         The product
     * @param array            $magentoProducts Magento products
     *
     * @return bool
     */
    protected function magentoProductExists(ProductInterface $product, $magentoProducts)
    {
        foreach ($magentoProducts as $magentoProduct) {
            if ($magentoProduct['sku'] == $product->getIdentifier()->getData()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Test if the product attribute set changed
     *
     * @param ProductInterface $product         The product
     * @param array            $magentoProducts Magento products
     *
     * @return bool
     */
    protected function attributeSetChanged(ProductInterface $product, $magentoProducts)
    {
        foreach ($magentoProducts as $magentoProduct) {
            if ($magentoProduct['sku'] == $product->getIdentifier()->getData() &&
                $magentoProduct['set'] != $this->getAttributeSetId($product->getFamily()->getCode(), $product)
            ) {
                return true;
            }
        }

        return false;
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
