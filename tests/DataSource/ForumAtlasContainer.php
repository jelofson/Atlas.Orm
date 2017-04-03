<?php
namespace Atlas\Orm\DataSource;

use Atlas\Orm\AbstractAtlasContainer;

class ForumAtlasContainer extends AbstractAtlasContainer
{
    protected function init()
    {
        parent::init();
        $this->setMappers(
            Author\AuthorMapper::CLASS,
            Reply\ReplyMapper::CLASS,
            Summary\SummaryMapper::CLASS,
            Tag\TagMapper::CLASS,
            Thread\ThreadMapper::CLASS,
            Tagging\TaggingMapper::CLASS
        );
    }
}
