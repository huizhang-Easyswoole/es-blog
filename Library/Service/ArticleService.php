<?php
namespace Library\Service;

use EasySwoole\Component\Singleton;
use EasySwoole\WordsMatch\WordsMatchClient;
use Parsedown;
use Library\Model\ArticleInfoModel;
use Library\Model\MenusModel;

class ArticleService
{
    use Singleton;

    public function hotArticle()
    {
        // TODO: 可根据访问量拿前10条
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

        $head = '';
        $content='';
        $file = fopen($filePath, 'rb');
        $isInHead = false;
        while (is_resource($file) && !feof($file)) {
            $line = fgets($file);
            if ($isInHead) {
                if (strlen(trim($line)) === 3 && strpos($line, '---') === 0) {
                    $isInHead = false;
                } else {
                    $head .= $line;
                }
            } else {
                if (strlen(trim($line))===3 && substr($line, 0, 3) === '---') {
                    $isInHead = true;
                } else {
                    $content .= $line;
                }
            }
        }

        if (empty($head))
        {
            $headInfo = WordsMatchClient::getInstance()->detect($content);
            $keywords = array_column($headInfo, 'word');
            $headInfo['title'] = $articleInfo['title'];
            $headInfo['meta'][] = [
                'name' => 'description',
                'content' => $articleInfo['description']
            ];
            $headInfo['meta'][] = [
                'name' => 'keywords',
                'content' => implode('|', $keywords)
            ];
        } else {
            $headInfo = yaml_parse($head);
        }

        return [
            'head' => $headInfo,
            'article' => Parsedown::instance()->text($content)
        ];
    }

}
