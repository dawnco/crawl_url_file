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

    public function saveFromUrl() {

        $array = parse_url($this->__url);
        $file  = $this->__saveDir . "/" . $array['path'];
        $file  = preg_replace("/\/+/", "/", $file);

        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }

        
        $ext = substr($file, strrpos($file, ".") + 1);
        if (in_array($ext, array("jpg","jpeg","png","gif")) && is_file($file) && getimagesize($file)) {
            //图片已经下载过了
            return true;
        }elseif(is_file($file)){
            //文件已经下载过了
            return true;
        }

        $userAgent = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:31.0) Gecko/20100101 Firefox/31.0";
        $header    = array('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');

        $url = $this->__url;

        $array = explode("/", $url);

        foreach ($array as $k => &$v) {
            if ($k > 2) {
                $v = rawurlencode($v);
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, implode("/", $array));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
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
     */
    public static function down($urls, $save_dir) {

        foreach ($urls as $key => $url) {
            $thread_array[$key] = new HtmlCrawlDownThread($url, $save_dir);
            $thread_array[$key]->start();
        }

        foreach ($thread_array as $thread_array_key => $thread_array_value) {
            while ($thread_array[$thread_array_key]->isRunning()) {
                usleep(10);
            }
        }
    }

}
