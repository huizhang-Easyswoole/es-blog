<?php
/**
 * @CreateTime:   2020/5/3 下午6:30
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2020) Easyswoole all rights reserved
 * @Description:  解析ini配置文件
 */
namespace Library\Comm;

use EasySwoole\Component\Singleton;
use EasySwoole\Spl\SplArray;

class IniConfig
{
    use Singleton;

    protected $iniDir;

    public function __construct($iniDir = EASYSWOOLE_ROOT.'/Library/Config/')
    {
        $this->iniDir = $iniDir;
    }

    public function setDir($iniDir)
    {
        $this->iniDir = $iniDir;
        return $this;
    }

    public function getConf(string $fileName, $key)
    {
        return $this->parseConf($fileName, $key);
    }

    private function parseConf($fileName, $key)
    {
        $config = parse_ini_file($this->iniDir.'/'.$fileName.'.ini', true);

        if ($key === null) {
            return $config;
        }

        if (empty($key)) {
            return null;
        }
        if (strpos($key, '.') > 0) {
            $temp = explode('.', $key);
            if (is_array($config)) {
                $data = new SplArray($config);
                return $data->get(implode('.', $temp));
            }
        }

        return $config[$key];
    }
}