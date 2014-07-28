<?php

namespace Pim\Bundle\MagentoConnectorBundle\Writer;

use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;

/**
 * Product to Api Import writer
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductToApiImportWriter extends AbstractWriter
{
    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param MagentoSoapClientParametersRegistry $clientParametersRegistry
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        MagentoSoapClientParametersRegistry $clientParametersRegistry
    ) {
        parent::__construct($webserviceGuesser, $clientParametersRegistry);
    }

    /**
     * {@inheritDoc}
     */
    public function write(array $items)
    {
        printf('WRITER');
        die(var_dump($items));
    }

} 