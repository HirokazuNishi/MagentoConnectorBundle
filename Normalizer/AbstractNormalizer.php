<?php

namespace Pim\Bundle\MagentoConnectorBundle\Normalizer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\Exception\LocaleNotMatchedException;
use Pim\Bundle\ConnectorMappingBundle\Mapper\MappingCollection;
use Pim\Bundle\CatalogBundle\Entity\Locale;

/**
 * A normalizer to transform a product entity into an array
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
abstract class AbstractNormalizer implements NormalizerInterface
{
    const MAGENTO_SIMPLE_PRODUCT_KEY       = 'simple';
    const MAGENTO_CONFIGURABLE_PRODUCT_KEY = 'configurable';
    const MAGENTO_GROUPED_PRODUCT_KEY      = 'grouped';
    const DATE_FORMAT                      = 'Y-m-d H:i:s';

    const MAGENTO_FORMAT     = 'MagentoArray';
    const MAGENTO_CSV_FORMAT = 'MagentoCsv';

    /**
     * @var array
     */
    protected $pimLocales;

    /**
     * @var array
     */
    protected $supportedFormats = [self::MAGENTO_FORMAT, self::MAGENTO_CSV_FORMAT];

    /**
     * @var ChannelManager
     */
    protected $channelManager;

    /**
     * Constructor
     * @param ChannelManager $channelManager
     */
    public function __construct(ChannelManager $channelManager)
    {
        $this->channelManager = $channelManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return in_array($format, $this->supportedFormats);
    }

    /**
     * Get all Pim locales for the given channel
     * @param string $channel
     *
     * @return array The locales
     */
    protected function getPimLocales($channel)
    {
        if (!$this->pimLocales) {
            $this->pimLocales = $this->channelManager
                ->getChannelByCode($channel)
                ->getLocales();
        }

        return $this->pimLocales;
    }

    /**
     * Get the corresponding storeview for a given locale
     * @param string            $locale
     * @param array             $magentoStoreViews
     * @param MappingCollection $storeViewMapping
     *
     * @return string
     */
    protected function getStoreViewForLocale($locale, $magentoStoreViews, MappingCollection $storeViewMapping)
    {
        return $this->getStoreView($storeViewMapping->getTarget($locale), $magentoStoreViews);
    }

    /**
     * Get the storeview for the given code
     * @param string $code
     * @param array  $magentoStoreViews
     *
     * @return null|string
     */
    protected function getStoreView($code, $magentoStoreViews)
    {
        foreach ($magentoStoreViews as $magentoStoreView) {
            if ($magentoStoreView['code'] === strtolower($code)) {
                return $magentoStoreView;
            }
        }
    }

    /**
     * Manage not found locales
     * @param Locale $locale
     *
     * @throws LocaleNotMatchedException
     */
    protected function localeNotFound(Locale $locale)
    {
        throw new LocaleNotMatchedException(
            sprintf(
                'No storeview found for "%s" locale. Please create a storeview named "%s" on your Magento or map ' .
                'this locale to a storeview code. You can also disable this locale in your channel\'s settings if you '.
                'don\'t want to export it.',
                $locale->getCode(),
                $locale->getCode()
            )
        );
    }
}
