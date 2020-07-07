<?php
namespace Library\Service;

use EasySwoole\Component\Singleton;
use EasySwoole\EasySwoole\Crontab\Crontab;
use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\ORM\Db\Config;
use EasySwoole\ORM\Db\Connection;
use EasySwoole\ORM\DbManager;
use EasySwoole\Template\Render;
use EasySwoole\WordsMatch\WordsMatchServer;
use Library\Comm\IniConfig;
use Library\Comm\Smarty;
use Library\Crontab\DetectDocChange;

class MainServerRegistryService
{
    use Singleton;

    public function hotReload()
    {
        $hotReloadOptions = new \EasySwoole\HotReload\HotReloadOptions;
        $hotReload = new \EasySwoole\HotReload\HotReload($hotReloadOptions);
        $hotReloadOptions->setMonitorFolder([EASYSWOOLE_ROOT . '/App',EASYSWOOLE_ROOT . '/Library']);
        $server = ServerManager::getInstance()->getSwooleServer();
        $hotReload->attachToServer($server);
    }

    public function dbPool()
    {
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
    }

    public function crontab()
    {
        Crontab::getInstance()->addTask(DetectDocChange::class);
    }

    public function smarty()
    {
        Render::getInstance()->getConfig()->setRender(new Smarty());
        Render::getInstance()->getConfig()->setTempDir(EASYSWOOLE_TEMP_DIR);
        Render::getInstance()->attachServer(ServerManager::getInstance()->getSwooleServer());
    }

    public function wordsMatch()
    {
        $config = [
            'wordBank' => EASYSWOOLE_ROOT.'/wordsmatch.txt', // 词库地址
            'processNum' => 1, // 进程数
            'maxMem' => 128, // 每个进程最大占用内存(M)
            'separator' => ',', // 词和其它信息的间隔符
        ];

        // 将words-match 进程绑定到主服务
        WordsMatchServer::getInstance()
            ->setConfig($config)
            ->attachToServer(ServerManager::getInstance()->getSwooleServer());
    }
}