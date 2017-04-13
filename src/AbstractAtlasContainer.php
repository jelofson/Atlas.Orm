<?php
/**
 *
 * This file is part of Atlas for PHP.
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 */
namespace Atlas\Orm;

use Atlas\Orm\Mapper\MapperEvents;
use Atlas\Orm\Mapper\MapperLocator;
use Atlas\Orm\Relationship\Relationships;
use Atlas\Orm\Table\IdentityMap;
use Atlas\Orm\Table\TableEvents;
use Atlas\Orm\Table\TableLocator;
use Aura\Sql\ConnectionLocator;
use Aura\Sql\ExtendedPdo;
use Aura\SqlQuery\QueryFactory;
use Capsule\Di\Container;
use PDO;

/**
 *
 * A container for setting up Atlas.
 *
 * @package atlas/orm
 *
 */
abstract class AbstractAtlasContainer extends Container
{
    /**
     *
     * Constructor.
     *
     * @param mixed $dsn A specifier for a default database connection. This
     * can be a PDO or ExtendedPdo instance, in which case all remaining params
     * are ignored. This can also be a DSN connection string. Finally, if it is
     * null, the default connection values are pulled from the environment keys
     * `ATLAS_PDO_(DSN|USERNAME|PASSWORD|OPTIONS|ATTRIBUTES)`.
     *
     * @param string $username The default database connection username.
     *
     * @param string $password The default database connection password.
     *
     * @param array $options The default database connection options.
     *
     * @param array $attributes The default post-connection attributes.
     *
     * @see ExtendedPdo::__construct()
     *
     */
    public function __construct(
        $dsn = null,
        $username = null,
        $password = null,
        array $options = null,
        array $attributes = null
    ) {
        if ($dsn !== null) {
            $this->setEnv([
                'ATLAS_PDO_DSN' => $dsn,
                'ATLAS_PDO_USERNAME' => $username,
                'ATLAS_PDO_PASSWORD' => $password,
                'ATLAS_PDO_OPTIONS' => $options,
                'ATLAS_PDO_ATTRIBUTES' => $attributes,
            ]);
        }

        /* provided services */
        $this->provide(ConnectionLocator::CLASS)
            ->args($this->getDefaultConnection());

        $this->provide(QueryFactory::CLASS)
            ->args($this->getPdoDriver());

        $this->provide(TableLocator::CLASS);

        $this->provide(MapperLocator::CLASS);

        $this->provide(Atlas::CLASS)
            ->args(
                $this->service(MapperLocator::CLASS),
                $this->new(Transaction::CLASS)
            );

        /* default configurations */
        $this->default(Transaction::CLASS)
            ->args(
                $this->service(MapperLocator::CLASS)
            );

        $this->default(Relationships::CLASS)
            ->args(
                $this->service(MapperLocator::CLASS)
            );

        $this->init();
    }

    public function getMapperLocator() : MapperLocator
    {
        return $this->serviceInstance(MapperLocator::CLASS);
    }

    public function getAtlas() : Atlas
    {
        return $this->serviceInstance(Atlas::CLASS);
    }

    /**
     *
     * To set custom creation for, say, Events classes, extend init, and then:
     *
     * $this->default(WhateverMapper\WhateverEvents::CLASS)->args(...);
     *
     * Should we go so far as to make setMapper/s() internal-only as well?
     *
     */
    abstract protected function init();

    protected function setMappers(...$mapperClasses)
    {
        foreach ($mapperClasses as $mapperClass) {
            $this->setMapper($mapperClass);
        }
    }

    protected function setMapper($mapperClass)
    {
        if (! class_exists($mapperClass)) {
            throw Exception::classDoesNotExist($mapperClass);
        }

        $tableClass = $mapperClass::getTableClass();
        $this->setTable($tableClass);

        $eventsClass = $mapperClass . 'Events';
        if (! class_exists($eventsClass)) {
            $eventsClass = MapperEvents::CLASS;
        }

        $this->default(MapperLocator::CLASS)->call(
            'set',
            $mapperClass,
            $this->closure(
                'newInstance',
                $mapperClass,
                $this->serviceCall(TableLocator::CLASS, 'get', $tableClass),
                $this->new(Relationships::CLASS),
                $this->new($eventsClass)
            )
        );
    }

    protected function getPdoDriver()
    {
        $spec = $this->env('ATLAS_PDO_DSN');
        if ($spec instanceof PDO) {
            return $spec->getAttribute(PDO::ATTR_DRIVER_NAME);
        }
        $parts = explode(':', $spec);
        return array_shift($parts);
    }

    protected function getDefaultConnection()
    {
        $spec = $this->env('ATLAS_PDO_DSN');

        if ($spec instanceof ExtendedPdo) {
            return function () use ($spec) { return $spec; };
        }

        if ($spec instanceof PDO) {
            return $this->closure(
                'newInstance',
                ExtendedPdo::CLASS,
                $spec
            );
        }

        return $this->closure(
            'newInstance',
            ExtendedPdo::CLASS,
            $this->env('ATLAS_PDO_DSN'),
            $this->env('ATLAS_PDO_USERNAME'),
            $this->env('ATLAS_PDO_PASSWORD'),
            $this->env('ATLAS_PDO_OPTIONS') ?? [],
            $this->env('ATLAS_PDO_ATTRIBUTES') ?? []
        );
    }

    protected function setTable($tableClass)
    {
        if (! class_exists($tableClass)) {
            throw Exception::classDoesNotExist($tableClass);
        }

        $eventsClass = $tableClass . 'Events';
        if (! class_exists($eventsClass)) {
            $eventsClass = TableEvents::CLASS;
        }

        $this->default(TableLocator::CLASS)->call(
            'set',
            $tableClass,
            $this->closure(
                'newInstance',
                $tableClass,
                $this->service(ConnectionLocator::CLASS),
                $this->service(QueryFactory::CLASS),
                $this->new(IdentityMap::CLASS),
                $this->new($eventsClass)
            )
        );
    }
}
