<?php
namespace Library\Comm;

use EasySwoole\Component\Singleton;

class File
{
    use Singleton;


    /**
     * 递归获取所有目录
     *
     * @param string $pathName
     * @return array
     */
    public function trees(string $pathName)
    {
        $result = [];
        $temp = [];
        if(!is_dir($pathName) || !is_readable($pathName)) {
            return [];
        }
        $allFiles = scandir($pathName);
        foreach($allFiles as $fileName) {
            if(in_array($fileName, array('.', '..'))) {
                continue;
            }
            $fullName = $pathName.'/'.$fileName;
            if(is_dir($fullName)) {
                $result[$fullName] = $this->trees($fullName);
            }else {
                $temp[] = $fullName;
            }
        }
        if($temp) {
            foreach($temp as $f) {
                $result[] = $f;
            }
        }
        return $result;
    }

}