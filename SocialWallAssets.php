<?php

class SocialWallAssets extends \yii\web\AssetBundle
{
    public $sourcePath = '@vendor/EstateApps/SocialWall/public/';
    public $baseUrl = '@vendor/EstateApps/SocialWall/public/';

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

//    public function init()
//    {
//        $theme = Yii::$app->getModule('estateapps')->theme;
//        $website = Yii::$app->getModule('estateapps')->website;
//
//        $this->sourcePath = '@app/websites/'.$website.'/';
//        $this->baseUrl = '@app/websites/'.$website.'/';
//        if (isset(Yii::$app->components['sass']))
//        {
//            $this->css = Yii::$app->sass->publishAndGetPathArray($this->scss,Yii::getAlias($this->baseUrl));
//        }
//    }

    public function getUrl($url)
    {
        return $this->baseUrl.'/'.ltrim($url, '/');
    }
}