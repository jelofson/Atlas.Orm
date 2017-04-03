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
        $atlasContainer = new ForumAtlasContainer('sqlite::memory:');
        $this->mapperLocator = $atlasContainer->getMapperLocator();
        $connection = $atlasContainer->getConnectionLocator()->getDefault();
        $fixture = new SqliteFixture($connection);
        $fixture->exec();
    }
}
