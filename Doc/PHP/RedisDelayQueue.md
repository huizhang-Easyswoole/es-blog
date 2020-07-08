
#Docker+TP5+RabbitMQ+入消息队列+自动消费队列

> 注意：仅仅记录学习，不能直接运行，有任何问题请留言。
### 1. 安装RabbitMQ
`拉取镜像`
```
docker pull rabbitmq:3.7.7-management
```
```
docker run -d --name rabbitmq3.7.7 -p 5672:5672 -p 15672:15672 -v `pwd`/data:/var/lib/rabbitmq --hostname myRabbit -e RABBITMQ_DEFAULT_VHOST=my_vhost  -e RABBITMQ_DEFAULT_USER=admin -e RABBITMQ_DEFAULT_PASS=admin df80af9ca0c9
```
`http://ip:15672`
![image.png](https://upload-images.jianshu.io/upload_images/10306662-51f9245a643a679e.png?imageMogr2/auto-orient/strip%7CimageView2/2/w/1240)

### 2. composer安装[php-amqplib](https://segmentfault.com/a/1190000012308675)
```
composer require php-amqplib/php-amqplib
```

### 3.Tp5 实现
`再次封装php-amqplib`
```
<?php
/**
 * User: yuzhao
 * Description: RabbitMq 工具
 */
namespace app\common\tool;
use app\common\config\SelfConfig;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQTool {

    /**
     * User: yuzhao
     * @var
     * Description:
     */
    private $channel;

    private $mqConf;

    /**
     * RabbitMQTool constructor.
     * @param $mqName
     */
    public function __construct($mqName)
    {
        // 获取rabbitmq所有配置
        $rabbitMqConf = SelfConfig::getConfig('Source.rabbit_mq');
        if (!isset($rabbitMqConf['rabbit_mq_queue'])) {
            die('没有定义Source.rabbit_mq');
        }
        //建立生产者与mq之间的连接
        $this->conn = new AMQPStreamConnection(
            $rabbitMqConf['host'], $rabbitMqConf['port'], $rabbitMqConf['user'], $rabbitMqConf['pwd'], $rabbitMqConf['vhost']
        );
        $channal = $this->conn->channel();
        if (!isset($rabbitMqConf['rabbit_mq_queue'][$mqName])) {
            die('没有定义'.$mqName);
        }
        // 获取具体mq信息
        $mqConf = $rabbitMqConf['rabbit_mq_queue'][$mqName];
        $this->mqConf = $mqConf;
        // 声明初始化交换机
        $channal->exchange_declare($mqConf['exchange_name'], 'direct', false, true, false);
        // 声明初始化一条队列
        $channal->queue_declare($mqConf['queue_name'], false, true, false, false);
        // 交换机队列绑定
        $channal->queue_bind($mqConf['queue_name'], $mqConf['exchange_name']);
        $this->channel = $channal;
    }

    /**
     * User: yuzhao
     * @param $mqName
     * @return RabbitMQTool
     * Description: 返回当前实例
     */
    public static function instance($mqName) {
        return new RabbitMQTool($mqName);
    }

    /**
     * User: yuzhao
     * @param $data
     * Description: 写mq
     * @return bool
     */
    public function wMq($data) {
        try {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
            $msg = new AMQPMessage($data, ['content_type' => 'text/plain', 'delivery_mode' => 2]);
            $this->channel->basic_publish($msg, $this->mqConf['exchange_name']);
        } catch (\Throwable $e) {
            $this->closeConn();
            return false;
        }
        $this->closeConn();
        return true;
    }

    /**
     * User: yuzhao
     * @param int $num
     * @return array
     * Description:
     * @throws \ErrorException
     */
    public function rMq($num=1) {
        $rData = [];
        $callBack = function ($msg) use (&$rData){
            $rData[] = json_decode($msg->body, true);
        };
        for ($i=0;$i<$num;$i++) {
            $this->channel->basic_consume($this->mqConf['queue_name'], '', false, true, false, false, $callBack);
        }
        $this->channel->wait();
        $this->closeConn();
        return $rData;
    }

    /**
     * User: yuzhao
     * Description: 关闭连接
     */
    public function closeConn() {
        $this->channel->close();
        $this->conn->close();
    }

}
```
`入队列`

```
<?php
/**
 * User: yuzhao
 * Description:
 */
namespace app\test\controller;

use app\common\tool\RabbitMQTool;
use think\Controller;

class TestController extends Controller {
    public function test() {
        RabbitMQTool::instance('test')->wMq(['name'=>'123']);
    }
}
```

`启动消费队列`
```
<?php
/**
 * User: yuzhao
 * Description: 启动MQ,php xxx/public/index.php /daemon/start_Mq/main 队列别名 进程数 -d(守护进程) | -s (杀死进程)
 */

namespace app\daemon\controller;
use app\common\config\SelfConfig;
use app\common\tool\RabbitMQTool;

class StartMqController {

    private $dealPath = null;

    private $childsPid = array();

    /**
     * StartRabbitMQ constructor.
     */
    public function __construct()
    {
        // 脚本路径
        $this->dealPath = str_replace('/','\\',"/app/daemon/deal/");
    }

    /**
     * User: yuzhao
     * Description: 返回当前实例
     */
    public static function instance() {
        return new StartMqController();
    }

    /**
     * User: yuzhao
     * Description: 主要处理流程
     * @throws \ErrorException
     */
    public function main() {
        global $argv;
        // 扩展参数
        if (isset($argv[3])) {
            switch ($argv[3]) {
                case '-d': // 守护进程启动
                    $this->daemonStart();
                break;
                case '-s': // 杀死进程
                    $this->killEasyExport($argv[2]);die();
                break;
            }
        }
        // 判断参数
        if (count($argv) < 2) {
            die('缺少参数');
        }
        // 获取配置信息
        $rabbitMqConf = SelfConfig::getConfig('Source.rabbit_mq');
        if (!isset( $rabbitMqConf['rabbit_mq_queue'][$argv[2]])) {
            die('没有配置:'.$argv[2]);
        }
        // 获取mq配置
        $mqConf = $rabbitMqConf['rabbit_mq_queue'][$argv[2]];
        // 实例化处理脚本
        $dealClass = $this->dealPath.$mqConf['consumer'];
        $dealObj = new $dealClass;
        $processNum = 1;
        if (isset($mqConf['process_num']) || !is_numeric($mqConf['process_num']) || $mqConf['process_num'] < 1 || $mqConf['process_num'] >10 ) {
            $processNum = $mqConf['process_num'];
        }
        if (!isset($mqConf['deal_num']) || !is_numeric($mqConf['deal_num'])) {
            die('处理条数设置有误');
        }
        // fork进程
        for ($i=0; $i<$processNum; $i++) {
            $pid = pcntl_fork();
            if( $pid < 0 ){
                exit();
            } else if( 0 == $pid ) {
                $this->downMqData($dealObj, $argv, $mqConf);
                exit();
            } else if( $pid > 0 ) {
                $this->childsPid[] = $pid;
            }
        }
        while( true ){
            sleep(1);
        }
    }

    /**
     * User: yuzhao
     * @param $dealObj
     * @param $argv
     * @param $mqConf
     * @throws \ErrorException
     * Description:
     */
    private function downMqData($dealObj, $argv, $mqConf) {
        while (true) {
            // 下载数据
            $mqData = RabbitMQTool::instance($argv[2])->rMq($mqConf['deal_num']);
            $dealObj->deal($mqData);
            sleep(1);
        }
    }

    private function killEasyExport($startFile) {
        exec("ps aux | grep $startFile | grep -v grep | awk '{print $2}'", $info);
        if (count($info) <= 1) {
            echo "not run\n";
        } else {
            echo "[$startFile] stop success";
            exec("ps aux | grep $startFile | grep -v grep | awk '{print $2}' |xargs kill -SIGINT", $info);
        }
    }

    /**
     * User: yuzhao
     * Description: 守护进程模式启动
     */
    private function daemonStart() {
        // 守护进程需要pcntl扩展支持
        if (!function_exists('pcntl_fork'))
        {
            exit('Daemonize needs pcntl, the pcntl extension was not found');
        }
        umask( 0 );
        $pid = pcntl_fork();
        if( $pid < 0 ){
            exit('fork error.');
        } else if( $pid > 0 ) {
            exit();
        }
        if( !posix_setsid() ){
            exit('setsid error.');
        }
        $pid = pcntl_fork();
        if( $pid  < 0 ){
            exit('fork error');
        } else if( $pid > 0 ) {
            // 主进程退出
            exit;
        }
        // 子进程继续，实现daemon化
    }

}

```

`自定义配置文件`
```
<?php
/**
 * User: yuzhao
 * Description:
 */

return [
   
    'rabbit_mq' => [
        'host' => ip,
        'port' => 5672,
        'user' => 'root',
        'pwd' => 'xxx',
        'vhost' => 'my_vhost',
        'rabbit_mq_queue' => [
            'test' => [
                'exchange_name' => 'ex_test', // 交换机名称
                'queue_name' => 'que_test', // 队列名称
                'process_num' => 3, // 默认单台机器的进程数量
                'deal_num' => '50', // 单次处理数量
                'consumer' => 'DealTest' // 消费地址
            ]
        ]
    ]
];
```

### 4. 学习地址

https://www.cnblogs.com/yufeng218/p/9452621.html
https://blog.csdn.net/demon3182/article/details/77335206
https://blog.csdn.net/u010472499/article/details/78366614
https://segmentfault.com/a/1190000012308675



