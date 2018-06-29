<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Collection;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use FSi\Component\DataSource\Driver\DriverFactoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Traversable;

/**
 * {@inheritdoc}
 */
class CollectionFactory implements DriverFactoryInterface
{
    /**
     * Array of extensions.
     *
     * @var array
     */
    private $extensions;

    /**
     * @var \Symfony\Component\OptionsResolver\OptionsResolver
     */
    private $optionsResolver;

    /**
     * @param array $extensions
     */
    public function __construct($extensions = [])
    {
        $this->extensions = $extensions;
        $this->optionsResolver = new OptionsResolver();
        $this->initOptions();
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverType()
    {
        return 'collection';
    }

    /**
     * Creates driver.
     *
     * @param array $options
     * @return \FSi\Component\DataSource\Driver\Collection\CollectionDriver
     */
    public function createDriver($options = [])
    {
        $options = $this->optionsResolver->resolve($options);

        return new CollectionDriver($this->extensions, $options['collection'], $options['criteria']);
    }

    /**
     * Initialize Options Resolvers for driver and datasource builder.
     */
    private function initOptions()
    {
        $this->optionsResolver->setDefaults([
            'criteria' => null,
            'collection' => [],
        ]);

        $this->optionsResolver->setAllowedTypes('collection', [
            'array',
            Traversable::class,
            Selectable::class
        ]);

        $this->optionsResolver->setAllowedTypes('criteria', ['null', Criteria::class]);
    }
}
