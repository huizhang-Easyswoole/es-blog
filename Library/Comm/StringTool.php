<?php
namespace Library\Comm;

use EasySwoole\Component\Singleton;

class StringTool
{
    use Singleton;

    function strBetween($content, $start, $end){
        $i = strpos($content, $start);
        if(!$i){
            return false;
        }

        $i += strlen($start);

        $j = strpos($content, $end, $i);

        if(!$j){
            return false;
        }

        return substr($content, $i, $j-$i);
    }
}