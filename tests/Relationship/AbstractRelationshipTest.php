<?php
namespace Atlas\Orm\Relationship;

use Atlas\Orm\DataSource\ForumAtlasContainer;
use Atlas\Orm\Mapper\MapperLocator;
use Aura\Sql\ExtendedPdo;
use Atlas\Orm\SqliteFixture;

abstract class AbstractRelationshipTest extends \PHPUnit\Framework\TestCase
{
    protected $mapperLocator;

    protected function setUp()
    {
        $connection = new ExtendedPdo('sqlite::memory:');
        $fixture = new SqliteFixture($connection);
        $fixture->exec();

        $atlasContainer = new ForumAtlasContainer($connection);
        $this->mapperLocator = $atlasContainer->getMapperLocator();
    }
}
