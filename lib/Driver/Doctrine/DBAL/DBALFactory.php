<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine\DBAL;

use Doctrine\Common\Persistence\ConnectionRegistry;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Extension\Core\EventSubscriber\ResultIndexer;
use FSi\Component\DataSource\Driver\DriverFactoryInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DBALFactory implements DriverFactoryInterface
{
    /**
     * @var ConnectionRegistry
     */
    private $registry;

    /**
     * Array of extensions.
     *
     * @var array
     */
    private $extensions;

    /**
     * @var OptionsResolver
     */
    private $optionsResolver;

    public function __construct(ConnectionRegistry $registry, $extensions = array())
    {
        $this->registry = $registry;
        $this->extensions = $extensions;
        $this->optionsResolver = new OptionsResolver();
        $this->initOptions();
    }

    public function getDriverType()
    {
        return 'doctrine-dbal';
    }

    public function createDriver($options = array())
    {
        $options = $this->optionsResolver->resolve($options);

        if (empty($options['connection'])) {
            $connection = $this->registry->getConnection($this->registry->getDefaultConnectionName());
        } else {
            $connection = $this->registry->getConnection($options['connection']);
        }

        $table = isset($options['table']) ? $options['table'] : $options['qb'];

        return new DBALDriver($this->extensions, $connection, $table, $options['alias'], $options['indexField']);
    }

    private function initOptions()
    {
        $this->optionsResolver->setDefaults(array(
            'qb' => null,
            'table' => null,
            'alias' => null,
            'connection' => null,
            'indexField' => null,
        ));

        $this->optionsResolver->setAllowedTypes('qb', array('\Doctrine\DBAL\Query\QueryBuilder', 'null'));
        $this->optionsResolver->setAllowedTypes('table', array('string', 'null'));
        $this->optionsResolver->setAllowedTypes('alias', array('null', 'string'));
        $this->optionsResolver->setAllowedTypes('connection', array('null', 'string'));
        $this->optionsResolver->setAllowedTypes('indexField', array('null', 'string', '\Closure'));

        $tableNormalizer = function(Options $options, $value) {
            if (is_null($options['qb']) && is_null($value)) {
                throw new InvalidOptionsException('You must specify at least one option, "qb" or "table".');
            }

            return $value;
        };

        $this->optionsResolver->setNormalizer('table', $tableNormalizer);
    }
}
