<?php

namespace Pim\Bundle\MagentoConnectorBundle\Normalizer;

use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\ConnectorMappingBundle\Mapper\MappingCollection;
use Symfony\Component\Serializer\Normalizer\scalar;

/**
 * A normalizer to transform a product entity into an array
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductMagentoCsvNormalizer extends AbstractNormalizer implements ProductNormalizerInterface
{
    /**
     * Normalizes an object into a set of arrays/scalars
     *
     * @param object $object  object to normalize
     * @param string $format  format the normalization result will be encoded as
     * @param array  $context Context options for the normalizer
     *
     * @return array|scalar
     */
    public function normalize($object, $format = null, array $context = array())
    {
        // TODO: Implement normalize() method.
    }

    /**
     * Get values array for a given product
     *
     * @param ProductInterface  $product                  The given product
     * @param array             $magentoAttributes        Attribute list from Magento
     * @param array             $magentoAttributesOptions Attribute options list from Magento
     * @param string            $localeCode               The locale to apply
     * @param string            $scopeCode                The akeno scope
     * @param MappingCollection $categoryMapping          Root category mapping
     * @param MappingCollection $attributeMapping         Attribute mapping
     * @param boolean           $onlyLocalized            If true, only get translatable attributes
     *
     * @return array Computed data
     */
    public function getValues(
        ProductInterface $product,
        $magentoAttributes,
        $magentoAttributesOptions,
        $localeCode,
        $scopeCode,
        MappingCollection $categoryMapping,
        MappingCollection $attributeMapping,
        $onlyLocalized
    )
    {
        // TODO: Implement getValues() method.
    }

    /**
     * Get all images of a product normalized
     *
     * @param ProductInterface $product
     *
     * @return array
     */
    public function getNormalizedImages(ProductInterface $product)
    {
        // TODO: Implement getNormalizedImages() method.
    }

} 