<?php
namespace Atlas;

use Atlas\Mapper\MapperFactory;
use Atlas\Mapper\MapperLocator;
use Atlas\Mapper\MapperRelations;
use Atlas\Table\TableFactory;
use Atlas\Table\TableLocator;
use Aura\Sql\ConnectionLocator;
use Aura\SqlQuery\QueryFactory;

class AtlasContainer
{
    protected $atlas;
    protected $connectionLocator;
    protected $factories;
    protected $mapperLocator;
    protected $queryFactory;
    protected $tableLocator;

    public function __construct($db, $common = null)
    {
        $this->queryFactory = new QueryFactory($db, $common);
        $this->connectionLocator = new ConnectionLocator();
        $this->tableLocator = new TableLocator();
        $this->mapperLocator = new MapperLocator();
        $this->atlas = new Atlas($this->mapperLocator);
    }

    public function getAtlas()
    {
        return $this->atlas;
    }

    public function getConnectionLocator()
    {
        return $this->connectionLocator;
    }

    public function getQueryFactory()
    {
        return $this->queryFactory;
    }

    public function getMapperLocator()
    {
        return $this->mapperLocator;
    }

    public function getTable($tableClass)
    {
        return $this->tableLocator->get($tableClass);
    }

    public function setDefaultConnection(callable $callable)
    {
        $this->connectionLocator->setDefault($callable);
    }

    public function setReadConnection($name, callable $callable)
    {
        $this->connectionLocator->setRead($name, $callable);
    }

    public function setWriteConnection($name, callable $callable)
    {
        $this->connectionLocator->setWrite($name, $callable);
    }

    public function setMapper($mapperClass, $tableClass = null)
    {
        if (! class_exists($mapperClass)) {
            throw new Exception("$mapperClass does not exist");
        }

        if (! $tableClass) {
            // Foo\Bar\BazMapper => Foo\Bar\BazTable
            $tableClass = substr($mapperClass, 0, -6) . 'Table';
        }

        if (! class_exists($tableClass)) {
            throw new Exception("$tableClass does not exist");
        }

        if (! $this->tableLocator->has($tableClass)) {
            $this->tableLocator->set($tableClass, $this->newTableFactory($tableClass));
        }

        $mapperFactory = $this->newMapperFactory($mapperClass, $tableClass);
        $this->mapperLocator->set($mapperClass, $mapperFactory);
    }

    public function setMappers(array $mapperClasses)
    {
        foreach ($mapperClasses as $key => $val) {
            if (is_int($key)) {
                $this->setMapper($val);
            } else {
                $this->setMapper($key, $val);
            }
        }
    }

    public function setFactoryFor($class, callable $callable)
    {
        $this->factories[$class] = $callable;
    }

    public function newInstance($class)
    {
        if (isset($this->factories[$class])) {
            $factory = $this->factories[$class];
            return $factory();
        }

        return new $class();
    }

    public function newMapperFactory($mapperClass, $tableClass)
    {
        return new MapperFactory($this, $mapperClass, $tableClass);
    }

    public function newTableFactory($tableClass)
    {
        return new TableFactory($this, $tableClass);
    }

    public function newMapperRelations($mapperClass)
    {
        return new MapperRelations($mapperClass, $this->mapperLocator);
    }
}
