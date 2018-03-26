<?php

class MyLog
{

    /**
     * @param $message
     * @param null $file_path 日志文件地址，null则使用默认的日志文件
     * @return mixed
     */
    public static function log($message, $operate = '', $file_path = null)
    {
        try {
            $file_path = $file_path == null ? App()->getConfig("tempdir") . "/runtime/IpAddressHotMap.log" : $file_path;
            $file = fopen($file_path, "a");
            fwrite($file, date('Y-m-d H:i:s') . " [$operate] $message\n");
            fclose($file);
        } catch (Exception $e) {
        }
    }

}
