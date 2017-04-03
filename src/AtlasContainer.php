<?php
/**
 *
 * This file is part of Atlas for PHP.
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 */
namespace Atlas\Orm;

use Atlas\Orm\Mapper\MapperLocator;
use Atlas\Orm\Mapper\MapperEvents;
use Atlas\Orm\Relationship\Relationships;
use Atlas\Orm\Table\TableEvents;
use Atlas\Orm\Table\TableLocator;
use Atlas\Orm\Table\IdentityMap;
use Aura\Sql\ConnectionLocator;
use Aura\Sql\ExtendedPdo;
use Aura\SqlQuery\QueryFactory;
use Capsule\Di\AbstractContainer;

/**
 *
 * A container for setting up Atlas.
 *
 * @package atlas/orm
 *
 */
class AtlasContainer extends AbstractContainer
{
    /**
     *
     * Constructor.
     *
     * @param ExtendedPdo|PDO|$dsn The data source name for a default
     * Lazy PDO connection, or an existing database connection. If the latter,
     * the remaining params are ignored.
     *
     * @param $username The default database connection username.
     *
     * @param $password The default database connection password.
     *
     * @param array $options The default database connection options.
     *
     * @param array $attributes The default database connection attributes.
     *
     * @see ExtendedPdo::__construct()
     *
     */
    public function __construct(
        $dsn = null,
        $username = null,
        $password = null,
        array $options = [],
        array $attributes = []
    ) {
        parent::__construct([
            'ATLAS_PDO_DSN' => $dsn,
            'ATLAS_PDO_USERNAME' => $username,
            'ATLAS_PDO_PASSWORD' => $password,
            'ATLAS_PDO_OPTIONS' => $options,
            'ATLAS_PDO_ATTRIBUTES' => $attributes,
        ]);
    }

    public function getAtlas() : Atlas
    {
        return $this->serviceInstance(Atlas::CLASS);
    }

    public function getTableLocator() : TableLocator
    {
        return $this->serviceInstance(TableLocator::CLASS);
    }

    public function getMapperLocator() : MapperLocator
    {
        return $this->serviceInstance(MapperLocator::CLASS);
    }

    public function getConnectionLocator() : ConnectionLocator
    {
        return $this->serviceInstance(ConnectionLocator::CLASS);
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
    protected function init()
    {
        parent::init();

        /* provided services */
        $this->provide(Atlas::CLASS)
            ->args(
                $this->service(MapperLocator::CLASS),
                $this->create(Transaction::CLASS)
            );

        $this->provide(ConnectionLocator::CLASS)
            ->args($this->getDefaultConnection());

        $this->provide(TableLocator::CLASS);

        $this->provide(MapperLocator::CLASS);

        $this->provide(QueryFactory::CLASS)
            ->args($this->getPdoDriver());

        /* default configurations */
        $this->default(Transaction::CLASS)
            ->args(
                $this->service(MapperLocator::CLASS)
            );

        $this->default(Relationships::CLASS)
            ->args(
                $this->service(MapperLocator::CLASS)
            );
    }

    public function setMappers(array $mapperClasses)
    {
        foreach ($mapperClasses as $mapperClass) {
            $this->setMapper($mapperClass);
        }
    }

    public function setMapper($mapperClass)
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

        $create = $this->create($mapperClass)->args(
                $this->lazy([$this->getTableLocator(), 'get'], $tableClass),
                $this->create(Relationships::CLASS),
                $this->create($eventsClass)
            );

        $this->getMapperLocator()->set($mapperClass, $create);
    }

    protected function getPdoDriver()
    {
        $spec = $this->env('ATLAS_PDO_DSN');
        if ($spec instanceof PDO) {
            return $pdo->getAttribute(ExtendedPdo::ATTR_DRIVER_NAME);
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

        $self = $this;
        if ($spec instanceof PDO) {
            return function () use ($self) {
                return $self->createInstance(ExtendedPdo::CLASS, [$spec]);
            };
        }

        return function () use ($self) {
            return $self->createInstance(ExtendedPdo::CLASS, [
                $self->env('ATLAS_PDO_DSN'),
                $self->env('ATLAS_PDO_USERNAME'),
                $self->env('ATLAS_PDO_PASSWORD'),
                $self->env('ATLAS_PDO_OPTIONS'),
                $self->env('ATLAS_PDO_ATTRIBUTES')
            ]);
        };
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

        $this->getTableLocator()->set(
            $tableClass,
            $this->create($tableClass)->args(
                $this->service(ConnectionLocator::CLASS),
                $this->service(QueryFactory::CLASS),
                $this->create(IdentityMap::CLASS),
                $this->create($eventsClass)
            )
        );
    }
}
