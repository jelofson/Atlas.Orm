<?php
namespace Atlas\Orm;

use Atlas\Orm\DataSource\Author\AuthorMapper;
use Atlas\Orm\DataSource\ForumAtlasContainer;
use Atlas\Orm\DataSource\Reply\ReplyMapper;
use Atlas\Orm\DataSource\Summary\SummaryMapper;
use Atlas\Orm\DataSource\Tag\TagMapper;
use Atlas\Orm\DataSource\Tagging\TaggingMapper;
use Atlas\Orm\DataSource\Thread\ThreadMapper;
use Atlas\Orm\Exception;

use Aura\Sql\ExtendedPdo;

class AtlasContainerTest extends \PHPUnit\Framework\TestCase
{
    protected $atlasContainer;

    protected function setUp()
    {
        $this->atlasContainer = new ForumAtlasContainer('sqlite::memory:');
    }

    public function test()
    {
        // get the atlas
        $atlas = $this->atlasContainer->getAtlas();

        // check that the mappers instantiated
        $this->assertInstanceOf(AuthorMapper::CLASS, $atlas->mapper(AuthorMapper::CLASS));
        $this->assertInstanceOf(ReplyMapper::CLASS, $atlas->mapper(ReplyMapper::CLASS));
        $this->assertInstanceOf(SummaryMapper::CLASS, $atlas->mapper(SummaryMapper::CLASS));
        $this->assertInstanceOf(TagMapper::CLASS, $atlas->mapper(TagMapper::CLASS));
        $this->assertInstanceOf(ThreadMapper::CLASS, $atlas->mapper(ThreadMapper::CLASS));
        $this->assertInstanceOf(TaggingMapper::CLASS, $atlas->mapper(TaggingMapper::CLASS));
    }

    public function testSetMapper_noSuchMapper()
    {
        $this->markTestIncomplete('need to test setting missing mappers');

        $this->expectException(
            Exception::CLASS,
            'FooMapper does not exist'
        );
        $this->atlasContainer->setMapper('FooMapper');
    }
}
