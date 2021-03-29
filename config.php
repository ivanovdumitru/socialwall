<?php

/**
 * PHP Social Stream 2.8.9
 * Copyright 2015-2020 Axent Media (support@axentmedia.com)
 */

// Path to script directory (public path like ./social-stream/ or https://yourdomain.com/social-stream/)
define( 'SB_PATH', './social-stream/' );

// Time Zone
define( 'SB_TIMEZONE', 'US/Pacific' );

// Language & Locale
// ATTENTION: You need to create your language translation directory and files in ./language directory before making any change here.
// More information can be find here: https://axentmedia.com/php-social-stream-docs/#translation
define( 'SB_LOCALE', 'en' );

/**
 * Available options:
 * en => English (United States)
 * ar => العربية
 * az => Azərbaycan dili
 * bg_BG => Български,
 * bs_BA => Bosanski,
 * ca => Català,
 * cy => Cymraeg,
 * da_DK => Dansk,
 * de_CH => Deutsch (Schweiz),
 * de_DE => Deutsch,
 * el => Ελληνικά,
 * en_CA => English (Canada),
 * en_AU => English (Australia),
 * en_GB => English (UK),
 * eo => Esperanto,
 * es_PE => Español de Perú,
 * es_ES => Español,
 * es_MX => Español de México,
 * es_CL => Español de Chile,
 * eu => Euskara,
 * fa_IR => فارسی,
 * fi => Suomi,
 * fr_FR => Français,
 * gd => Gàidhlig,
 * gl_ES => Galego,
 * haz => هزاره گی,
 * he_IL => עִבְרִית,
 * hr => Hrvatski,
 * hu_HU => Magyar,
 * id_ID => Bahasa Indonesia,
 * is_IS => Íslenska,
 * it_IT => Italiano,
 * ja => 日本語,
 * ko_KR => 한국어,
 * lt_LT => Lietuvių kalba,
 * my_MM => ဗမာစာ,
 * nb_NO => Norsk bokmål,
 * nl_NL => Nederlands,
 * nn_NO => Norsk nynorsk,
 * oci => Occitan,
 * pl_PL => Polski,
 * ps => پښتو,
 * pt_PT => Português,
 * pt_BR => Português do Brasil,
 * ro_RO => Română,
 * ru_RU => Русский,
 * sk_SK => Slovenčina,
 * sl_SI => Slovenščina,
 * sq => Shqip,
 * sr_RS => Српски језик,
 * sv_SE => Svenska,
 * th => ไทย,
 * tr_TR => Türkçe,
 * ug_CN => Uyƣurqə,
 * uk => Українська,
 * zh_CN => 简体中文,
 * zh_TW => 繁體中文
*/

// DateTime Format - Unix style
define( 'SB_DATE_FORMAT', '%B %e, %Y' );
define( 'SB_TIME_FORMAT', '%I:%M %p' );

// For Ajax Security
define( 'SB_NONCE_KEY', '1a2b3c4d' ); // Replace this with a different unique phrases

// Use CURL
define( 'SB_CURL', 1 );

// Social API connection timeout (sec)
define( 'SB_API_TIMEOUT', 15 );

// Development mode
define( 'SB_DEBUG', false );

// Proxy setting
$GLOBALS['SB_PROXY'] = array(
    'proxy' => '',
    'proxy_port' => '',
    'proxy_userpass' => ''
);

// API Credentials
$GLOBALS['api'] = array(
    'facebook' => array(
        // Put your Facebook pages access tokens array here
        'facebook_accounts' => array()
    ),
    'twitter' => array(
        'twitter_api_key' => '', // Put your Twitter API Key within ''
        'twitter_api_secret' => '', // Put your Twitter API Secret within ''
        'twitter_access_token' => '', // Put your Twitter OAuth Access Token within ''
        'twitter_access_token_secret' => '' // Put your Twitter OAuth Access Token Secret within ''
	),
	/*
    'instagram' => array(
        // Put your Instagram access tokens array here
        'instagram_accounts' => array(),
        // Put your Instagram login information array here if required
        'instagram_logins' => array()
    ),*/
    'google' => array(
        'google_api_key' => '' // Put your Google API Key within ''
    ),
    'flickr' => array(
        'flickr_api_key' => '' // Put your Flickr API Key within ''
    ),
    'tumblr' => array(
        'tumblr_api_key' => '' // Put your Tumblr API Key within ''
    ),
    'soundcloud' => array(
        'soundcloud_client_id' => '' // Put your SoundCloud Client ID within ''
	),
	/*
    'linkedin' => array(
        'linkedin_access_token' => '' // Put your LinkedIn Access Token within ''
    ),*/
    'vimeo' => array(
        'vimeo_access_token' => '' // Put your Vimeo Access Token within ''
    ),
    'vk' => array(
        'vk_service_token' => '' // Put your VK Service Token within ''
    )
);

// Default colors
$social_colors = array(
    'facebook' => '#305790',
    'twitter' => '#06d0fe',
    'google' => '#c04d2e',
    'tumblr' => '#2E4E65',
    'delicious' => '#2d6eae',
    'pinterest' => '#cb1218',
    'flickr' => '#ff0185',
    'instagram' => '#295477',
    'youtube' => '#b80000',
    'vimeo' => '#00a0dc',
    'stumbleupon' => '#ec4415',
    'deviantart' => '#495d51',
    'rss' => '#d78b2d',
    'soundcloud' => '#ff3300',
    'vk' => '#4c75a3',
    'linkedin' => '#1884BC',
    'vine' => '#39a97b'
);

// Themes
$GLOBALS['themes'] = array(
    // Modern Light
    'sb-modern-light' => array(
        'layout' => 'modern',
        'font_size' => '11',
        'social_colors' => $social_colors,
        'social_icons' => '',
        'type_icons' => '',
        'custom_css' => '',
        'wall' => array(
            // 'background_color' => '#f3f3f3',
            // 'border_color' => '#d9d9d9',
            // 'border_size' => 1,
            'background_image' => '',
            'font_color' => '#000000',
            'link_color' => '#305790',
            'item_background_color' => '#ffffff',
            'item_border_color' => '#e5e5e5',
            'item_border_size' => 1
        ),
        'timeline' => array(
            // 'background_color' => '',
            // 'border_color' => '',
            // 'border_size' => 0,
            'background_image' => '',
            'font_color' => '#000000',
            'link_color' => '#305790',
            'item_background_color' => '#ffffff',
            'item_border_color' => '#e5e5e5',
            'item_border_size' => 1
        ),
        'feed' => Array(
            'title_background_color' => '#dd3333',
            'title_color' => '#ffffff',
            'background_color' => '#f2f2f2',
            'border_color' => '#e5e5e5',
            'border_size' => 1,
            'background_image' => '',
            'font_color' => '#000000',
            'link_color' => '#305790',
            'item_background_color' => '#ffffff',
            'item_border_color' => '#e2e2e2',
            'item_border_size' => 1
        ),
        'feed_sticky' => Array(
            'title_background_color' => '#dd3333',
            'title_color' => '#FFFFFF',
            'opener_image' => '',
            'background_color' => '#f2f2f2',
            'border_color' => '#d6d6d6',
            'border_size' => 1,
            'background_image' => '',
            'font_color' => '#000000',
            'link_color' => '#305790',
            'item_background_color' => '#ffffff',
            'item_border_color' => '#e2e2e2',
            'item_border_size' => 1
        ),
        'feed_carousel' => Array(
            'title_background_color' => '#dd3333',
            'title_color' => '#ffffff',
            // 'background_color' => '#f2f2f2',
            // 'border_color' => '#e5e5e5',
            // 'border_size' => 1,
            'background_image' => '',
            'font_color' => '#000000',
            'link_color' => '#305790',
            'item_background_color' => '#ffffff',
            'item_border_color' => '#e2e2e2',
            'item_border_size' => 1
        )
    ),
    // Metro Dark
    'sb-metro-dark' => array(
        'layout' => 'metro',
        'font_size' => '11',
        'social_colors' => $social_colors,
        'type_icons' => '',
        'custom_css' => '',
        'wall' => Array(
            // 'background_color' => '#2d2d2d',
            // 'border_color' => '#280000',
            // 'border_size' => 1,
            'background_image' => '',
            'font_color' => '#ffffff',
            'item_background_color' => '#444444',
            'item_border_color' => '#050505',
            'item_border_size' => 1
        ),
        'timeline' => Array(
            // 'background_color' => '#2d2d2d',
            // 'border_color' => '#280000',
            // 'border_size' => 1,
            'background_image' => '',
            'font_color' => '#ffffff',
            'item_background_color' => '#444444',
            'item_border_color' => '#000000',
            'item_border_size' => 1,
        ),
        'feed' => Array(
            'title_background_color' => '#dd3333',
            'title_color' => '#ffffff',
            'background_color' => '#2b2b2b',
            'border_color' => '#000000',
            'border_size' => 1,
            'background_image' => '',
            'font_color' => '#ffffff',
            'item_background_color' => '#444444',
            'item_border_color' => '#000000',
            'item_border_size' => 1
        ),
        'feed_sticky' => Array(
            'title_background_color' => '#dd3333',
            'title_color' => '#FFFFFF',
            'opener_image' => '',
            'background_color' => '#2d2d2d',
            'border_color' => '#000000',
            'border_size' => 1,
            'background_image' => '',
            'font_color' => '#FFFFFF',
            'item_background_color' => '#545454',
            'item_border_color' => '#000000',
            'item_border_size' => 3
        ),
        'feed_carousel' => Array(
            'title_background_color' => '#dd3333',
            'title_color' => '#ffffff',
            // 'background_color' => '#2b2b2b',
            // 'border_color' => '#000000',
            // 'border_size' => 1,
            'background_image' => '',
            'font_color' => '#ffffff',
            'item_background_color' => '#444444',
            'item_border_color' => '#000000',
            'item_border_size' => 1
        )
    ),
    // Modern 2 Light
    'sb-modern2-light' => array(
        'layout' => 'modern2',
        'font_size' => '11',
        'social_colors' => $social_colors,
        'type_icons' => '',
        'custom_css' => '',
        'wall' => array(
            // 'background_color' => '#f3f3f3',
            // 'border_color' => '#d9d9d9',
            // 'border_size' => 1,
            'background_image' => '',
            'font_color' => '#000000',
            'item_background_color' => '#ffffff',
            'item_border_color' => '#e5e5e5',
            'item_border_size' => 1
        ),
        'timeline' => Array(
            // 'background_color' => '',
            // 'border_color' => '',
            // 'border_size' => 0,
            'background_image' => '',
            'font_color' => '#000000',
            'item_background_color' => '#ffffff',
            'item_border_color' => '#e5e5e5',
            'item_border_size' => 1
        ),
        'feed' => Array(
            'title_background_color' => '#dd3333',
            'title_color' => '#ffffff',
            'background_color' => '#f2f2f2',
            'border_color' => '#e5e5e5',
            'border_size' => 1,
            'background_image' => '',
            'font_color' => '#000000',
            'item_background_color' => '#ffffff',
            'item_border_color' => '#e2e2e2',
            'item_border_size' => 1
        ),
        'feed_sticky' => Array(
            'title_background_color' => '#dd3333',
            'title_color' => '#FFFFFF',
            'opener_image' => '',
            'background_color' => '#f2f2f2',
            'border_color' => '#d6d6d6',
            'border_size' => 1,
            'background_image' => '',
            'font_color' => '#000000',
            'item_background_color' => '#ffffff',
            'item_border_color' => '#e2e2e2',
            'item_border_size' => 1
        ),
        'feed_carousel' => Array(
            'title_background_color' => '#dd3333',
            'title_color' => '#ffffff',
            // 'background_color' => '#f2f2f2',
            // 'border_color' => '#e5e5e5',
            // 'border_size' => 1,
            'background_image' => '',
            'font_color' => '#000000',
            'item_background_color' => '#ffffff',
            'item_border_color' => '#e2e2e2',
            'item_border_size' => 1
        )
    ),
    // Default Light
    'sb-default-light' => array(
        'layout' => 'default',
        'font_size' => '11',
        'social_colors' => $social_colors,
        'type_icons' => '',
        'custom_css' => '',
        'wall' => Array(
            // 'background_color' => '#f3f3f3',
            // 'border_color' => '#d9d9d9',
            // 'border_size' => 1,
            'background_image' => '',
            'font_color' => '#000000',
            'item_background_color' => '#ffffff',
            'item_border_color' => '',
            'item_border_size' => 1
        ),
        'timeline' => Array(
            // 'background_color' => '',
            // 'border_color' => '',
            // 'border_size' => 0,
            'background_image' => '',
            'font_color' => '#000000',
            'item_background_color' => '#ffffff',
            'item_border_color' => 'transparent',
            'item_border_size' => 1
        ),
        'feed' => Array(
            'title_background_color' => '#dd3333',
            'title_color' => '#ffffff',
            'background_color' => '#f2f2f2',
            'border_color' => '#e5e5e5',
            'border_size' => 1,
            'background_image' => '',
            'font_color' => '#050505',
            'item_background_color' => '#ffffff',
            'item_border_color' => '',
            'item_border_size' => 1
        ),
        'feed_sticky' => Array(
            'title_background_color' => '#dd3333',
            'title_color' => '#FFFFFF',
            'opener_image' => '',
            'background_color' => '#f2f2f2',
            'border_color' => '#e5e5e5',
            'border_size' => 1,
            'background_image' => '',
            'font_color' => '#000000',
            'item_background_color' => '#FFFFFF',
            'item_border_color' => '',
            'item_border_size' => 1
        ),
        'feed_carousel' => Array(
            'title_background_color' => '#dd3333',
            'title_color' => '#ffffff',
            // 'background_color' => '#f2f2f2',
            // 'border_color' => '#e5e5e5',
            // 'border_size' => 1,
            'background_image' => '',
            'font_color' => '#050505',
            'item_background_color' => '#ffffff',
            'item_border_color' => '',
            'item_border_size' => 1
        )
    ),
    // Flat Light
    'sb-flat-light' => array(
        'layout' => 'flat',
        'font_size' => '11',
        'social_colors' => $social_colors,
        'type_icons' => '',
        'custom_css' => '',
        'wall' => Array(
            // 'background_color' => '',
            // 'border_color' => '',
            // 'border_size' => 1,
            'background_image' => '',
            'font_color' => '#000000',
            'item_background_color' => '#ffffff',
            'item_border_color' => '',
            'item_border_size' => 1
        ),
        'timeline' => Array(
            // 'background_color' => '',
            // 'border_color' => '',
            // 'border_size' => 1,
            'background_image' => '',
            'font_color' => '#000000',
            'item_background_color' => '#ffffff',
            'item_border_color' => 'transparent',
            'item_border_size' => 2
        ),
        'feed' => Array(
            'title_background_color' => '#dd3333',
            'title_color' => '#ffffff',
            'background_color' => '#ffffff',
            'border_color' => '#cecece',
            'border_size' => 1,
            'background_image' => '',
            'font_color' => '#000000',
            'item_background_color' => '#ffffff',
            'item_border_color' => '',
            'item_border_size' => 2
        ),
        'feed_sticky' => Array(
            'title_background_color' => '#dd3333',
            'title_color' => '#ffffff',
            'opener_image' => '',
            'background_color' => '#ffffff',
            'border_color' => '#545454',
            'border_size' => 1,
            'background_image' => '',
            'font_color' => '#000000',
            'item_background_color' => '#ffffff',
            'item_border_color' => '#a3a3a3',
            'item_border_size' => 2
        ),
        'feed_carousel' => Array(
            'title_background_color' => '#dd3333',
            'title_color' => '#ffffff',
            // 'background_color' => '#ffffff',
            // 'border_color' => '#cecece',
            // 'border_size' => 1,
            'background_image' => '',
            'font_color' => '#000000',
            'item_background_color' => '#ffffff',
            'item_border_color' => '',
            'item_border_size' => 2
        )
    ),
    // Modern Dark
    'sb-modern-dark' => array(
        'layout' => 'modern',
        'font_size' => '11',
        'social_colors' => $social_colors,
        'type_icons' => '',
        'custom_css' => '',
        'wall' => Array(
            'background_color' => '#2d2d2d',
            'border_color' => '#280000',
            'border_size' => 1,
            'background_image' => '',
            'font_color' => '#ffffff',
            'item_background_color' => '#444444',
            'item_border_color' => '#050505',
            'item_border_size' => 1
        ),
        'timeline' => Array(
            'background_color' => '#2d2d2d',
            'border_color' => '#280000',
            'border_size' => 1,
            'background_image' => '',
            'font_color' => '#ffffff',
            'item_background_color' => '#444444',
            'item_border_color' => '#000000',
            'item_border_size' => 1,
        ),
        'feed' => Array(
            'title_background_color' => '#dd3333',
            'title_color' => '#ffffff',
            'background_color' => '#2b2b2b',
            'border_color' => '#000000',
            'border_size' => 1,
            'background_image' => '',
            'font_color' => '#ffffff',
            'item_background_color' => '#444444',
            'item_border_color' => '#000000',
            'item_border_size' => 1
        ),
        'feed_sticky' => Array(
            'title_background_color' => '#dd3333',
            'title_color' => '#FFFFFF',
            'opener_image' => '',
            'background_color' => '#2d2d2d',
            'border_color' => '#000000',
            'border_size' => 1,
            'background_image' => '',
            'font_color' => '#FFFFFF',
            'item_background_color' => '#545454',
            'item_border_color' => '#000000',
            'item_border_size' => 3
        ),
        'feed_carousel' => Array(
            'title_background_color' => '#dd3333',
            'title_color' => '#ffffff',
            'background_color' => '#2b2b2b',
            'border_color' => '#000000',
            'border_size' => 1,
            'background_image' => '',
            'font_color' => '#ffffff',
            'item_background_color' => '#444444',
            'item_border_color' => '#000000',
            'item_border_size' => 1
        )
    )
);

// End of file config.php