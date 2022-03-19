<?php

namespace App\Service;

use Illuminate\Support\Env;

class HttpService
{

    /**
     * @param $url
     * @param $data
     * @param $header
     * @param $https
     * @param $timeout
     * @return mixed
     */
    public static function send_post($url, $data = null, $header = array("content-type: application/json"), $https = true, $timeout = 5)
    {
        return self::curl_request($url, $data, $method = 'POST', $header, $https, $timeout);

    }


    /**
     * @Description: curl请求
     * @Author: Yang
     * @param $url
     * @param null $data
     * @param string $method
     * @param array $header
     * @param bool $https
     * @param int $timeout
     * @return mixed
     */
    public static function curl_request($url, $data = null, $method = 'get', $header = array("content-type: application/json"), $https = true, $timeout = 5)
    {
        $method = strtoupper($method);
        $ch = curl_init();                             //初始化
        curl_setopt($ch, CURLOPT_URL, $url);           //访问的URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//只获取页面内容，但不输出
        if ($https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//https请求 不验证证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//https请求 不验证HOST
        }
        if ($method != "GET") {
            if ($method == 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);//请求方式为post请求
            }
            if ($method == 'PUT' || strtoupper($method) == 'DELETE') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); //设置请求方式
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//请求数据
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //模拟的header头
        //curl_setopt($ch, CURLOPT_HEADER, false);//设置不需要头信息
        $result = curl_exec($ch);                      //执行请求
        curl_close($ch);                               //关闭curl，释放资源
        return $result;
    }
}
