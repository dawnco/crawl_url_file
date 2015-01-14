<?php

/**
 * 获取文件中的 css js 图片 等 并多线程下载文件
 * 多线程用到 pthread扩展
 * @author  Dawnc
 * @date    2015-01-09
 */
include "HtmlCrawlFind.php";

$text = file_get_contents("data.php");

$html = new HtmlCrawlFind($text);

//$urls = $html->getJs();
//$urls = $html->getCss();
//$urls = $html->getImages();
$urls = $html->getCssImages();

//下载文件
HtmlCrawlDown::down($urls, "D:/tmp/temp");
