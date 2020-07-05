<?php
namespace App\HttpController;

use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\Template\Render;
use Library\Comm\IniConfig;
use Library\Service\ArticleService;

class Article extends Controller
{

    public function index()
    {

    }

    public function defaultArticle()
    {
        $menus = ArticleService::getInstance()->menes();
        $articleList = ArticleService::getInstance()->defaultArticle();
        $this->response()->write(Render::getInstance()->render('Blog/index.html', [
            'menus' => $menus,
            'article_list' => $articleList,
            'menu_name' => '',
            'total' => 0,
            'page' => 1,
            'page_size' => 10,
            'view_config' => IniConfig::getInstance()->getConf('blog', 'view')
        ]));
    }

    public function articleClass()
    {
        $params = $this->request()->getQueryParams();
        $menus = ArticleService::getInstance()->menes();
        [$total, $articleList] = ArticleService::getInstance()
            ->articleClass($params['page'], $params['page_size'], $params['menu_name']);
        $this->response()->write(Render::getInstance()->render('Blog/index.html', [
            'menus' => $menus,
            'article_list' => $articleList,
            'total' => $total,
            'menu_name' => $params['menu_name'],
            'page' => $params['page'],
            'page_size' => $params['page_size'],
            'view_config' => IniConfig::getInstance()->getConf('blog', 'view')
        ]));
    }

    public function articleDetail()
    {
        $uuid = $this->request()->getRequestParam('uuid');
        $article = ArticleService::getInstance()->articleDetail($uuid);
        $menus = ArticleService::getInstance()->menes();
        $this->response()->write(Render::getInstance()->render('Blog/detail.html', [
            'menus' => $menus,
            'article' => $article,
            'view_config' => IniConfig::getInstance()->getConf('blog', 'view')
        ]));
    }

}