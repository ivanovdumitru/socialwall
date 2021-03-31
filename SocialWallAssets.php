<?php

class SocialWallAssets extends \yii\web\AssetBundle
{
    public $sourcePath = '@vendor/Social/Social-Wall/public/';
    public $baseUrl = '@vendor/Social/Social-Wall/public/';

    public $css = [
        'css/brick.css',
        'css/colorbox.css',
        'css/hero.css',
        'css/slick.css',
        'css/slick-theme.css',
        'css/timeline-styles.css',
        'css/styles.css',
    ];
    public $js = [
        'js/brick.min.js',
        'js/jquery.min.js',
        'js/sb-rotating.js',
        'js/sb-timeline.js',
        'js/sb-wall.js',
        'js/slick.min.js',
    ];

    //BUG: Does not work with Yii2 activeform
    //public $jsOptions = ['async' => true];

    public $depends = [
        'yii\web\YiiAsset',
    ];


    public function getUrl($url)
    {
        return $this->baseUrl.'/'.ltrim($url, '/');
    }
}