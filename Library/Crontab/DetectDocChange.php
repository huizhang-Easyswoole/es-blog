<?php

namespace Library\Crontab;

use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use Library\Comm\File;
use Library\Comm\StringTool;
use Library\Model\ArticleInfoModel;
use Library\Model\MenusModel;

class DetectDocChange extends AbstractCronTask
{

    public static function getRule(): string
    {
        return '*/1 * * * *';
    }

    public static function getTaskName(): string
    {
        return  'DetectDocChange';
    }

    private $specialChars = [
        '>' => '',
        '#' => '',
        '`' => ''
    ];

    function run(int $taskId, int $workerIndex)
    {

        $trees = File::getInstance()->trees(EASYSWOOLE_ROOT.'/Doc');

        [$menus, $articlesInfo] = $this->getMenusAndArticlesInfo($trees);

        $this->updateMenus($menus);

        $this->updateArticlesInfo($articlesInfo);
    }

    private function updateArticlesInfo($articlesInfo)
    {
        $uuids = array_column($articlesInfo, 'uuid');

        $articlesInfoDb = ArticleInfoModel::create()->all();

        $articleInfoDbUuid = [];
        foreach ($articlesInfoDb as $articleInfoDb)
        {
            $articleInfo = $articleInfoDb->toArray();
            $articleInfoDbUuid[] = $articleInfo['uuid'];
        }

        $intersect = array_intersect($uuids, $articleInfoDbUuid);

        foreach ($articlesInfo as $item)
        {
            if (in_array($item['uuid'], $intersect, false)) {
                $articleInfo = ArticleInfoModel::create()->get(['uuid' => $item['uuid']]);
                $articleInfo->update([
                    'title' => $item['title']??'',
                    'introduction' => $item['introduction']??'',
                    'cover' => $item['cover']??'/Images/cover.png',
                    'utime' => date('Y-m-d H:i:s'),
                    'uuid' => $item['uuid'],
                    'menu_name' => $item['menu_name'],
                    'file_name' => $item['file_name']
                ]);

            } else {
                ArticleInfoModel::create()->data([
                    'title' => $item['title']??'',
                    'introduction' => $item['introduction']??'',
                    'cover' => $item['cover']??'/Images/cover.png',
                    'utime' => date('Y-m-d H:i:s'),
                    'uuid' => $item['uuid'],
                    'menu_name' => $item['menu_name'],
                    'file_name' => $item['file_name'],
                ], false)->save();
            }
        }


//        foreach ($articleInfoDbUuid as $uuid)
//        {
//            if (
//                !empty($articlesInfoDb) &&
//                !in_array($uuid, $intersect, false)
//            ) {
//                ArticleInfoModel::create()->destroy([
//                    'uuid' => $uuid
//                ]);
//            }
//        }

        return true;
    }

    private function updateMenus(array $menus)
    {
        $menusModel = MenusModel::create();
        $menusDb = $menusModel->all();
        if (empty($menusDb)) {
            $menusDb = [];
        }

        $menuDbArr = [];
        foreach ($menusDb as $menu)
        {
            $menu = $menu->toArray();
            $menuDbArr[] = $menu['menu_name'];
        }

        $intersect = array_intersect($menus, $menuDbArr);

        foreach ($menus as $menu)
        {
            if (!in_array($menu, $intersect, false)) {
                $menusModel->data([
                    'menu_name' => $menu
                ])->save();

            }
        }

//        foreach ($menuDbArr as $menuDb)
//        {
//            if (
//                !empty($menusDb) &&
//                !in_array($menuDb, $intersect, false)
//            ) {
//                $menusModel->destroy([
//                    'menu_name' => $menuDb
//                ]);
//            }
//        }

        return true;
    }

    private function articleInfo(string $file) : array
    {
        $articleInfo = [];
        $fileArr = explode('/', $file);
        $articleInfo['file_name'] = $fileArr[count($fileArr)-1];
        $ext = substr(strrchr($file, '.'), 1);
        if ($ext === 'md')
        {
            $fileResource = fopen($file, 'a+');
            $waitUpArticleInfo = [
                'title' => false,
                'introduction' => false,
                'cover' => false
            ];
            $description = '';
            while (!feof($fileResource))
            {
                if ($waitUpArticleInfo['title'] && $waitUpArticleInfo['introduction'] && $waitUpArticleInfo['cover'])
                {
                    break;
                }

                $line = trim(fgets($fileResource));
                if (empty($line)) {
                    continue;
                }

                $type = $line[0];
                if ($type === '#' && !$waitUpArticleInfo['title'])
                {
                    $articleInfo['title'] = mb_substr($line, 1);
                    $waitUpArticleInfo['title'] = true;
                } elseif ($type === '!' && !$waitUpArticleInfo['cover']) {
                    $articleInfo['cover'] = StringTool::getInstance()->strBetween($line, '(', ')');
                    $waitUpArticleInfo['cover'] = true;
                }

                $line = strtr($line, $this->specialChars);

                if (strlen($description) <= 300 && !$waitUpArticleInfo['introduction'])
                {
                    preg_match_all('/[\x{4e00}-\x{9fff}]+/u', $line, $chinese);
                    $chinese = $chinese[0];
                    $chinese = implode('', $chinese);
                    $description .= mb_substr($chinese, 0, 300 - mb_strlen($description));
                    if (empty($description))
                    {
                        continue;
                    }
                    if (strlen($description) >= 300)
                    {
                        $waitUpArticleInfo['introduction'] = true;
                        $articleInfo['introduction'] = $description;
                    }
                }
            }
            $articleInfo['uuid'] = md5($articleInfo['file_name']);
            fclose($fileResource);
        }

        return $articleInfo;
    }

    private function getMenusAndArticlesInfo(array $trees)
    {
        $menus = [];
        $articlesInfo = [];
        foreach ($trees as $menu => $files)
        {
            if (is_dir($menu))
            {
                $menuArr = explode('/', $menu);
                $menus[] = $menuArr[count($menuArr)-1];
                foreach ($files as $file)
                {
                    $articleInfo = $this->articleInfo($file);
                    $articleInfo['menu_name'] = $menuArr[count($menuArr)-1];
                    if (!empty($articleInfo)) {
                        $articlesInfo[] = $articleInfo;
                    }
                }
            }
        }

        return [$menus, $articlesInfo];
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        echo $throwable->getMessage();
    }
}
