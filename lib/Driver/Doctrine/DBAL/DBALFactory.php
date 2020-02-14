<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine\DBAL;

use Closure;
use Doctrine\Common\Persistence\ConnectionRegistry;
use Doctrine\DBAL\Query\QueryBuilder;
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
     * @var array
     */
    private $extensions;

    /**
     * @var OptionsResolver
     */
    private $optionsResolver;

    public function __construct(ConnectionRegistry $registry, $extensions = [])
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

    public function createDriver($options = [])
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
        $this->optionsResolver->setDefaults([
            'qb' => null,
            'table' => null,
            'alias' => null,
            'connection' => null,
            'indexField' => null,
        ]);

        $this->optionsResolver->setAllowedTypes('qb', [QueryBuilder::class, 'null']);
        $this->optionsResolver->setAllowedTypes('table', ['string', 'null']);
        $this->optionsResolver->setAllowedTypes('alias', ['null', 'string']);
        $this->optionsResolver->setAllowedTypes('connection', ['null', 'string']);
        $this->optionsResolver->setAllowedTypes('indexField', ['null', 'string', Closure::class]);

        $tableNormalizer = function (Options $options, $value) {
            if (is_null($options['qb']) && is_null($value)) {
                throw new InvalidOptionsException('You must specify at least one option, "qb" or "table".');
            }

            return $value;
        };

        $this->optionsResolver->setNormalizer('table', $tableNormalizer);
    }
}
