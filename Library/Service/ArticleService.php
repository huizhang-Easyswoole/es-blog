<?php
namespace Library\Service;

use EasySwoole\Component\Singleton;
use Library\Comm\IniConfig;
use Parsedown;
use Library\Model\ArticleInfoModel;
use Library\Model\MenusModel;

class ArticleService
{
    use Singleton;

    public function defaultArticle()
    {
        $res = ArticleInfoModel::create()->order('id', 'desc')->limit(10)->all();
        $articleList = [];
        foreach ($res as $item)
        {
            $item = $item->toArray();
            $articleList[] = $item;
        }

        return $articleList;
    }

    public function menes()
    {
        $res = MenusModel::create()->all();
        $menus = [];
        foreach ($res as $item)
        {
            $menus[] = $item->toArray();
        }
        return $menus;
    }

    public function articleClass(int $page=1, int $pageSize=10, string $menuName)
    {

        $articleInfoModel = ArticleInfoModel::create()
            ->where('menu_name', $menuName)
            ->order('id', 'desc')
            ->limit($pageSize * ($page - 1), $pageSize)
            ->withTotalCount();
        $res = $articleInfoModel->all();

        $total = $articleInfoModel->lastQueryResult()->getTotalCount();

        $articleList = [];
        foreach ($res as $item)
        {
            $item = $item->toArray();
            $articleList[] = $item;
        }

        return [$total, $articleList];
    }

    public function articleDetail(string $uuid)
    {
        $articleInfo = ArticleInfoModel::create()->where('uuid', $uuid)->get();
        $articleInfo = $articleInfo->toArray();

        $filePath = EASYSWOOLE_ROOT.'/Doc/' . $articleInfo['menu_name'] . '/' . $articleInfo['file_name'];

        $content = file_get_contents($filePath);

        return Parsedown::instance()->text($content);
    }

}
