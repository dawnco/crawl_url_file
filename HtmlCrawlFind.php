<?php

/**
 * 抓取html代码中的 图片 js css
 * @author  Dawnc
 * @date    2015-01-09
 */
class HtmlCrawlFind {

    private $__text = null;

    public function __construct($text) {
        $this->__text = $text;
    }

    /**
     * 获取所有图片
     */
    public function getImages() {
        $spot    = '"';
        $pattern = "#<img(.+?)src=$spot(.+?)$spot#s";
        preg_match_all($pattern, $this->__text, $matches);
        $data    = $matches[2];
        return $data;
    }

    /**
     * 获取所有css
     */
    public function getCss() {
        $spot    = '"';
        $pattern = "#<link(.+?)href=$spot(.+?)$spot#s";
        preg_match_all($pattern, $this->__text, $matches);
        $data    = $matches[2];
        return $data;
    }

    /**
     * 获取所有css图片
     */
    public function getCssImages() {
        $pattern = "#url\((.+?)\)#s";
        preg_match_all($pattern, $this->__text, $matches);
        $data    = $matches[1];
        foreach ($data as &$v) {
            $v = trim($v, "'\"");
        }
        return $data;
    }

    /**
     * 获取所有JS 
     */
    public function getJs() {
        $spot    = '"';
        $pattern = "#<script(.+?)src=$spot(.+?)$spot#s";
        preg_match_all($pattern, $this->__text, $matches);
        $data    = $matches[2];
        return $data;
    }

}

class HtmlCrawlDownThread extends Thread {

    private $__url;
    private $__saveDir;
    public $error = null;

    
    public function parseUrl($url){
        
        $array = parse_url($url);
        
        $path = $array['path'];
        
        $path_a = explode("/", $path);
        foreach($path_a as $k=>$vo){
            $path_a[$k] = rawurlencode(rawurldecode($vo));
        }
        $path = implode("/", $path_a);
        
        $query = isset($array['query']) ? $array['query'] : "";
        
        if($query){
            parse_str($query, $arr);
            $query_str = array();
            foreach($arr as $k => $v){
                $query_str[] = $k ."=" . rawurlencode($v);
            }
            $query = implode("&", $query_str);
        }
        
        
        
        
        $fragment = isset($array['fragment']) ? $array['fragment'] : "";
        
        $url = $array['scheme'] . "://" .$array['host'] . $path . ($query ? "?$query" : "") . ($fragment ? "#$fragment" : "");
        
      
        return $url;
        
    }
    
    public function saveFromUrl() {

        $array = parse_url($this->__url);
        $file  = $this->__saveDir . "/" . $array['path'];
        $file  = preg_replace("/\/+/", "/", $file);

        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }


        $ext = substr($file, strrpos($file, ".") + 1);
        if (in_array($ext, array("jpg", "jpeg", "png", "gif")) && is_file($file) && getimagesize($file)) {
            //图片已经下载过了
            return true;
        } elseif (is_file($file)) {
            //文件已经下载过了
            return true;
        }

       // $userAgent = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:31.0) Gecko/20100101 Firefox/31.0";
        
        $headers ['User-Agent']      = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET4.0C; .NET4.0E; InfoPath.3)';
        $headers ['Accept']          = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
        $headers ['Accept-Encoding'] = 'gzip, deflate';
        $headers ['Accept-Language'] = 'zh-cn,zh;q=0.8,en-us;q=0.5,en;q=0.3';
        $headers ['Connection']      = 'keep-alive';
        $headerArr                   = array();
        foreach ($headers as $n => $v) {
            $headerArr [] = $n . ': ' . $v;
        }
 
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->parseUrl($this->__url));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);
        //curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        //ssl
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        //curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //返回
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); //跟随跳转

        $output = curl_exec($ch);
        if ($output === false) {
            $this->error = curl_error($ch);
        }
        curl_close($ch);


        if ($output) {
            file_put_contents($file, $output);
            echo "download:\n {$this->__url}\n";
            echo "save    :\n {$file}\n";
        } else {
            echo "error   :\n" . $this->error . "\n";
        }
    }

    public function __construct($url, $save_dir) {
        $this->__url     = $url;
        $this->__saveDir = $save_dir;
    }

    public function run() {
        $this->saveFromUrl();
    }

}

class HtmlCrawlDown {

    /**
     * 
     * @param type $urls
     * @param type $save_dir 保存路径
     * @param type $base_url url根路径
     */
    public static function down($urls, $save_dir, $base_url = "") {

        foreach ($urls as $key => $url) {
            $thread_array[$key] = new HtmlCrawlDownThread($base_url . $url, $save_dir);
            $thread_array[$key]->start();
        }

        foreach ($thread_array as $thread_array_key => $thread_array_value) {
            while ($thread_array[$thread_array_key]->isRunning()) {
                usleep(10);
            }
        }
    }

}
