<?php
namespace EasySwoole\EasySwoole;

use EasySwoole\EasySwoole\Crontab\Crontab;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\ORM\Db\Connection;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Db\Config;
use EasySwoole\Template\Render;
use Library\Comm\IniConfig;
use Library\Comm\Smarty;
use Library\Crontab\DetectDocChange;

class EasySwooleEvent implements Event
{

    public static function initialize()
    {
        // TODO: Implement initialize() method.
        date_default_timezone_set('Asia/Shanghai');
    }

    public static function mainServerCreate(EventRegister $register)
    {
        // TODO: Implement mainServerCreate() method.
        $hotReloadOptions = new \EasySwoole\HotReload\HotReloadOptions;
        $hotReload = new \EasySwoole\HotReload\HotReload($hotReloadOptions);
        $hotReloadOptions->setMonitorFolder([EASYSWOOLE_ROOT . '/App',EASYSWOOLE_ROOT . '/Library']);
        $server = ServerManager::getInstance()->getSwooleServer();
        $hotReload->attachToServer($server);

        $dbConfig = IniConfig::getInstance()->getConf('blog', 'db');
        $config = new Config();
        $config->setDatabase($dbConfig['database']);
        $config->setUser($dbConfig['user']);
        $config->setPassword($dbConfig['password']);
        $config->setHost($dbConfig['host']);
        $config->setGetObjectTimeout(3.0); //设置获取连接池对象超时时间
        $config->setIntervalCheckTime(30*1000); //设置检测连接存活执行回收和创建的周期
        $config->setMaxIdleTime(15); //连接池对象最大闲置时间(秒)
        $config->setAutoPing(5); //设置自动ping客户端链接的间隔
        DbManager::getInstance()->addConnection(new Connection($config));

        Crontab::getInstance()->addTask(DetectDocChange::class);

        Render::getInstance()->getConfig()->setRender(new Smarty());
        Render::getInstance()->getConfig()->setTempDir(EASYSWOOLE_TEMP_DIR);
        Render::getInstance()->attachServer(ServerManager::getInstance()->getSwooleServer());
    }

    public static function onRequest(Request $request, Response $response): bool
    {
        // TODO: Implement onRequest() method.
        $response->withHeader('Access-Control-Allow-Origin', '*');
        $response->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->withHeader('Access-Control-Allow-Credentials', 'true');
        $response->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        if ($request->getMethod() === 'OPTIONS') {
            return false;
        }
        return true;
    }

    public static function afterRequest(Request $request, Response $response): void
    {
        // TODO: Implement afterAction() method.
    }
}