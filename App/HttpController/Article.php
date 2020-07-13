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

    /**
     * 热门文章
     */
    public function hotArticle()
    {
        $articleList = ArticleService::getInstance()->hotArticle();
        $this->formatReturn([
            'article_list' => $articleList,
        ], 'index.html');
    }

    /**
     * 分类下的文章
     */
    public function articleClass()
    {
        $params = $this->request()->getQueryParams();
        [$total, $articleList] = ArticleService::getInstance()
            ->articleClass($params['page'], $params['page_size'], $params['menu_name']);
        $this->formatReturn([
            'article_list' => $articleList,
            'total' => $total,
            'menu_name' => $params['menu_name'],
            'page' => $params['page'],
            'page_size' => $params['page_size'],
        ], 'index.html');
    }

    /**
     * 文章详情
     */
    public function articleDetail()
    {
        $uuid = $this->request()->getRequestParam('uuid');
        ArticleService::getInstance()->articlePV($this->request()->getHeader('x-real-ip')[0]??'', $uuid);
        $articleDetail = ArticleService::getInstance()->articleDetail($uuid);
        $this->formatReturn($articleDetail, 'detail.html');
    }

    /**
     * 返回给view层的数据
     *
     * @param array $data
     * @param string $page
     */
    private function formatReturn(array $data, string $page) : void
    {
        $menus = ArticleService::getInstance()->menes();

        $base = [
            'menus' => $menus,
            'view_config' => IniConfig::getInstance()->getConf('blog', 'view')
        ];

        $pageData = array_merge($base, $data);
        $this->response()->write(Render::getInstance()->render('Blog/'.$page, $pageData));
    }

}