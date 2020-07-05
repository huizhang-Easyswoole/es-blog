<?php
namespace Library\Model;

use EasySwoole\ORM\AbstractModel;

class ArticleInfoModel extends AbstractModel
{

    protected $tableName = 'article_info';

    public static function test()
    {
        self::create()->get([]);
    }

}