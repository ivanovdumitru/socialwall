<?php

/**
 * Script Name: PHP Social Stream
 * Script URI: https://axentmedia.com/php-social-stream/
 * Description: Combine all your social media network & feed updates (Facebook, Twitter, Flickr, YouTube, RSS, ...) into one feed and display on your website.
 * Tags: social media, social networks, social feed, social tabs, social wall, social timeline, social stream, php social stream, feed reader, facebook, twitter, tumblr, delicious, pinterest, flickr, instagram, youtube, vimeo, deviantart, rss, soundcloud, vk, vine
 * Version: 2.9.2
 * Author: Axent Media
 * Author URI: https://axentmedia.com/
 * License: https://codecanyon.net/licenses/standard
 * 
 * Copyright 2014-2020 Axent Media (support@axentmedia.com)
 */

// Load configuration
define( 'SB_DIR', dirname( __FILE__ ) );

@include( SB_DIR . '/config.php' );
@include( SB_DIR.'/SocialWallAssets.php');
$url = \SocialWallAssets::register($this);
define('url',$url->baseUrl);
if ( ! defined( 'SB_PATH' ) && ! isset( $GLOBALS['SB_PATH'] ) ) {
    exit('Configuration file was not found or path to PHP Social Stream script directory is not defined! <strong>Run script setup.</strong>');
}

// For load more feature
if ( ! session_id() )
    session_start();

// Make sure feeds are getting local timestamps
date_default_timezone_set( SB_TIMEZONE );

// DateTime localization
if ( strtoupper( substr( PHP_OS, 0, 3 ) ) == 'WIN' ) {
    setlocale( LC_ALL, ss_win_locale( SB_LOCALE ) );
} else {
    setlocale( LC_ALL, SB_LOCALE );
}

// Define constants
define( 'SB_DT_FORMAT', ss_format_locale( SB_DATE_FORMAT ) );
define( 'SB_TT_FORMAT', ss_format_locale( SB_TIME_FORMAT ) );
define( 'SB_LOGFILE', SB_DIR . '/log.txt' );

// load cache system
include( SB_DIR . '/library/SimpleCache.php' );
include( SB_DIR . '/library/HtmlEmbedCache.php' );

// Language localization
if ( file_exists(SB_DIR . '/language/social-stream-' . SB_LOCALE . '.php') )
	include( SB_DIR . '/language/social-stream-' . SB_LOCALE . '.php' );
else
	include( SB_DIR . '/language/social-stream-en.php' );

$GLOBALS['_'] = $_;
$GLOBALS['enqueue'] = array(
    'general' => false, 'timeline' => false, 'carousel' => false, 'rotating' => false, 'wall' => false
);
$GLOBALS['sb_scripts'] = array();

// Instagram lib
include_once SB_DIR . "/library/class_instagram.php";

// Layout lib
require SB_DIR . "/library/layout.php";
require SB_DIR . "/layout/default.php";

// load dependencies
require SB_DIR . "/library/autoload.php";

// load Twitter lib
use Abraham\TwitterOAuth\TwitterOAuth;

// load login session cache for Instagram
use phpFastCache\Helper\Psr16Adapter;

// Social Stream main class
class SocialStream {
    public $attr, $final, $finalslide;
    public $cache, $forceCrawl, $target, $setoption, $fetchcomments;
    public $echo = true;
    public $args = null;
	private $instagram;
	private $sbinstagram;
    
    public function __construct() {}
    
    // Initialize by property
    function run() {
        $this->init( $this->attr, $this->echo, $this->args );
    }

    // Initialize by function
    function init( $attr, $echo = true, $args = null, $ajax_feed = array(), $loadmore = array() ) {

        $SB_PATH = url;
        $id = ! empty($attr['id']) ? $attr['id'] : substr( sha1( implode('', $attr) ), 0, 5 );
        $type = ! empty($attr['type']) ? $attr['type'] : 'wall';
        $this->sboption = $attr['network'];
        $attr_ajax = json_encode($attr);
        
        // get default setting & post options
        $setoption = array(
            'setting' => array(
                'theme' => 'sb-modern-light',
				'results' => '30',
				'custom_results' => false,
				'custom_results_default' => 5,
                'words' => '40',
                'slicepoint' => '300',
                'commentwords' => '20',
                'titles' => '15',
                'dateformat' => 'friendly',
                'readmore' => '1',
                'order' => 'date',
                'filters' => '1',
                'filter_ads' => true,
                'display_all' => '',
                'loadmore' => '1',
                'iframe' => 'media',
                'slideshow' => false,
                'slideshowSpeed' => '30000',
                'layout_image' => 'imgexpand',
                'layout_user' => 'userpic',
                'links' => '1',
                'nofollow' => '1',
                'https' => true,
                'lazyload' => true,
                'filters_order' => 'facebook,twitter,google,tumblr,delicious,pinterest,flickr,instagram,youtube,vimeo,stumbleupon,deviantart,rss,soundcloud,vk,linkedin,vine',
                'display_ads' => true,
                'cache' => '360',
                'crawl' => '10',
                'debuglog' => '0'
            ),
            'wallsetting' => array(
                'transition' => '400',
                'stagger' => '',
                'filter_search' => true,
                'originLeft' => 'true',
                'wall_width' => '',
                'wall_height' => '',
                'fixWidth' => 'block',
                'breakpoints' => array('5', '4', '4', '3', '2', '2', '1'),
                'itemwidth' => '250',
                'gutterX' => '10',
                'gutterY' => '10'
            ),
            'gridsetting' => array(
                'height' => '',
                'fixWidth' => 'block',
                'breakpoints' => array('5', '4', '4', '3', '2', '2', '1'),
                'gutterX' => '0',
                'gutterY' => '0',
                'scroll' => false,
				'columns_style' => false,
				'grid_height' => 340
            ),
            'feedsetting' => array(
                'rotate_speed' => '100',
                'duration' => '4000',
                'direction' => 'up',
                'controls' => '1',
                'autostart' => '1',
                'pauseonhover' => '1',
                'width' => '280'
            ),
            'carouselsetting' => array(
				'cs_speed' => '400',
				'cs_rows' => '2',
                'cs_item' => array('4', '3', '2', '2', '1'),
                'cs_height' => array(
                    'thumb' => '150',
                    'text' => '75',
                    'meta' => '50'
                ),
				'cs_controls' => 'true',
				'cs_rtl' => 'false',
				'cs_auto' => 'false',
				'cs_autospeed' => '2000',
				'cs_pause' => 'true',
                'cs_loop' => 'true',
				'cs_pager' => 'true'
            ),
            'timelinesetting' => array(
                'onecolumn' => 'false'
            )
        );
        
        // define theme
        $theme = ! empty($attr['theme']) ? $attr['theme'] : @$setoption['setting']['theme'];
        $themeoption = $GLOBALS['themes'][$attr['theme']];

        // set some settings

		if ( $type == 'carousel' ) {
			$attr['carousel'] = 'on';
			$type = 'feed';
		}
		
        $label = $type.$id;
        if ( $type == 'feed' ) {
            $is_feed = true;
            $settingsection = ! empty($attr['carousel']) ? 'carouselsetting' : 'feedsetting';
            $filterlabel = '';
        } else {
            $is_feed = false;
            $settingsection = $type.'setting';
            $filterlabel = ' filter-label';
        }
        $is_timeline = ( $type == 'timeline' ) ? true : false;
        $is_wall = ( $type == 'wall' ) ? true : false;
        $is_grid = ( $type == 'grid' ) ? true : false;

        $typeoption = $type;
        if ( $is_feed ) {
            if ( ! empty($attr['position'] ) ) {
                if ( $attr['position'] != 'normal' )
                    $typeoption = 'feed_sticky';
            }
            if ( ! empty($attr['carousel']) )
                $typeoption = 'feed_carousel';
        }

        // main container id
        if ( $label )
            $attr_id = ' id="timeline_'.$label.'"';
        $class = array('sboard');

        // merge shortcode and widget attributes with related default settings
        if ( empty($setoption[$settingsection]) )
            $setoption[$settingsection] = array();
        $this->attr = $attr = array_merge($setoption['setting'], $setoption[$settingsection], $attr);
        
    	// the array of feeds to get
    	$filtersArr = explode(',', str_replace(' ', '', $attr['filters_order']) );
    	$filtersArrAll = explode(',', str_replace(' ', '', $setoption['setting']['filters_order']) );
    	// if filters_order is set in social_stream()
    	if ($setoption['setting']['filters_order'] != $attr['filters_order']) {
			foreach($filtersArr as $val) {
				$filters_order[] = array($val => 1);
			}
			$filtersRest = array_diff($filtersArrAll, $filtersArr);
			foreach($filtersRest as $val) {
				$filters_order[] = array($val => 0);
			}
		} else {
			foreach($filtersArr as $val) {
				$filters_order[] = array($val => 1);
			}
		}
        $this->feed_keys = ( ! empty($ajax_feed) && $ajax_feed != 'all' ) ? $ajax_feed : $filters_order;
        
    	// set results
    	$results = ! empty($attr['results']) ? $attr['results'] : 10;
    	if ( ! empty($args['liveresults']) )
            if ( $results < $args['liveresults'] )
                $results = $args['liveresults'];
        if ($results > 100)
            $results = 100;
        $attr['results'] = $results;
            
        $attr['cache'] = (int)$attr['cache'];
        // set crawl time limit (some servers can not read a lot of feeds at the same time)
        $GLOBALS['crawled'] = 0;
        $crawl_limit = ($attr['cache'] == 0) ? 0 : (int)@$attr['crawl'];
		
		// Init Instagram official class
		$this->sbinstagram = new \SB_AX_SOCIAL\INSTAGRAM\SB_Instagram();

        // Init cache
        $cache = new SimpleCache;
        $cache->debug_log = $this->sbinstagram->debug_log = @$attr['debuglog'];
        
        if ( $is_feed ) {
            if ( ! empty($attr['carousel']) ) {
                $class[] = 'sb-carousel';
                if ( ! empty($args['widget_id']) )
                    $class[] = 'sb-widget';
            } else {
                $class[] = 'sb-widget';
            }
        } elseif ($is_wall) {
            $class[] = 'sb-wall';
        }

        // set iframe
        if ( ! empty($attr['iframe']) ) {
            if ($attr['iframe'] == 'media' || $attr['iframe'] == 'slide')
                $iframe = true;
            else
                $iframe = false;

            // if slideshow is active
            if ($attr['iframe'] == 'slide') {
                $slideshow = true;
                $class[] = 'sb-slideshow';
            }
        } else {
            $iframe = false;
        }
        
        // set the block height
        $block_height = ! empty($attr['height']) ? $attr['height'] : 400;

        // find layout
        if (stristr($themeoption['layout'], ' ') == TRUE) {
            $themelayouts = explode(' ', $themeoption['layout'], 2);
            $themeoption['layout'] = str_replace(' ', '_', $themeoption['layout']);
            $themeclass = $themelayouts[0].' sb-'.$themelayouts[1];
        } else {
            $themeclass = $themeoption['layout'];
        }

        // load layout
        $layouts_path = SB_DIR . '/layout/'.$themeoption['layout'].'.php';
        if ( ! file_exists($layouts_path) ) {
			$layouts_path = SB_DIR . '/custom-layouts/' . $themeoption['layout'] . '.php';
        }

        $output = $ss_output = '';
        include_once($layouts_path);
        if ( $attr['theme'] != $themeoption['layout'] )
            $class[] = 'sb-'.$themeclass;

        $layoutclass = 'ss_'.$themeoption['layout'].'_layout';
        $layoutobj = new $layoutclass;
        
        // load slide layout
        if ( isset($slideshow) ) {
            include_once( SB_DIR . '/layout/slide/default.php' );
            $slidelayoutobj = new ss_default_slidelayout;
        }
        
        if ( ! $ajax_feed) {
	        // do some styling stuffs
	        $dotboard = "#timeline_$label.sboard";
	        if ( ! empty($themeoption['social_colors']) ) {
	            $style = $layoutobj->create_colors( $themeoption['social_colors'], $filters_order, $type, $dotboard, $attr, @$themeoption[$typeoption] );
            }
        
        if ($is_wall || $is_grid) {
            $dotitem2 = '.sb-item';
            $sbitem2 = "$dotboard $dotitem2";
            $sbgutter2 = "$dotboard .sb-gsizer";
            $sbgrid2 = "$dotboard .sb-isizer";
            $gutterX = ! empty($attr['gutterX']) ? $attr['gutterX'] : ($is_grid ? 0 : 10);
            $gutterY = ! empty($attr['gutterY']) ? $attr['gutterY'] : ($is_grid ? 0 : 10);
            $itemwidth = ! empty($attr['itemwidth']) ? $attr['itemwidth'] : $setoption['wallsetting']['itemwidth'];

            // if wall is set to block
            if (@$attr['fixWidth'] == 'false' || @$attr['fixWidth'] == 'block') {
                // calculate breakpoints
                $bpsizes = array(1200, 960, 768, 600, 480, 320, 180);
                if ( ! is_array($attr['breakpoints']) ) {
                    $breakpoints = $setoption['wallsetting']['breakpoints'];
                } else {
                    $breakpoints = $attr['breakpoints'];
                    if (count($breakpoints) != count($bpsizes) ) {
                        foreach ($setoption['wallsetting']['breakpoints'] as $breakKey => $breakVal)
                            if (empty($breakpoints[$breakKey]) )
                                $breakpoints[$breakKey] = $breakVal;
                    }
                }
                foreach ($breakpoints as $bpkey => $breakpoint) {
                    if ($is_grid && $attr['columns_style'] == "1-2") {
                        $breakpoint = round( $breakpoint - ($breakpoint / 3) );
                    }

                    $gut = ($gutterX) ? number_format( ($gutterX * 100) / $bpsizes[$bpkey], 3, '.', '') : 0;
                    if ($gutterY) {
                        $yut = number_format( ($gutterY * 100) / $bpsizes[$bpkey], 3, '.', '');
                        $bpyut = number_format($bpsizes[$bpkey] / (100/$yut), 3, '.', '');
                    } else {
                        $bpyut = 0;
                    }
                    $tw = number_format(100 - ( ($breakpoint - 1) * $gut), 3, '.', '');
                    if ($tw < 100 || ! $gutterX) {
                        $bpgrid = number_format($tw / $breakpoint, 3, '.', '');
                        $bpgut = $gut;
                        $bpgridtwo = number_format( ($bpgrid * 2) + $bpgut, 3, '.', '');
                        $bpgridthree = number_format( ($bpgrid * 3) + ($bpgut * 2), 3, '.', '');
                    } else {
                        $bpgrid = 100;
                        $bpgut = 0;
                        $bpgridtwo = $bpgridthree = 100;
                    }
                    $bpcol[$bpkey] = '';
                    if (@$attr['fixWidth'] == 'false') {
                        if ($is_grid) {
                            $sbcolumn2 = "$dotboard .sb-column";
							$bpcol[$bpkey] .= "$sbcolumn2 { width: $bpgrid%; margin-bottom: {$bpyut}px; margin-right: {$bpgut}%; }
                            $sbcolumn2:nth-child({$breakpoint}n) { margin-right: 0; }";
							if ($attr['columns_style'] == "1-2") {
								$bpcol[$bpkey] .= "
                                $sbitem2 { width: 50%; height: 40%; margin-bottom: {$bpyut}px; }
                                $sbitem2.sb-twofold { width: 100%; height: 60%; }";
							}
                        } else {
                            $bpcol[$bpkey] .= "$sbitem2, $sbgrid2 { width: $bpgrid%; margin-bottom: {$bpyut}px; }
                            $sbitem2.sb-twofold { width: $bpgridtwo%; }
                            $sbitem2.sb-threefold { width: $bpgridthree%; }
                            $sbgutter2 { width: $bpgut%; }";
                        }
                    } else {
                        if ($is_grid) {
                            $sbcolumn2 = "$dotboard .sb-column";
                            $bpcol[$bpkey] .= '$("'.$sbcolumn2.'").css({ "width": "'.$bpgrid.'%", "margin-bottom": "'.$bpyut.'px", "margin-right": "'.$bpgut.'%" });
                            $("'.$sbcolumn2.':nth-child('.$breakpoint.'n)").css({ "margin-right": 0 });';
                        } else {
                            $bpcol[$bpkey] .= '$("'.$sbitem2.', '.$sbgrid2.'").css({ "width": "'.$bpgrid.'%", "margin-bottom": "'.$bpyut.'px" });
                            $("'.$sbitem2.'.sb-twofold").css({ "width": "'.$bpgridtwo.'%" });
                            $("'.$sbitem2.'.sb-threefold").css({ "width": "'.$bpgridthree.'%" });
                            $("'.$sbgutter2.'").css({ "width": "'.$bpgut.'%" });';
                        }
					}
                }
                
                if (@$attr['fixWidth'] == 'false') {
                	$mediaqueries = "$bpcol[0]
@media (min-width: 960px) and (max-width: 1200px) { $bpcol[1] }
@media (min-width: 768px) and (max-width: 959px) { $bpcol[2] }
@media (min-width: 600px) and (max-width: 767px) { $bpcol[3] }
@media (min-width: 480px) and (max-width: 599px) { $bpcol[4] }
@media (min-width: 320px) and (max-width: 479px) { $bpcol[5] }
@media (max-width: 319px) { $bpcol[6] }";
                }
                
                if ($is_grid) {
                    $grid_height = @$attr['grid_height'] ? $attr['grid_height'] : 340;
                    if ($attr['columns_style'] == "1-2") {
                        $grid_height *= 2;
                    }
                    $style[$sbcolumn2][] = 'height: '.$grid_height.'px';
                }
            } else {
                $style[$sbitem2][] = 'width: '.$itemwidth.'px';
                $style[$sbitem2][] = 'margin-bottom: '.$gutterY.'px';
            }
        } else {
            if ( ! empty($attr['carousel']) ) {
                $style["$dotboard.sb-carousel .sb-item .sb-thumb"][] = 'height: '.$attr['cs_height']['thumb'].'px';
                if ( isset($attr['cs_height']['text']) ) {
                    $style["$dotboard.sb-carousel .sb-item .sb-inner .sb-text"][] = 'height: '.$attr['cs_height']['text'].'px';

                    if (@$attr['cs_height']['thumb'])
                        $sb_expand_size = $attr['cs_height']['thumb'] + $attr['cs_height']['text'] - 30;

                    $style["$dotboard.sb-carousel .sb-item .sb-inner .sb-text.sb-expand"][] = 'height: '.$sb_expand_size.'px';
                }
                if ( isset($attr['cs_height']['meta']) ) {
                    $style["$dotboard.sb-carousel .sb-item .sb-inner .sb-metadata"][] = 'height: '.$attr['cs_height']['meta'].'px';
                    if (@$sb_expand_size)
                        $style["$dotboard.sb-carousel .sb-item .sb-nometa .sb-inner .sb-text.sb-expand"][] = 'height: '.($sb_expand_size + $attr['cs_height']['meta']).'px';
                }
                $sb_nometa_size = @$attr['cs_height']['text'] + @$attr['cs_height']['meta'];
                $style["$dotboard.sb-carousel .sb-item .sb-nometa .sb-inner .sb-text"][] = 'height: '.$sb_nometa_size.'px';
				
				/*
                //no text size
                $style["$dotboard.sb-carousel .sb-item .sb-inner .sb-no-ctext"][] = 'height: '.$sb_nometa_size.'px';

                switch (@$themeoption['layout']) {
					case "modern":
						$fix_inner_height = 104;
						$fix_slide_height = 70;
						break;
					case "modern2":
						$fix_inner_height = 130;
						$fix_slide_height = 28;
						break;
					case "metro":
						$fix_inner_height = 148;
						$fix_slide_height = 0;
						$style["$dotboard.sb-carousel .sb-item .sb-container .sb-inner .sb-inner2"][] = 'height: '. ($this->attr['cs_height']['thumb'] + $sb_nometa_size + 17) .'px';
						break;
					case "default":
						$fix_inner_height = 105;
						$fix_slide_height = 64;
						$fix_CsRows_height = ($this->attr['cs_rows'] * 8);
						break;
					case "flat":
						$fix_inner_height = 105;
						$fix_slide_height = 64;
						break;
					case "hero":
						$fix_inner_height = 0;
						$fix_slide_height = 0;
						break;
					default:
						$fix_inner_height = 0;
						$fix_slide_height = 0;
						break;
				}
                
				$slide_inner_height = $this->attr['cs_height']['thumb'] + $sb_nometa_size + $fix_inner_height;
				$style["$dotboard.sb-carousel .sb-item .sb-container .sb-inner"][] = 'height: '.$slide_inner_height.'px';

				$slide_height = $slide_inner_height + $fix_slide_height;
				$style["$dotboard.sb-carousel .sb-item"][] = 'height: '.$slide_height.'px';

				$style["$dotboard.sb-carousel"][] = 'height: '. ( ($slide_height * $this->attr['cs_rows']) + (int) @$fix_CsRows_height ) .'px';
				*/
                 
                if (@$themeoption['layout'] == "hero") {
					$style["$dotboard.sb-carousel .ax-slider__item"][] = 'height: '.$slide_height.'px';
				}
            }
        }
        
        if ( $font_size = @$themeoption['font_size'] ) {
            $style["$dotboard, $dotboard a"][] = 'font-size: '.$font_size.'px';
            $style["$dotboard .sb-heading"][] = 'font-size: '.($font_size+1).'px !important';
        }
        
        if ( $is_feed && @$themeoption[$typeoption]['title_background_color'] )
        if ( $themeoption[$typeoption]['title_background_color'] != 'transparent') {
            $style["$dotboard .sb-heading, $dotboard .sb-opener"][] = 'background-color: '.$themeoption[$typeoption]['title_background_color'].' !important';        }
        if ( $is_feed && @$themeoption[$typeoption]['title_color'] )
        if ( $themeoption[$typeoption]['title_color'] != 'transparent')
            $style["$dotboard .sb-heading"][] = 'color: '.$themeoption[$typeoption]['title_color'];
        
        if ( $is_feed )
            $csskey = "$dotboard .sb-content, $dotboard .toolbar";
        else
            $csskey = '#sb_'.$label;
            
        if ( @$themeoption[$typeoption]['background_color'] ) {
            if ( $themeoption[$typeoption]['background_color'] != 'transparent') {
                $bgexist = true;
                $style[$csskey][] = 'background-color: '.$themeoption[$typeoption]['background_color'];
            }
        }
        
        if ( $is_timeline )
            $fontcsskey = "$dotboard .timeline-row";
        else
            $fontcsskey = "$dotboard .sb-item";
            
        if (@$themeoption[$typeoption]['font_color'])
        if ($themeoption[$typeoption]['font_color'] != 'transparent') {
            $rgbColorVal = ss_hex2rgb($themeoption[$typeoption]['font_color']); // returns the rgb values separated by commas

            if ( $is_timeline ) {
                $style["$dotboard .timeline-row small"][] = 'color: '.$themeoption[$typeoption]['font_color'];
            }
            
            $style["$fontcsskey .sb-title a"][] = 'color: '.$themeoption[$typeoption]['font_color'];
            $style["$fontcsskey"][] = 'color: rgba('.$rgbColorVal.', 0.8)';
        }
        if (@$themeoption[$typeoption]['link_color'])
        if ($themeoption[$typeoption]['link_color'] != 'transparent') {
            $rgbColorVal = ss_hex2rgb($themeoption[$typeoption]['link_color']); // returns the rgb values separated by commas
            $style["$fontcsskey a"][] = 'color: '.$themeoption[$typeoption]['link_color'];
            $style["$fontcsskey a:visited"][] = 'color: rgba('.$rgbColorVal.', 0.8)';
        }

        if ( ! empty(@$themeoption[$typeoption]['background_image']) ) {
            $bgexist = true;
            $cssbgkey = $csskey;
            if ( $is_feed )
                $cssbgkey = "$dotboard .sb-content";
            $style[$cssbgkey][] = 'background-image: url('.$themeoption[$typeoption]['background_image'].');background-repeat: repeat';
        }

        $location = null;
        if ( $is_feed ) {
            $class[] = @$attr['position'];
            if ( @$attr['position'] != 'normal' ) {
                $class[] = @$attr['location'];
                if ( ! @$attr['autoclose'] ) {
                    $class[] = 'open';
                    $active = ' active';
                }
                
                $locarr = explode('_', str_replace('sb-', '', @$attr['location']) );
                $location = $locarr[0];
            }
        }

        if (@$attr['carousel'] && @$attr['tabable'])
            unset($attr['tabable']);

        if (@$attr['carousel'])
            $attr['layout_image'] = 'imgnormal';

        if (@$attr['tabable'])
            $class[] = 'tabable';

        if ( (@$attr['filters'] or @$attr['controls']) && !@$attr['carousel']) {
            $style[$dotboard.' .sb-content'][] = 'border-bottom-left-radius: 0 !important;border-bottom-right-radius: 0 !important';
        }
        if ( (@$attr['showheader'] || ($location == 'bottom' && ! @$attr['tabable']) ) && $is_feed) {
            $style[$dotboard.' .sb-content'][] = 'border-top: 0 !important;border-top-left-radius: 0 !important;border-top-right-radius: 0 !important';
        }
        if ( $location == 'left' )
            $style[$dotboard.' .sb-content'][] = 'border-top-left-radius: 0 !important';
        if ( $location == 'right' )
            $style[$dotboard.' .sb-content'][] = 'border-top-right-radius: 0 !important';
        
    	// set block border
        if ( @$themeoption[$typeoption]['border_color'] ) {
            if ( $themeoption[$typeoption]['border_color'] != 'transparent') {
                $bgexist = true;
                if ( $is_feed ) {
                    $style[$dotboard.' .toolbar'][] = 'border-top: 0 !important';
                }
                $style[$csskey][] = 'border: '.@$themeoption[$typeoption]['border_size'].'px solid '.$themeoption[$typeoption]['border_color'];
            }
        } else {
            if (@$attr['carousel'])
                $style[$dotboard.' .sb-content'][] = 'padding: 10px 0 5px 0';
        }
        
        // set block padding if required
        if (@$bgexist) {
            $border_radius = @$themeoption[$typeoption]['border_radius'];
            if ( ! $is_feed ) {
                if ($border_radius == '')
                    $border_radius = 7;
            }
            if ($border_radius != '') {
                $radius = 'border-radius: '.$border_radius.'px;-moz-border-radius: '.$border_radius.'px;-webkit-border-radius: '.$border_radius.'px';
                if ( ! $is_feed ) {
                    $style['#sb_'.$label][] = $radius;
                } else {
                    if ($location == 'bottom') {
                        $radiusval = ': '.$border_radius.'px '.$border_radius.'px 0 0;';
                        $style["$dotboard .sb-content, $dotboard.sb-widget, $dotboard .sb-heading"][] = 'border-radius'.$radiusval.'-moz-border-radius'.$radiusval.'-webkit-border-radius'.$radiusval;
                    } else {
                        $style["$dotboard .sb-content, $dotboard.sb-widget"][] = $radius;
                        $style[$dotboard.' .toolbar'][] = 'border-radius: 0 0 '.$border_radius.'px '.$border_radius.'px;-moz-border-radius: 0 0 '.$border_radius.'px '.$border_radius.'px;-webkit-border-radius: 0 0 '.$border_radius.'px '.$border_radius.'px';
                    }
                }
            }
            if ( $is_wall )
                $style['#sb_'.$label][] = 'padding: 10px';
        }
        
        if ($is_feed) {
            if (@$attr['width'] != '')
                $style["$dotboard"][] = 'width: '.$attr['width'].'px';
        }
        
        if ( @$attr['height'] != '' && ! $is_feed ) {
            $style[$csskey][] = 'height: '.$attr['height'].'px';
            if ( ! $is_feed ) {
                $style[$csskey][] = 'overflow: scroll';
                if ( $is_timeline )
                    $style[$csskey][] = 'padding-right: 0';
                $style[$dotboard][] = 'padding-bottom: 30px';
            }
        }
        } // end no ajax
        
        if ( @$theme ) {
            $class['theme'] = $attr['theme'];
        }
        
    	if ( ! $order = $attr['order'] )
            $order = 'date';
        
        $target = '';
        // nofollow links
        if (@$attr['nofollow'])
            $target .= ' rel="nofollow"';
        
        // open links in new window
        if (@$attr['links'])
            $target .= ' target="_blank"';
        $this->target = $layoutobj->target = $target;
        if ( isset($slideshow) )
            $slidelayoutobj->target = $target;
        
        // use https
        $protocol = ! empty($attr['https']) ? 'https' : 'http';
        
        if ( ! $ajax_feed)
            $output .= "\n<!-- PHP Social Stream By Axent Media -->\t";

        $GLOBALS['islive'] = false;
        if ( ! empty($attr['live']) ) {
            $GLOBALS['islive'] = true;
        }

        if ($GLOBALS['islive']) {
            // Live update need cache to be disabled
            $forceCrawl = true;
        } else {
            // If a cache time is set in the admin AND the "cache" folder is writeable, set up the cache.
            if ( $attr['cache'] > 0 && is_writable( SB_DIR . '/cache/' ) ) {
                $cache->cache_path = SB_DIR . '/cache/';
                $cache->cache_time = $attr['cache'] * 60;
        		$forceCrawl = false;
        	} else {
        		// cache is not enabled, call local class
                $forceCrawl = true;
        	}
        }

        $this->cache = $this->sbinstagram->cache = $cache;
        $this->forceCrawl = $this->sbinstagram->forceCrawl = $forceCrawl;
        $this->setoption = $setoption;

        // Create instagram instance
        $this->instagram = $this->instagram_set_proxy();

        if ($this->fetchcomments)
            return null;

        // if is ajax request
        if ( ! empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            if ( ! empty($_SESSION["$label-temp"]) ) {
            	$_SESSION[$label] = $_SESSION["$label-temp"];
                $_SESSION["$label-temp"] = array();
            }
            if (@$_REQUEST['action'] == "sb_liveupdate") {
                $_SESSION["$label-temp"] = @$_SESSION[$label];
                $_SESSION[$label] = array();
            }
        } else {
            $_SESSION[$label] = array();
            unset($_SESSION["$label-temp"]);
        }

        // Check which feeds are specified
        $feeds = array();
        $firstTab = false;
        foreach ($this->feed_keys as $value) {
        	$key = key($value);
            for ($i = 1; $i <= 6; $i++) {
        		if ( $keyitems = @$this->sboption[$key][$key.'_id_'.$i] ) {
                    foreach ($keyitems as $key2 => $eachkey) {
                        if ( (@$_REQUEST['action'] == "sb_loadmore" && @$_SESSION[$label]['loadcrawl']) && ! @$_SESSION[$label]['loadcrawl'][$key.$i.$key2])
                            $load_stop = true;
                        else
                            $load_stop = false;
                        if ( ! @$load_stop) {
                        if ( $eachkey != '') {
         			        if ( ! @$attr['tabable'] || $ajax_feed ) {
                                if ( $crawl_limit && $GLOBALS['crawled'] >= $crawl_limit )
                                    break;
                                if ( $feed_data = $this->get_feed( $key, $i, $key2, $eachkey, $results, $this->sboption[$key], $cache, $forceCrawl, $label ) ) {
                                    $filteractive = (@$attr['default_filter'] == $key) ? ' active' : '';
                                    $feeds[$key][$i][$key2] = $feed_data;
                                    if (@$value[$key])
                                    	$filterItems[$key] = ($is_feed) ? '<span class="sb-hover sb-'.$key.$filterlabel.$filteractive.'" data-filter=".sb-'.$key.'"><i class="sb-micon sb-'.$key.'"></i></span>' : '<span class="sb-hover sb-'.$key.$filterlabel.$filteractive.'" data-filter=".sb-'.$key.'"><i class="sb-icon sb-'.$key.'"></i></span>';
                                }
                            } else {
                                $activeTab = '';
                                if ( @$attr['position'] == 'normal' || (@$attr['slide'] && ! @$attr['autoclose']) ) {
                                    if ( ! isset($firstTab) ) {
                                        if ( $feed_data = $this->get_feed( $key, $i, $key2, $eachkey, $results, $this->sboption[$key], $cache, $forceCrawl, $label ) ) {
                                            $feeds[$key][$i][$key2] = $feed_data;
                                        }
                                        $firstTab = true;
                                        $activeTab = ' active';
                                    }
                                }
                                
                                $fi = '
                    			<li class="'.$key.@$activeTab.'" data-feed="'.$key.'">';
                                if (@$attr['position'] == 'normal') {
                                    $fi .= '
                                    <span><i class="sb-icon sb-'.$key.'"></i></span>';
                                } else {
                                	$fi .= '
                                    <i class="sb-icon sb-'.$key.'"></i>';
                                    if ( $location != 'bottom' )
                                        $fi .= ' <span>'.ucfirst($key).'</span>';
                                }
                    			$fi .= '</li>';
                                $filterItems[$key] = $fi;
                            }
                        }
                        }
                    }
                }
            }
        }

        // set wall custom size if defined
        if ($is_wall) {
            if (@$attr['wall_height'] != '')
            	$style['#sb_'.$label][] = 'height: '.$attr['wall_height'].'px;overflow-y: scroll';
            if (@$attr['wall_width'] != '')
            	$style['#sb_'.$label][] = 'width: '.$attr['wall_width'].'px';
		}
		
        // set timeline style class
        if ( $is_timeline ) {
            $class[] = ($attr['onecolumn'] == 'true') ? 'timeline onecol' : 'timeline';
            $class[] = 'animated';
        }
        
        if ( ! $ajax_feed) {
            if ( ! empty($attr['add_files']) ) {
                $SB_DEBUG = defined( 'SB_DEBUG' ) && SB_DEBUG && file_exists( SB_DIR . '/public/src' );
                $src = $SB_DEBUG ? 'src/' : '';

                $cssfiles = $jsfiles = '';
                if ( ! $GLOBALS['enqueue']['general']) {
                    // add css files
                    $cssfiles .= '<link href="'. $SB_PATH . '/css/colorbox.css" rel="stylesheet" type="text/css" />';
                   $cssfiles .= '<link href="'. $SB_PATH . '/css/styles.css" rel="stylesheet" type="text/css" />';

                    // load custom css file if exist
//                    if ( ! empty($themeoption['custom_css']) )
//                        if (stristr($themeoption['custom_css'], '.css') == TRUE)
//                            $cssfiles .= '<link href="'. $SB_PATH . 'custom-layouts/' . @$themeoption['custom_css'].'" rel="stylesheet" type="text/css" />';

                    // add js files
                    $jsfiles .= '<script type="text/javascript" src="'. $SB_PATH . '/js/jquery.min.js"></script>';
                    $jsfiles .= '<script type="text/javascript" src="'. $SB_PATH . '/js/sb-utils.js"></script>';
                    
                    $GLOBALS['enqueue']['general'] = true;
                }
                if ( $is_timeline ) {
                    if ( ! $GLOBALS['enqueue']['timeline']) {
                        $cssfiles .= '<link href="'.$SB_PATH . '/css/timeline-styles.css" rel="stylesheet" type="text/css" />';
                        $jsfiles .= '<script type="text/javascript" src="'.$SB_PATH . '/js/sb-timeline.js"></script>';
                        $GLOBALS['enqueue']['timeline'] = true;
                    }
                } else {
                    if ( $is_feed ) {
                        if ( ! empty($attr['carousel']) ) {
                            if ( ! $GLOBALS['enqueue']['carousel']) {
								$src_carousel = $SB_DEBUG ? 'src/carousel/' : '';
                                $cssfiles .= '<link href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" rel="stylesheet" type="text/css" />';
								$cssfiles .= '<link href="'.$SB_PATH . '/' . $src_carousel . 'css/slick.css" rel="stylesheet" type="text/css" />';
								$cssfiles .= '<link href="'.$SB_PATH . '/' . $src_carousel . 'css/slick-theme.css" rel="stylesheet" type="text/css" />';
								$jsfiles .= '<script type="text/javascript" src="'.$SB_PATH . '/js/slick.min.js"></script>';
                                $GLOBALS['enqueue']['carousel'] = true;
                            }
                        } else {
                            if ( ! $GLOBALS['enqueue']['rotating']) {
                                $jsfiles .= '<script type="text/javascript" src="'.$SB_PATH . '/js/sb-rotating.js"></script>';
                                $GLOBALS['enqueue']['rotating'] = true;
                            }
                        }
                    } else {
                        if ( ! $GLOBALS['enqueue']['wall'] && ! $is_grid ) {
							$jsfiles .= '<script type="text/javascript" src="'.$SB_PATH . '/js/sb-wall.js"></script>';
							$GLOBALS['enqueue']['wall'] = true;
                        }
                    }
                }
                
                if (@$themeoption['layout'] == "hero") {
					$cssfiles .= '<link href="'.$SB_PATH . '/' . $src . 'css/hero.css" rel="stylesheet" type="text/css" />';
				}
				if (@$themeoption['layout'] == "brick") {
					$cssfiles .= '<link href="'.$SB_PATH . '/' . $src . 'css/brick.css" rel="stylesheet" type="text/css" />';
					
					if ($attr["columns_style"] == "1-2")
						$jsfiles .= '<script type="text/javascript" src="' . $SB_PATH . '/js/brick.min.js"></script>';
				}
				
                $output .= $cssfiles . $jsfiles;
            }

            if ( ! empty($style) ) {
                $output .= '<style type="text/css">';
                if ( ! empty($themeoption['custom_css']) )
                    if (stristr($themeoption['custom_css'], '.css') === FALSE)
                        $output .= $themeoption['custom_css']."\n";

                if ( @$mediaqueries )
                    $output .= $mediaqueries."\n";
                foreach ($style as $stKey => $stItem) {
                    $output .= $stKey.'{'.implode(';', $stItem).'}';
                }
                $output .= '</style>';
            }

            if ($is_wall || $is_timeline || $is_grid)
                $output .= '<div id="sb_'.$label.'">';
                
            if ( ! $is_feed && (@$attr['position'] == 'normal' || ! $is_timeline && ! @$attr['tabable'] ) ) {
                $filter_box = '';
                if ( ! empty($attr['filtering_tabs']) ) {
					foreach ($attr['filtering_tabs'] as $filtertab) {
						$filteractive = (@$filtertab['default_filter']) ? ' active' : '';
						if (@$filtertab['search_term'])
							$filterItems[] = '<span class="sb-hover sb-filter'.$filteractive.'" data-filter="'.$filtertab['search_term'].'"><i class="sb-filter-icon">'.(@$filtertab['filter_icon'] ? '<img class="sb-img" src="' . $filtertab['filter_icon'] . '" alt="" title="'.@$filtertab['tab_title'].'">' : @$filtertab['tab_title']).'</i></span>';
					}
				}
                if ( @$attr['filters'] && @$filterItems ) {
                    if (@$attr['display_all'] != 'disabled') {
                    	$display_all = '<span class="sb-hover filter-label'.(@$attr['default_filter'] ? '' : ' active').'" data-filter="*" title="'.ss_lang('Show All').'"><i class="sb-icon sb-ellipsis-h"></i></span>';
	                    if (@$attr['display_all'] == '1')
	                    	$display_all_last = @$display_all;
	                    else
                    		$display_all_first = @$display_all;
                    }
                    $filter_box .= @$display_all_first.implode("\n", $filterItems).@$display_all_last;
                }
                if ( @$attr['filter_search'] )
                    $filter_box .= '<input type="text" class="sb-search" title="'.ss_lang('search_stream', 'social-board').'" placeholder="'.ss_lang('search', 'social-board').'" />';
                if ($filter_box) {
                    $output .= '
            		<div class="filter-items sb-'.$themeoption['layout'].'">
                        '.$filter_box.'
            		</div>';
                }
            }

            $output .= '<div' . @$attr_id . ' class="' . @implode(' ', $class) . '" data-columns>' . "\n";
            if ($is_wall) {
                $output .= '<div class="sb-gsizer"></div><div class="sb-isizer"></div>';
            }
            if ( $is_feed ) {
                if (@$attr['tabable']) {
                        $minitabs = ( count($filterItems) > 5 ) ? ' minitabs' : '';
                        $output .= '
                        <div class="sb-tabs'.$minitabs.'">
                    		<ul class="sticky" data-nonce="'.ss_nonce_create( 'tabable', $label ).'">
                            '.implode("\n", $filterItems).'
                    		</ul>
                    	</div>';
                }
                if ( $is_feed ) {
                    if ( ! @$attr['tabable'] && @$attr['slide'] ) {
                        if ( $location == 'left' || $location == 'right' ) {
                            $opener_image = (@$themeoption[$typeoption]['opener_image']) ? $themeoption[$typeoption]['opener_image'] : $SB_PATH.'public/img/opener.png';
                            $output .= '<div class="sb-opener'.@$active.'" title="'.@$attr['label'].'"><img class="sb-img" src="'.$opener_image.'" alt="" /></div>';
                        } else {
                            $upicon = '<i class="sb-arrow"></i>';
                        }
                    }
                    if ( @$attr['showheader'] || ($location == 'bottom' && ! @$attr['tabable']) )
                        $output .= '<div class="sb-heading'.@$active.'">'.@$attr['label'].@$upicon.'</div>';
                }
                
                $content_style = (!@$attr['carousel']) ? ' style="height: '.$block_height.'px"' : '';
                $output .= '<div class="sb-content"'.$content_style.'>';
				$output .=  (@$themeoption['layout'] == "hero") ? '<div id="ticker_'.$label.'" class="ax-slider ax-slider--'.$themeoption[$typeoption]["skin"].'-theme ax-slider--hover-effect-'.$themeoption[$typeoption]["hover_effect"].' ax-slider--diff">' : '<ul id="ticker_'.$label.'">' ;
                
            }
        }

        // Parsing the combined feed items and create a unique feed
        if ( ! empty($feeds) ) {
            foreach ($feeds as $feed_class => $feeditem) {
                foreach ($feeditem as $i => $feeds2) {
                foreach ($feeds2 as $ifeed => $feed) {
                // Facebook
                if ($feed_class == 'facebook') {
                    if ( ! empty($feed) ) {
                        $facebook_output = ( ! empty($this->sboption['facebook']['facebook_output']) ) ? ss_explode($this->sboption['facebook']['facebook_output']) : array('title' => true, 'thumb' => true, 'text' => true, 'comments' => true, 'likes' => true, 'user' => true, 'share' => true, 'info' => true);
                        $facebook_image_proxy = isset($this->sboption['facebook']['image_proxy']) ? $this->sboption['facebook']['image_proxy'] : false;

                    if ($i != 3 && $i != 4 && ! empty($feed['data']) ) {
                    	$feednext = $feed['next'];
                    	$feed = $feed['data'];
                    }

                    // loop through feeds
                    if (@$feed)
                    foreach ($feed as $data) {
                    if (@$data) {
                        if ($i == 3 || $i == 4) {
                            $loadcrawl[$feed_class.$i.$ifeed] = @$data->paging->cursors->after;
                            $data = $data->data;
                        }

                    // loop through feed items
                    foreach ($data as $entry) {
                        $url = $play = $text = $object_id = $source = $image2 = $mediasize = $embed = $type2 = '';

                        if (@$entry->attachments) {
                            if ($i == 1 && @$sboption['facebook_pagefeed'] != 'tagged') {
                                $entry->type = @$entry->attachments->data[0]->media_type;
                                $entry->description = @$entry->attachments->data[0]->description;
                                $entry->link = @$entry->attachments->data[0]->unshimmed_url;
                                $entry->name = @$entry->attachments->data[0]->title;
                                $entry->object_id = @$entry->attachments->data[0]->target->id;
                                $entry->source = @$entry->attachments->data[0]->media->source;
                                $entry->caption = @$entry->attachments->data[0]->title;
                            }

                            $restricted_content = @$entry->attachments->data[0]->media_type == 'link' && @$entry->attachments->data[0]->type == 'native_templates';
                        }

                        // define link
                        if ( isset($entry->permalink_url) ) {
                            $link = $entry->permalink_url;
                        } else {
                            $idparts = @explode('_', @$entry->id);
                            if ( @count($idparts) > 1 )
                                $link = 'https://www.facebook.com/'.$idparts[0].'/posts/'.$idparts[1];
                            elseif (@$entry->from->id && @$entry->id)
                                $link = 'https://www.facebook.com/'.$entry->from->id.'/posts/'.$entry->id;
                        }

                        if ( ! $link)
                            $link = @$entry->link;
                        if ( ! $link)
                            $link = @$entry->source;

                        // Fixing the links when permalink_url does not contain facebook.com
                        $link = trim($link);
                        if ( 0 !== stripos( $link, 'http' ) ) {
                            if ( 0 !== stripos( $link, '/' ) ) {
                                $link = '/'.$link;
                            }
                            $link = 'https://www.facebook.com'.$link;
                        }

                        if ( $this->make_remove($link) && empty($restricted_content) ) {
                        // body text
                        $content = array();
                        if (@$entry->message)
                            $content[] = $entry->message;
                        elseif (@$entry->description)
                            $content[] = $entry->description;
                        if (@$entry->story)
                            $content[] = $entry->story;
                        $content = implode(" \n", $content);
                        $text = (@$attr['words']) ? $this->word_limiter($content, $link) : @$this->format_text($content);
                        
                        // link hashtags
                        if ( @$text ) {
                            $text = $this->add_links('facebook', $text);
                        }

                        $meta = array();

                        // comments
                        $count = 0;
                        $meta['comments_data'] = '';
                        $comments_count = 3;
                        if ( isset($this->sboption['facebook']['facebook_comments']) )
                            $comments_count = ( @$this->sboption['facebook']['facebook_comments'] > 0 ) ? $this->sboption['facebook']['facebook_comments'] : 0;
                        $comments = is_array($entry->comments) ? $entry->comments : $entry->comments->data;
                        if ( isset($comments) && $comments_count ) {
                            foreach ( $comments as $comment ) {
                                $count++;
                                $comment_message = (@$attr['commentwords']) ? $this->word_limiter(nl2br($comment->message), @$link, true) : nl2br($comment->message);
                                $nocommentimg = empty($comment->from) ? ' sb-nocommentimg' : '';
                                $meta['comments_data'] .= '<span class="sb-meta sb-mention'.$nocommentimg.'">';
                                if ( ! @empty($comment->from) ) {
                                    $commentPicture = isset($comment->from->picture->data) ? $comment->from->picture->data : $comment->from->picture;
                                    $comment_picture = (@$commentPicture->url) ? $commentPicture->url : $protocol.'://graph.facebook.com/' . $comment->from->id . '/picture?type=square';
                                    $meta['comments_data'] .= '<img class="sb-img sb-commentimg" src="'.$comment_picture.'" alt="" /><a href="' . (@$comment->permalink_url ? $comment->permalink_url : 'https://www.facebook.com/'.$comment->from->id) . '"'.$target.'>' . @$comment->from->name . '</a> ';
                                }
                                $meta['comments_data'] .= $comment_message . '</span>';
                                if ( $count >= $comments_count ) break;
                            }
                        }

                        // likes
                        $count = 0;
                        $meta['likes_data'] = '';
                        $likes_count = 5;
                        if ( isset($this->sboption['facebook']['facebook_likes']) )
                            $likes_count = ( @$this->sboption['facebook']['facebook_likes'] > 0 ) ? $this->sboption['facebook']['facebook_likes'] : 0;
						if ( isset($entry->likes) ) {
							$likes = is_array($entry->likes) ? $entry->likes : $entry->likes->data;
							if ( isset($likes) && $likes_count ) {
								foreach ( $likes as $like ) {
									$count++;
									$like_title = (@$like->name) ? ' title="' . $like->name . '"' : '';
									$like_image = (@$like->pic) ? $like->pic : $protocol.'://graph.facebook.com/' . $like->id . '/picture?type=square';
									$meta['likes_data'] .= '<a href="' . (@$like->link ? $like->link : 'https://www.facebook.com/'.$like->id) . '"'.$target.'><img class="sb-img" src="' . $like_image . '"' . $like_title . ' alt=""></a>';
									if ( $count >= $likes_count ) break;
								}
							}

							$meta['comments_total_count'] = @$entry->comments->summary->total_count;
							$meta['likes_total_count'] = @$entry->likes->summary->total_count;
						}
                        
                        $image_width = (@$this->sboption['facebook']['facebook_image_width']) ? $this->sboption['facebook']['facebook_image_width'] : 300;
                        $source = @$entry->picture;
                        $object_id = @$entry->object_id;
                        if ($iframe) {
                            $url = $source;
                            $image_width_iframe = 800;
                        }
                        if ( ! empty($entry->images) ) {
                            if ($image_width) {
                                // get closest image width
                                $closest = null;
                                foreach ($entry->images as $image) {
                                    if ($closest === null || abs($image_width - $closest) > abs($image->width - $image_width) ) {
                                        $closest = $image->width;
                                        $source = $image->source;
                                    }
                                }
                            }
                            // set iframe image
                            if ($iframe) {
                                $closest = null;
                                foreach ($entry->images as $image2) {
                                    if ($closest === null || abs($image_width_iframe - $closest) > abs($image2->width - $image_width_iframe) ) {
                                        $closest = $image2->width;
                                        $url = $image2->source;
                                        $mediasize = $image2->width.','.$image2->height;
                                    }
                                }
                            }
                        } else {
                            if (@$entry->attachments) {
                                $type2 = @$entry->attachments->data[0]->type;
                                if ($type2 == 'multi_share') {
									$image2 = @$entry->attachments->data[0]->subattachments->data[0]->media->image;
	                                if (@$image2->src) {
	                                    $source = $image2->src;
	                                }
								} else {
	                                $image2 = @$entry->attachments->data[0]->media->image;
	                                if ( ! $image2 ) {
	                                    $image2 = @$entry->attachments->data[0]->subattachments->data[0]->media->image;
	                                } else {
		                                if ( ! @$mediasize)
		                                	$mediasize = $image2->width.','.$image2->height;
									}
                                }
                            }
                            // get or create thumb
                            if ($image_width > 180 && @$type2 != 'multi_share') {
                                if (@$entry->full_picture) {
                                    $urlArr = explode('&url=', $entry->full_picture);
                                    if ($urlfb = @$urlArr[1]) {
                                        if (stristr($urlfb, 'fbcdn') == TRUE || stristr($urlfb, 'fbstaging') == TRUE) {
                                            $source = $entry->full_picture."&w=$image_width&h=$image_width";
                                        } else {
                                            $urlfbArr = explode('&', $urlfb);
                                            if ($facebook_image_proxy) {
                                            	$token = md5(@$urlfbArr[0].@$_SERVER['SERVER_ADDR'].@$_SERVER['SERVER_ADMIN'].@$_SERVER['SERVER_NAME'].@$_SERVER['SERVER_PORT'].@$_SERVER['SERVER_PROTOCOL'].@$_SERVER['SERVER_SIGNATURE'].@$_SERVER['SERVER_SOFTWARE'].@$_SERVER['DOCUMENT_ROOT']);
                                            	$imgStr = 'resize='.$image_width.'&refresh=3600&token='.$token.'&src='.@$urlfbArr[0];
                                            	$source = SB_DIR.'/ajax.php?sbimg='.base64_encode($imgStr);
                                            } else {
                                                $source = (@$type2 != 'share') ? urldecode(@$urlfbArr[0]) : $entry->full_picture;
											}
                                        }
                                    } else {
                                        $source = $entry->full_picture;
                                    }
                                } else {
                                    if ($object_id)
                                        $source = $protocol.'://graph.facebook.com/'.$object_id.'/picture?type=normal';
                                }
                            }
                            
                            if (empty($source) ) {
                                if (@$image2->src)
                                    $source = $image2->src;
                            }
                            
                            // set iframe image
                            if ($iframe) {
                                if (@$type2 == 'multi_share' && @$image2->src) {
                                    $url = $image2->src;
                                } else {
	                                if (@$entry->full_picture) {
	                                    $url = $entry->full_picture;
	                                } else {
	                                    if ($object_id)
	                                        $url = $protocol.'://graph.facebook.com/'.$object_id.'/picture?type=normal';
                                    }
                                }
                            }
                        }
                        
                        // set video
                        if (@$entry->type == 'video' || $i == 4 || stristr($type2, 'animated_image') == TRUE) {
                            $play = true;
                            $video_width = (@$this->sboption['facebook']['facebook_video_width']) ? $this->sboption['facebook']['facebook_video_width'] : 720;
                            if (@$entry->format) {
                                // get closest video width
                                $videosize = null;
                                foreach ($entry->format as $eformat) {
                                    if (abs($eformat->width) >= abs($video_width) ) {
                                        $videosize = $eformat;
                                        $source = $eformat->picture;
                                        break;
                                    }
                                }
                                if ( ! $videosize)
                                	$videosize = end($entry->format);
                                $mediasize = $videosize->width.','.$videosize->height;
                            }
                            if ( ! @$mediasize)
                                $mediasize = '640,460';
                            if ($iframe) {
                                if (stristr($type2, 'animated_image') == TRUE) {
                                    if (stristr(@$entry->link, '.gif') == TRUE)
                                        $url = @$entry->link;
                                    else {
                                        if (stristr(@$entry->link, 'giphy.com') == TRUE) {
                                            $giphyID = substr($entry->link, strrpos($entry->link, '-') + 1);
                                            $url = 'https://i.giphy.com/'.$giphyID.'.gif';
                                        }
                                    }
                                } else {
                                    if (@$entry->status_type == 'shared_story') {
                                        $url = $entry->link;
                                    } else {
                                        $msize = explode(',', $mediasize);
                                        if (@$entry->link)
                                            $vlink = $entry->link;
                                        else {
                                            $vlink = 'https://www.facebook.com/'.(@$entry->from->id ? $entry->from->id : 'facebook').'/videos/'.$entry->id.'/';
                                        }
                                        $url = 'https://www.facebook.com/plugins/video.php?href='.urlencode($vlink).'&show_text=0&width='.$msize[0].'&height='.@$msize[1];
                                    }
                                }
								
								if (stristr(@$entry->source, 'youtube.com/embed') == TRUE) {
									$url = $entry->source;
								} else {
									// if shared from youtube without embed
									if (strpos($url, 'youtube.com') == TRUE or strpos($url, 'youtu.be') == TRUE) {
										if (stristr($url, 'youtube.com/embed') === FALSE) {
											$url = $this->youtube_get_embedurl($url) . '?rel=0&wmode=transparent';
										}
									}
								}
                            }
                        }

                        switch ($i) {
                            case 3:
                                $itemtype = 'image';
                                $type_icon = @$themeoption['type_icons'][4];
                            break;
                            case 4:
                                $itemtype = 'video-camera';
                                $type_icon = @$themeoption['type_icons'][6];
                            break;
                            default:
                                $itemtype = 'pencil';
                                $type_icon = @$themeoption['type_icons'][0];
                            break;
                        }
                        
                        // Set title
                        $title = (@$entry->name) ? $entry->name : (@$entry->title ? $entry->title : '');
                        if (empty($title) ) {
                            if (@$entry->attachments) {
                                $title = @$entry->attachments->data[0]->title;
                            }
                        }
                        if ($title == "'s cover photo") {
                            $title = (@$entry->from->name) ? @$entry->from->name . $title : '';
                        }

                        // If is a shared link and the picture is very small
                        if (@$entry->type == 'link' && ! empty($entry->link) && @$image2->width < 150) {
                            $source = '';
                            if (@$this->sboption['facebook']['facebook_embed']) {
                                $text .= $this->get_embed($entry->link, true);
                            } else {
                                $embed = '<a class="sb-html-embed" href="' . $entry->link . '" target="_blank">';
                                $embed .= '<div class="sb-embed-user">';
                                if ( ! empty($entry->picture) && @$entry->name ) {
                                    $embed .= '
                                    <div class="sb-embed-uthumb">
                                        <img class="sb-img" alt="' . $entry->name . '" src="' . $entry->picture . '">
                                    </div>';
                                }
                                $embed .= '<div class="sb-embed-uinfo">' . $entry->name . '</div>
                                </div>';
                                if ( ! empty($entry->description) )
                                    $embed .= '<span class="sb-text">' . $this->word_limiter($entry->description, $entry->link) . '</span>';
                                $embed .= '</a>';
                            }
                            $text .= $embed;
                            $title = @$entry->story ? $entry->story : '';
                        }

                        $entryPicture = isset($entry->from->picture->data) ? $entry->from->picture->data : @$entry->from->picture;

                        $thetime = (@$entry->created_time) ? $entry->created_time : $entry->updated_time;
                        $sbi = $this->make_timestr($thetime, $link);
                        $itemdata = array(
                        'title' => (@$title) ? '<a href="' . ( (@$entry->link && (stristr(@$entry->link, 'http://') == TRUE || stristr(@$entry->link, 'https://') == TRUE) ) ? $entry->link : $link) . '"'.$target.'>' . (@$attr['titles'] ? $this->title_limiter($title) : $title) . '</a>' : '',
                        'thumb' => (@$source) ? $source : '',
                        'thumburl' => $url,
                        'text' => @$text,
                        'meta' => @$meta,
                        'url' => @$link,
                        'iframe' => ($iframe && @$entry->status_type != 'shared_story') ? ( (@$entry->type == 'video' || $i == 4) ? 'iframe' : 'icbox') : '',
                        'date' => $thetime,
                        'user' => array(
                            'name' => @$entry->from->name,
                            'url' => @$entry->from->link ? $entry->from->link : (@$entry->from->id ? 'https://www.facebook.com/' . $entry->from->id : ''),
                            'image' => @$entryPicture->url ? $entryPicture->url : (@$entry->from->id ? $protocol.'://graph.facebook.com/' . $entry->from->id . '/picture?type=square' : ''),
                            // Status type
                            'status' => (@$entry->status_type) ? ( (@$GLOBALS['_'][$entry->status_type]) ? @ss_lang($entry->status_type) : ucfirst( str_replace('_', ' ', $entry->status_type) ) ) : ''
                            ),
                        'type' => $itemtype,
                        'play' => @$play,
                        'icon' => array(@$themeoption['social_icons'][0], $type_icon)
                        );
                        
							if (@$mediasize && ($iframe || isset($slideshow) ) )
								$itemdata['size'] = $mediasize;
                            $this->final[$sbi] = $layoutobj->create_item($feed_class, $itemdata, $attr, $facebook_output, $sbi, $i, $ifeed);
                            if ( isset($slideshow) ) {
                                $content = @$this->format_text($content);
                                $itemdata['text'] = $this->add_links('facebook', $content);
                                if ($url)
                                    $itemdata['thumb'] = $url;
                                $this->finalslide[$sbi] = $slidelayoutobj->create_slideitem($feed_class, $itemdata, $attr, $facebook_output, $sbi, $i, $ifeed);
                            }
                        }
                    } // end foreach
                    
                    if ($i != 3 && $i != 4) {
                        // facebook get last item date
                        if ( ! empty($feednext) )
                        	$loadcrawl[$feed_class.$i.$ifeed] = strtotime($thetime)-1;
                    }
                    
                    } // end $data
                    }
                    }
                }
                // Twitter
        		elseif ($feed_class == 'twitter') {
                    // if search/tweets
                    if ($i == 3) {
                        if ( ! empty($feed->statuses) )
                            $feed = $feed->statuses;
                    }
                    if ( ! empty($feed) ) {
                        $twitter_output = ( ! empty($this->sboption['twitter']['twitter_output']) ) ? ss_explode($this->sboption['twitter']['twitter_output']) : array('thumb' => true, 'text' => true, 'user' => true, 'share' => true, 'info' => true);
	                    foreach ($feed as $data) {
	                        if (isset($data->created_at) ) {
                                if (@$_SESSION[$label]['loadcrawl'][$feed_class.$i.$ifeed] == @$data->id_str)
                                    continue;
	                        $link = $protocol.'://twitter.com/' . $data->user->screen_name . '/status/' . @$data->id_str;
	                        if ($this->make_remove($link) ) {
                                $url = $thumb = $mediasize = $play = $meta = $embed = '';
                                $entities = (@$data->entities) ? @$data->entities : @$data->extended_entities;

	                            if ( ! $status_text = @$data->retweeted_status->text)
	                                $status_text = @$data->text;
	                            if ( ! $status_full_text = @$data->retweeted_status->full_text)
	                                $status_full_text = @$data->full_text;
	                            $text = (@$status_text) ? $status_text : $status_full_text;
	                            $text = str_replace("\n", " \n", $text);
	                            $text = (@$attr['words']) ? $this->word_limiter($text) : @$this->format_text($text);
	                        
	                        // get image
	                        if ($mediaobj = @$entities->media[0]) {
	                            $twitter_images = (@$this->sboption['twitter']['twitter_images']) ? $this->sboption['twitter']['twitter_images'] : 'small';
	                            $media_url = (@$attr['https']) ? $mediaobj->media_url_https : $mediaobj->media_url;
	                            $thumb = $media_url . ':' . $twitter_images;
	                            if ($iframe) {
	                                $url = $media_url . ':large';
	                                $mediasize = $mediaobj->sizes->large->w.','.$mediaobj->sizes->large->h;
	                            }
	                        }
	                        
	                        // get video
	                        if ($extmediaobj = @$data->extended_entities->media[0]) {
	                            if (@$extmediaobj->type == 'video' || @$extmediaobj->type == 'animated_gif') {
	                                $play = true;
	                                if ($iframe) {
			                            $twitter_videos = (@$this->sboption['twitter']['twitter_videos']) ? $this->sboption['twitter']['twitter_videos'] : 'small';
			                            if ($videosize = @$extmediaobj->sizes->$twitter_videos) {
			                                // get the video size
			                                $mediasize = $videosize->w.','.$videosize->h;
			                            }
	                                    $url = 'https://twitter.com/i/videos/tweet/' . @$data->id_str;
	                                }
	                            }
	                        }
                            
                            // Convert hashtags to links
                            // Convert shared links to embed only if there is no media tag set
                            $embed_urls = (@$this->sboption['twitter']['twitter_embed'] && @$entities->urls && empty($mediaobj) && empty($extmediaobj) ) ? $entities->urls : false;
                            $text = $this->twitter_add_links($text, $embed_urls);
                            
	                        // set user
	                        if ( ! $user = @$data->retweeted_status->user) {
	                            $user = $data->user;
	                        }
                            
                            $meta = array();
	                        if (@$data->retweeted_status) {
	                            $meta['data'] = '
	                            <span class="sb-metadata">
	                                <span class="sb-meta sb-tweet">
	                                    <a href="https://twitter.com/' . $data->user->screen_name . '"'.$target.'><i class="sb-bico sb-retweet"></i> ' . $data->user->name . ' '.ucfirst(ss_lang('retweeted') ).'</a>
	                                </span>
	                            </span>';
	                        }

                            $user_image = (@$attr['https']) ? $user->profile_image_url_https : $user->profile_image_url;

	                        $sbi = $this->make_timestr($data->created_at, $link);
	                        $itemdata = array(
	                        'thumb' => $thumb,
	                        'thumburl' => $url,
	                        'iframe' => $iframe ? (@$play ? 'iframe' : 'icbox') : '',
	                        'text' => $text,
	                        'meta' => @$meta,
	                        'share' => (@$twitter_output['share']) ? '
	                        <span class="sb-share sb-tweet">
	                            <a href="https://twitter.com/intent/tweet?in_reply_to=' . @$data->id_str . '&via=' . $data->user->screen_name . '"'.$target.'><i class="sb-bico sb-reply">Reply</i></a>
	                            <a class="retweet" href="https://twitter.com/intent/retweet?tweet_id=' . @$data->id_str . '&via=' . $data->user->screen_name . '"'.$target.'><i class="sb-bico sb-retweet">Retweet</i> ' . $data->retweet_count . '</a>
	                            <a href="https://twitter.com/intent/favorite?tweet_id=' . @$data->id_str . '"'.$target.'><i class="sb-bico sb-star-o">Favorite</i> ' . $data->favorite_count . '</a>
	                        </span>' : null,
	                        'url' => $link,
	                        'date' => $data->created_at,
	                        'user' => array(
	                            'name' => $user->screen_name,
	                            'url' => 'https://twitter.com/'.$user->screen_name,
	                            'title' => $user->name,
	                            'image' => $user_image
	                            ),
	                        'type' => 'pencil',
	                        'play' => @$play,
	                        'icon' => array(@$themeoption['social_icons'][1], @$themeoption['type_icons'][0])
	                        );
	                            
								if (@$mediasize && ($iframe || isset($slideshow) ) )
									$itemdata['size'] = $mediasize;
	                            $this->final[$sbi] = $layoutobj->create_item($feed_class, $itemdata, $attr, @$twitter_output, $sbi, $i, $ifeed);
	                            if ( isset($slideshow) ) {
	                            	$text = (@$data->text) ? $data->text : $data->full_text;
	                                $text = @$this->format_text($text);
	                                $itemdata['text'] = $this->twitter_add_links($text, $embed_urls);
	                                $itemdata['size'] = @$mediasize;
	                                if ($url)
	                                    $itemdata['thumb'] = $url;
	                                $this->finalslide[$sbi] = $slidelayoutobj->create_slideitem($feed_class, $itemdata, $attr, @$twitter_output, $sbi, $i, $ifeed);
	                            }
	                        }
	                        }
	                    } // end foreach
	                    
	                    // twitter get last id
                        if (@$_SESSION[$label]['loadcrawl'][$feed_class.$i.$ifeed] != @$data->id_str)
                            $loadcrawl[$feed_class.$i.$ifeed] = @$data->id_str;
                    }
        		}
                // Google+
                /*
                elseif ( $feed_class == 'google' ) {
                    $keyTypes = array( 'note' => array('pencil', 0), 'article' => array('edit', 1), 'activity' => array('quote-right', 2), 'photo' => array('image', 5), 'video' => array('video-camera', 6) );
                    $google_output = ( ! empty($this->sboption['google']['google_output']) ) ? ss_explode($this->sboption['google']['google_output']) : array('title' => true, 'thumb' => true, 'text' => true, 'stat' => true, 'user' => true, 'share' => true, 'info' => true);
                    
                    // google next page
                    $loadcrawl[$feed_class.$i.$ifeed] = @$feed->nextPageToken;
                    
                    if (@$feed->items) {
                    foreach ($feed->items as $item) {
                        $url = $play = $text = $textlong = $content = $contentlong = $image_url = $mediasize = '';
                        $link = @$item->url;
                        if ( $this->make_remove($link) ) {
                            // get text
                            if ($attachments = @$item->object->attachments[0]) {
                                $image_url = @$attachments->image->url;
                                if ($iframe && @$attachments->fullImage) {
                                    $url = @$attachments->fullImage->url;
                                    $mediasize = @$attachments->fullImage->width.','.@$attachments->fullImage->height;
                                }
                                if ($iframe && ! $mediasize) {
                                    $mediasize = @$attachments->image->width.','.@$attachments->image->height;
                                }
                                
                                if (@$attachments->objectType == 'photo') {
                                    if (@$attachments->displayName) {
                                        $text = (@$attr['words']) ? $this->word_limiter($attachments->displayName, $link) : $this->format_text($attachments->displayName);
                                        if ( isset($slideshow) )
                                            $textlong = @$this->format_text($attachments->displayName);
                                    }
                                } else {
                                    if (@$attachments->content)
                                        $content = (@$attr['words']) ? $this->word_limiter($attachments->content, $link) : @$this->format_text($attachments->content);
                                    $text = '<span class="sb-title"><a href="' . $attachments->url . '"'.$target.'>'.$attachments->displayName.'</a></span>'.@$content;
                                    
                                    if ( isset($slideshow) ) {
                                        if (@$attachments->content)
                                            $contentlong = @$this->format_text($attachments->content);
                                        $textlong = '<span class="sb-title"><a href="' . $attachments->url . '"'.$target.'>'.$attachments->displayName.'</a></span>'.@$contentlong;
                                    }
                                }
                                
                                if (@$attachments->objectType == 'video') {
                                    if (@$attachments->embed && $iframe) {
                                        $play = true;
                                        $url = $attachments->embed->url;
                                        // add 30% to media size
                                        $medias = explode(',', $mediasize);
                                        $mediasize = number_format($medias[0] * 1.3, 0, '.', '').','.number_format($medias[1] * 1.3, 0, '.', '');
                                    } else {
                                        $url = $image_url;
                                    }
                                }
                            } else {
                                $text = (@$attr['words']) ? $this->word_limiter($item->object->content, $link) : @$this->format_text($item->object->content);
                                if ( isset($slideshow) )
                                    $textlong = @$this->format_text($item->object->content);
                            }
                                
                        	$title = (@$attr['titles'] ? $this->title_limiter($item->title) : $item->title);

	                        if ($title || $image_url || $text) {
	                        $sbi = $this->make_timestr($item->updated, $link);
	                        $itemdata = array(
	                        'thumb' => (@$image_url) ? $image_url : '',
	                        'thumburl' => $url,
	                        'title' => '<a href="' . $link . '"'.$target.'>' . (@$attr['titles'] ? $this->title_limiter($item->title) : $item->title) . '</a>',
	                        'text' => $text,
	                        'iframe' => $iframe ? (@$play ? 'iframe' : 'icbox') : '',
	                        'meta' => (@$google_output['stat']) ? '
	                        <span class="sb-text">
	                            <span class="sb-meta">
	                                <span class="plusones">+1s ' . $item->object->plusoners->totalItems . '</span>
	                                <span class="shares"><i class="sb-bico sb-users"></i> ' . $item->object->resharers->totalItems . '</span>
	                                <span class="comments"><i class="sb-bico sb-comment"></i> ' . $item->object->replies->totalItems . '</span>
	                            </span>
	                        </span>' : null,
	                        'url' => @$link,
	                        'date' => @$item->published,
	                        'user' => array(
	                            'name' => $item->actor->displayName,
	                            'url' => $item->actor->url,
	                            'image' => @$item->actor->image->url
	                            ),
	                        'type' => $keyTypes[$item->object->objectType][0],
	                        'play' => @$play,
	                        'icon' => array(@$themeoption['social_icons'][2], @$themeoption['type_icons'][$keyTypes[$item->object->objectType][1]])
	                        );
                            
								if (@$mediasize && ($iframe || isset($slideshow) ) )
									$itemdata['size'] = $mediasize;
	                            $this->final[$sbi] = $layoutobj->create_item($feed_class, $itemdata, $attr, $google_output, $sbi, $i, $ifeed);
	                            if ( isset($slideshow) ) {
	                                $itemdata['text'] = $textlong;
	                                if ($url)
	                                    $itemdata['thumb'] = $url;
	                                $this->finalslide[$sbi] = $slidelayoutobj->create_slideitem($feed_class, $itemdata, $attr, @$google_output, $sbi, $i, $ifeed);
	                            }
                            }
                        }
                    } // end foreach
                    }
                }
                */
                elseif ( $feed_class == 'tumblr' ) {
                    $keyTypes = array( 'text' => array('pencil', 0), 'quote' => array('quote-right', 2), 'link' => array('link', 3), 'answer' => array('reply', 1), 'video' => array('video-camera', 6), 'audio' => array('youtube-play', 7), 'photo' => array('image', 5), 'chat' => array('comment', 9) );
                    $tumblr_thumb = (@$this->sboption['tumblr']['tumblr_thumb']) ? $this->sboption['tumblr']['tumblr_thumb'] : '250';
                    $tumblr_video = (@$this->sboption['tumblr']['tumblr_video']) ? $this->sboption['tumblr']['tumblr_video'] : '500';
                    
                    // tumblr next page start
                    $total_posts = @$feed->response->total_posts;
                    $loadcrawl[$feed_class.$i.$ifeed] = (@$_SESSION[$label]['loadcrawl'][$feed_class.$i.$ifeed]) ? $_SESSION[$label]['loadcrawl'][$feed_class.$i.$ifeed] + $results : $results;
                    
                    // blog info
                    $blog = $feed->response->blog;
                    
                    if (@$feed->response->posts) {
                        $tumblr_output = (@$this->sboption['tumblr']['tumblr_output']) ? ss_explode($this->sboption['tumblr']['tumblr_output']) : array('title' => true, 'thumb' => true, 'text' => true, 'user' => true, 'share' => true, 'info' => true, 'tags' => false);
                    
                    foreach ($feed->response->posts as $item) {
                        $title = $thumb = $text = $textlong = $body = $object = $tags = $url = $mediasrc = $mediasize = $play = '';
                        
                        $link = @$item->post_url;
                        if ( $this->make_remove($link) ) {
                        
                        // tags
                        if ( @$tumblr_output['tags'] ) {
                            if ( @$item->tags ) {
                                $tags = implode(', ', $item->tags);
                            }
                        }
                        
                        if ( @$item->title ) {
                            $title = '<a href="' . $link . '"'.$target.'>' . (@$attr['titles'] ? $this->title_limiter($item->title) : $item->title) . '</a>';
                        }
                        
                        // set image
                        if ($photoItem = @$item->photos[0]) {
                            if (@$photoItem->alt_sizes) {
                                foreach ($photoItem->alt_sizes as $photo) {
                                    if ($photo->width == $tumblr_thumb)
                                        $thumb = $photo->url;
                                }
                            }
                            // set iframe image
                            if ($iframe) {
                                if ($original = @$photoItem->original_size) {
                                    $url = $mediasrc = $original->url;
                                    $mediasize = $original->width.','.$original->height;
                                }
                            }
                        }
                        
                        if ($item->type == 'photo') {
                            $body = @$item->caption;
                        }
                        elseif ($item->type == 'video') {
                            $url = (@$item->video_type == 'tumblr') ? @$item->video_url : @$item->permalink_url;
                            if (@$item->thumbnail_url)
                                $thumb = $item->thumbnail_url;
                                
                            if ($iframe) {
                                // set player
                                if (@$item->player) {
                                    foreach ($item->player as $player) {
                                        if ($player->width == $tumblr_video) {
                                            $object = $player->embed_code;
											if (@$original->height) {
												$player_height = number_format( ($player->width * $original->height) / $original->width, 0, '.', '');
												$mediasize = $player->width.','.$player_height;
											}
											break;
										}
                                    }
                                }
                            }
                            $body = @$item->caption;
                            $play = true;
                        }
                        elseif ( $item->type == 'audio') {
                            $tit = @$item->artist . ' - ' . @$item->album . ' - ' . @$item->id3_title;
                            $title = '<a href="' . $link . '"'.$target.'>' . (@$attr['titles'] ? $this->title_limiter($tit) : $tit) . '</a>';
                            if ( ! @$thumb)
                                $thumb = $SB_PATH . 'public/img/thumb-audio.png';
                            $body = @$item->caption;
                            $object = @$item->embed;
                        }
                        elseif ( $item->type == 'link') {
                            $title = '<a href="' . $item->url . '"'.$target.'>' . (@$attr['titles'] ? $this->title_limiter($item->title) : $item->title) . '</a>';
                            if (@$item->excerpt)
                                $excerpt = $item->excerpt." \n";
                            $text = $body = @$excerpt.@$item->description;
                            if ( ! @$thumb)
                                $thumb = @$item->link_image;
                            if ( ! $url) {
								$url = $item->link_image;
								$mediasize = $item->link_image_dimensions->width.','.$item->link_image_dimensions->height;
							}
                        }
                        elseif ( $item->type == 'answer') {
                            $text = $body = @$item->question." \n".@$item->answer;
                        }
                        elseif ( $item->type == 'quote') {
                            if (@$item->source)
                                $source = $item->source;
                            $text = $textlong = '<span class="sb-title">'.@$item->text.'</span>'.@$source;
                        }
                        elseif ( $item->type == 'chat') {
                            $text = $body = @$item->body;
                        }
                        // type = text
                        else {
                            if ( @$item->body )
                                $text = $body = $item->body;
                            
                            // find img
                            if ( ! @$thumb) {
                                $thumbarr = $this->getsrc($body);
                                $thumb = $thumbarr['src'];
                            }
                        }
                        
                        if ($iframe) {
                            if ( ! @$url )
                                $url = @$thumb;
                        }
                        
                        if ( empty($text) )
                            $text = @$item->summary;
                        $text = (@$attr['words']) ? $this->word_limiter($text, $link) : @$this->format_text($text);
                        
                        if ( isset($slideshow) && ! empty($body) && ! @$textlong ) {
                            $textlong = @$this->format_text($body);
                        }
                        
                        $sbi = $this->make_timestr($item->timestamp, $link);
                        $itemdata = array(
                        'title' => @$title,
                        'thumb' => @$thumb,
                        'thumburl' => $url,
                        'iframe' => $iframe ? ( (@$item->type == 'video' || $item->type == 'audio') ? 'iframe' : 'icbox') : '',
                        'text' => @$text,
                        'tags' => @$tags,
                        'url' => @$link,
                        'object' => @$object,
                        'date' => $item->date,
                        'play' => @$play,
                        'user' => array(
                            'name' => $blog->name,
                            'title' => $blog->title,
                            'url' => $blog->url,
                            'image' => $protocol.'://api.tumblr.com/v2/blog/'.$blog->name.'.tumblr.com/avatar/64'
                            ),
                        'type' => $keyTypes[$item->type][0],
                        'icon' => array(@$themeoption['social_icons'][3], @$themeoption['type_icons'][$keyTypes[$item->type][1]])
                        );
							if (@$mediasize && ($iframe || isset($slideshow) ) )
								$itemdata['size'] = $mediasize;
                            $this->final[$sbi] = $layoutobj->create_item($feed_class, $itemdata, $attr, $tumblr_output, $sbi, $i, $ifeed);
                            if ( isset($slideshow) ) {
                                $itemdata['text'] = $textlong;
                                $itemdata['object'] = @$object;
                                if ($mediasrc)
                                    $itemdata['thumb'] = $mediasrc;
                                $this->finalslide[$sbi] = $slidelayoutobj->create_slideitem($feed_class, $itemdata, $attr, $tumblr_output, $sbi, $i, $ifeed);
                            }
                        }
                    }
                    }
                }
                elseif ( $feed_class == 'delicious' ) {
                    if ( ! empty($feed) ) {
                        $delicious_output = (@$this->sboption['delicious']['delicious_output']) ? ss_explode($this->sboption['delicious']['delicious_output']) : array( 'title' => true, 'text' => true, 'user' => true, 'share' => true, 'info' => true, 'tags' => false );
                    foreach ($feed as $item) {
                        $link = @$item->u;
                        if ( $this->make_remove($link) ) {
                        // tags
                        $tags = '';
                        if ( @$delicious_output['tags'] ) {
                            $tags = '';
                            if ( @$item->t ) {
                                $tags = implode(', ', $item->t);
                            }
                        }
                        
                        $sbi = $this->make_timestr($item->dt, $link);
                        $itemdata = array(
                        'title' => '<a href="' . $link . '"'.$target.'>' . (@$attr['titles'] ? $this->title_limiter($item->d) : $item->d) . '</a>',
                        'text' => (@$attr['words']) ? $this->word_limiter(@$item->n, $link) : @$this->format_text($item->n),
                        'tags' => $tags,
                        'url' => $link,
                        'date' => $item->dt,
                        'user' => array('name' => $item->a),
                        'type' => 'pencil',
                        'icon' => array(@$themeoption['social_icons'][4], @$themeoption['type_icons'][0])
                        );
                        $this->final[$sbi] = $layoutobj->create_item($feed_class, $itemdata, $attr, $delicious_output, $sbi, $i, $ifeed);
                            if ( isset($slideshow) ) {
                                $itemdata['text'] = @$this->format_text($item->n);
                                $this->finalslide[$sbi] = $slidelayoutobj->create_slideitem($feed_class, $itemdata, $attr, $delicious_output, $sbi, $i, $ifeed);
                            }
                        }
                    }
                    }
                }
                elseif ( $feed_class == 'pinterest' ) {
                    $pinterest_output = (@$this->sboption['pinterest']['pinterest_output']) ? ss_explode($this->sboption['pinterest']['pinterest_output']) : array('title' => true, 'thumb' => true, 'text' => true, 'user' => true, 'share' => true, 'info' => true);

                    $fcount = $ikey = 0;
                    $pinuser = @$feed[0]->data->user;
                    $pinuser_url = @$pinuser->profile_url;
                    $pinuser_image = str_replace('_30.', '_140.', @$pinuser->image_small_url);
                    if (@$attr['https']) {
                        $pinuser_url = str_replace('http:', 'https:', $pinuser_url);
                        $pinuser_image = str_replace('http:', 'https:', $pinuser_image);
                    }
                    
                    if ($items = @$feed[1]->channel->item)
                    foreach($items as $item) {
                        $link = @$item->link;
                        $pin = @$feed[0]->data->pins[$ikey];
                        $ikey++;
                        if ( $this->make_remove($link) ) {
                        $fcount++;
        
                        $cats = array();
                        if (@$item->category) {
                            foreach($item->category as $category) {
                                $cats[] = (string) $category;
                            }
                        }
                        
                        // fix the links in description
                        $pattern = "/(?<=href=(\"|'))[^\"']+(?=(\"|'))/";
                        if (preg_match($pattern, @$pin->description, $url1) ) {
                            $description = preg_replace($pattern, "https://www.pinterest.com$url1[0]", @$pin->description);
                        } else {
                            $description = @$pin->description;
                        }
                        
                        // find img
                        $mediasrc = $mediasize = '';
                        $image_width = (@$this->sboption['pinterest']['pinterest_image_width']) ? $this->sboption['pinterest']['pinterest_image_width'] : 237;
                        if ($thumbobj = @$pin->images->{'237x'}) {
                            $thumb = $thumbobj->url;
                            if (@$attr['https'])
                                $thumb = str_replace('http:', 'https:', $thumb);
                            $bigthumb = str_replace('237x', '736x', $thumb);
                            if ($image_width == '736')
                                $thumb = $bigthumb;
                            if ($iframe) {
                                $mediasrc = $bigthumb;
                                $newwidth = 450;
                                $newheight = number_format( ($newwidth * $thumbobj->height) / $thumbobj->width, 0, '.', '');
                                $mediasize = $newwidth.','.$newheight;
                            }
                        }
                        
                        if (@$pin->is_video && @$pin->embed && $iframe) {
                            $mediasrc = @$pin->embed->src;
                            $mediasize = (@$pin->embed->width && @$pin->embed->height) ? $pin->embed->width.','.$pin->embed->height : $newwidth.','.$newheight;
                        }

                        // add meta
                        $meta = array();
                        if (@$pinterest_output['stat']) {
                            $meta['data'] = '
                            <span class="sb-text">
                                <span class="sb-meta">
                                    <span class="shares"><i class="sb-bico sb-star-o"></i> ' . @$pin->repin_count . ' '.ucfirst(ss_lang( 'repin' ) ).'</span>
                                    <span class="comments"><i class="sb-bico sb-thumbs-up"></i> ' . @$pin->like_count . ' '.ucfirst(ss_lang( 'likes' ) ).'</span>
                                </span>
                            </span>';
                        }
                        
                        $sbi = $this->make_timestr($item->pubDate, $link);
                        $itemdata = array(
                        'title' => '<a href="' . @$pin->link . '"'.$target.'>' . (@$attr['titles'] ? $this->title_limiter($item->title) : $item->title) . '</a>',
                        'text' => (string) (@$attr['words']) ? $this->word_limiter($description, $link) : $this->format_text($description),
                        'thumb' => $thumb,
                        'thumburl' => ($mediasrc) ? $mediasrc : $link,
                        'tags' => @implode(', ', $cats),
                        'url' => $link,
                        'iframe' => $iframe ? (@$pin->is_video ? 'iframe' : 'icbox') : '',
                        'date' => $item->pubDate,
                        'meta' => @$meta,
                        'user' => array(
                            'name' => @$pinuser->full_name,
                            'url' => @$pinuser_url,
                            'image' => @$pinuser_image
                            ),
                        'type' => 'pencil',
                        'play' => (@$pin->is_video) ? true : false,
                        'icon' => array(@$themeoption['social_icons'][5], @$themeoption['type_icons'][0])
                        );
							if (@$mediasize && ($iframe || isset($slideshow) ) )
								$itemdata['size'] = $mediasize;
                            $this->final[$sbi] = $layoutobj->create_item($feed_class, $itemdata, $attr, $pinterest_output, $sbi, $i, $ifeed);
                            if ( isset($slideshow) ) {
                                $itemdata['text'] = $this->format_text($description);
                                if ($mediasrc)
                                    $itemdata['thumb'] = $mediasrc;
                                $this->finalslide[$sbi] = $slidelayoutobj->create_slideitem($feed_class, $itemdata, $attr, $pinterest_output, $sbi, $i, $ifeed);
                            }
                            
                            if ( $fcount >= $results ) break;
                        }
                    }
                }
                elseif ( $feed_class == 'flickr' ) {
                    $feed = ($i == 3) ? @$feed->photoset : @$feed->photos;

                    // flickr next page
                    $loadcrawl[$feed_class.$i.$ifeed] = @$feed->page+1;
                    
                    if (@$feed->photo) {
                        $flickr_output = (@$this->sboption['flickr']['flickr_output']) ? ss_explode($this->sboption['flickr']['flickr_output']) : array( 'title' => true, 'thumb' => true, 'text' => true, 'user' => true, 'share' => true, 'info' => true, 'tags' => false );
                        
	                    foreach ($feed->photo as $media) {
                            $owner = ($i == 3) ? @$feed->owner : $media->owner;
	                        $link = 'https://flickr.com/photos/' . $owner . '/' . $media->id;
	                        if ( $this->make_remove($link) ) {
	                        	$text = $image = $url = $tags = $mediasize = '';

		                        // tags
		                        if ( @$flickr_output['tags'] ) {
		                            if ( @$media->tags ) {
		                                $tags = $media->tags;
		                            }
		                        }

		                        if (@$attr['carousel'])
		                            $text = (@$attr['words']) ? $this->word_limiter($media->title, $link) : $media->title;
		                        
		                        $flickr_thumb = (@$this->sboption['flickr']['flickr_thumb']) ? $this->sboption['flickr']['flickr_thumb'] : 'm';
		                        $image = $protocol.'://farm' . $media->farm . '.staticflickr.com/' . $media->server . '/' . $media->id . '_' . $media->secret . '_' . $flickr_thumb . '.jpg';
		                        $author_icon = (@$media->iconserver > 0) ? $protocol.'://farm' . $media->iconfarm . '.staticflickr.com/' . $media->iconserver . '/buddyicons/' . $owner . '.jpg' : 'https://www.flickr.com/images/buddyicon.gif';
		                        if ($iframe) {
		                            $url = $protocol.'://farm' . $media->farm . '.staticflickr.com/' . $media->server . '/' . $media->id . '_' . $media->secret . '_c.jpg';
		                            $mediasize = (@$media->width_c && @$media->height_c) ? $media->width_c.','.$media->height_c : '';
		                        }
		                        
		                        $mediadate = (@$media->dateadded) ? $media->dateadded : $media->dateupload;
		                        $sbi = $this->make_timestr($mediadate, $link);
		                        $itemdata = array(
			                        'thumb' => $image,
			                        'thumburl' => @$url,
			                        'title' => '<a href="' . $link . '"'.$target.'>' . (@$attr['titles'] ? $this->title_limiter($media->title) : $media->title) . '</a>',
			                        'text' => $text,
			                        'tags' => @$tags,
			                        'iframe' => $iframe ? 'icbox' : '',
			                        'url' => $link,
			                        'date' => $media->datetaken,
			                        'user' => array(
			                            'name' => @$media->ownername,
			                            'url' => $protocol.'://www.flickr.com/people/' . $owner . '/',
			                            'image' => $author_icon
			                            ),
			                        'type' => 'image',
			                        'icon' => array(@$themeoption['social_icons'][6], @$themeoption['type_icons'][5])
		                        );
	                            $this->final[$sbi] = $layoutobj->create_item($feed_class, $itemdata, $attr, $flickr_output, $sbi, $i, $ifeed);
	                            if ( isset($slideshow) ) {
	                                $itemdata['text'] = $media->title;
	                                $itemdata['size'] = @$mediasize;
	                                if ($url)
	                                    $itemdata['thumb'] = $url;
	                                $this->finalslide[$sbi] = $slidelayoutobj->create_slideitem($feed_class, $itemdata, $attr, $flickr_output, $sbi, $i, $ifeed);
	                            }
	                        }
	                    }
                    }
                }
                // Instagram
                elseif ( $feed_class == 'instagram' ) {
                    $keyTypes = array(
                        'image' => array('camera', 5),
                        'video' => array('video-camera', 6),
                        'carousel' => array('camera', 5),
                        'sidecar' => array('camera', 5)
                    );
                    $instagram_output = (@$this->sboption['instagram']['instagram_output']) ? ss_explode($this->sboption['instagram']['instagram_output']) : array( 'title' => true, 'thumb' => true, 'text' => true, 'comments' => true, 'likes' => true, 'user' => true, 'share' => true, 'info' => true, 'tags' => false );
                    
                    // instagram next page
                    if ($i != 4) {
                        if ($i == 2) {
                            // deprecated
                            // $next_id = @$feed->pagination->next_max_tag_id;
                        } else {
                            $next_id = @$feed->pagination->next_max_id;
                        }
                        $loadcrawl[$feed_class.$i.$ifeed] = @$next_id;
                    }
                    
                    // loop in feed items
                    if (@$feed->data) {
                        foreach ($feed->data as $item) {
                            $link = $url = @$item->link;
                            if ( $this->make_remove($link) ) {
                            $thumb = $mediasrc = $mediasize = '';
                            $instagram_images = (@$this->sboption['instagram']['instagram_images']) ? $this->sboption['instagram']['instagram_images'] : 'low_resolution';
                            if (@$item->images) {
                                $thumb = $item->images->{$instagram_images}->url ? $item->images->{$instagram_images}->url : $item->images->thumbnail->url;
                            
                                // set iframe image
                                $instagram_images_iframe = 'standard_resolution';
                                if ($iframe) {
                                    $itemimages = $item->images->{$instagram_images_iframe};
                                    $url = $mediasrc = $itemimages->url ? $itemimages->url : $item->images->high_resolution->url;
                                    if (@$itemimages->width && $itemimages->height)
                                        $mediasize = $itemimages->width.','.$itemimages->height;
                                }
                            }
                            
                            if (@$item->type == 'video' && $iframe) {
                                $instagram_videos = (@$this->sboption['section_instagram']['instagram_videos']) ? $this->sboption['section_instagram']['instagram_videos'] : 'low_resolution';
                                $itemvideos = $item->videos->{$instagram_videos};
                                if (strlen($link) <= 40) {
                                    $url = $mediasrc = rtrim($link, '/\\') . '/embed/';
                                } else {
                                    // embed does not work for non-private posts
                                    $url = $mediasrc = $itemvideos->url;
                                }
                                if (@$itemvideos->width && $itemvideos->height) {
                                    $mediasize = $itemvideos->width.','.($itemvideos->height + 65);
                                } else {
                                    $mediasize = '640,640';
                                }
                            }
                            
                            // tags
                            $tags = '';
                            if ( @$instagram_output['tags'] ) {
                                if ( @$item->tags ) {
                                    $tags = implode(', ', $item->tags);
                                }
                            }
                            
                            $meta = array();

                            // comments
                            if (@$item->comments->count) {
                                if ( ! empty($item->comments->data) )
                                    $meta['comments_data'] = $this->instagram_parse_comments($item->comments->data, $link);
                                elseif ($i == 1)
                                    $meta['comments_data'] = 'fetch';
                            }

                            // likes
                            $count = 0;
                            $meta['likes_data'] = '';
                            $likes_count = 5;
                            if ( isset($this->sboption['instagram']['instagram_likes']) )
                                $likes_count = ( @$this->sboption['instagram']['instagram_likes'] > 0 ) ? $this->sboption['instagram']['instagram_likes'] : 0;
                            if ( ! empty($item->likes->data) && $likes_count ) {
                                foreach ( $item->likes->data as $like ) {
                                    $count++;
                                    $meta['likes_data'] .= '<img class="sb-img" src="' . $like->profile_picture . '" title="' . $like->full_name . '" alt="">';
                                    if ( $count >= $likes_count ) break;
                                }
                            }
                            
                            $meta['comments_total_count'] = $item->comments->count;
                            $meta['likes_total_count'] = $item->likes->count;

                            $textlong = '';
                            $text = (@$attr['words']) ? $this->word_limiter(@$item->caption->text, $link) : @$this->format_text($item->caption->text);
                            if ( isset($slideshow) )
                                $textlong = @$this->format_text($item->caption->text);
                                
                            // Add links to all hash tags
                            if ( @$item->tags ) {
                                $text = $this->add_links('instagram', $text);

                                if ( isset($slideshow) )
                                    $textlong = $this->add_links('instagram', $textlong);
                            }
                            
                            // create item
                            $sbi = $this->make_timestr($item->created_time, $link);
                            $itemdata = array(
                                'id' => @$item->id,
                                'thumb' => @$thumb,
                                'thumburl' => $url,
                                'iframe' => $iframe ? (@$item->type == 'video' ? 'iframe' : 'icbox') : '',
                                'text' => $text,
                                'meta' => @$meta,
                                'tags' => $tags,
                                'url' => $link,
                                'date' => $item->created_time,
                                'user' => array(
                                    'name' => @$item->user->username,
                                    'title' => @$item->user->full_name,
                                    'url' => 'https://instagram.com/'.@$item->user->username.'/',
                                    'image' => @$item->user->profile_picture
                                ),
                                'type' => isset($keyTypes[$item->type][0]) ? $keyTypes[$item->type][0] : 'camera',
                                'play' => (@$item->type == 'video') ? true : false,
                                'icon' => array(@$themeoption['social_icons'][7], @$themeoption['type_icons'][$keyTypes[$item->type][1]])
                            );
                                if (@$mediasize && ($iframe || isset($slideshow) ) )
                                    $itemdata['size'] = $mediasize;

                                $this->final[$sbi] = $layoutobj->create_item($feed_class, $itemdata, $attr, $instagram_output, $sbi, $i, $ifeed);
                                if ( isset($slideshow) ) {
                                    $itemdata['text'] = $textlong;
                                    if ($mediasrc)
                                        $itemdata['thumb'] = $mediasrc;
                                    $this->finalslide[$sbi] = $slidelayoutobj->create_slideitem($feed_class, $itemdata, $attr, $instagram_output, $sbi, $i, $ifeed);
                                }
                            }
                        } // end foreach

                        // next page timestamp only for /media/search
                        if ($i == 4 && ! @$next_id)
                            $loadcrawl[$feed_class.$i.$ifeed] = @$item->created_time;

                        // next page get last id only for hashtag search
                        if ($i == 2 && ! @$next_id)
                            $loadcrawl[$feed_class.$i.$ifeed] = @$item->id;
                    }
                }
                elseif ( $feed_class == 'youtube' ) {
                    // youtube next page
                    $loadcrawl[$feed_class.$i.$ifeed] = @$feed->nextPageToken;
                    
                    $youtube_output = (@$this->sboption['youtube']['youtube_output']) ? ss_explode($this->sboption['youtube']['youtube_output']) : array('title' => true, 'thumb' => true, 'text' => true, 'comments' => true, 'user' => true, 'share' => true, 'info' => true);
                    
                    if (@$feed->items)
                    foreach ($feed->items as $item) {
                        $watchID = ($i == 3) ? @$item->id->videoId : @$item->snippet->resourceId->videoId;
                        $link = $protocol.'://www.youtube.com/watch?v='.$watchID;
                        $snippet = $item->snippet;
                        if ( $this->make_remove($link) ) {
                        $dateof = @$item->contentDetails->videoPublishedAt;
                        $title = @$snippet->title;
                        $text = @$snippet->description;
                        $text = (@$attr['words']) ? $this->word_limiter(@$text, $link) : @$this->format_text($text);

                        $thumb = $mediasrc = $mediasize = '';
                        if ($iframe) {
                            $mediasrc = $protocol.'://www.youtube.com/embed/' . $watchID . '?rel=0&wmode=transparent';
                        }
                        $youtube_thumb = (@$this->sboption['youtube']['youtube_thumb']) ? $this->sboption['youtube']['youtube_thumb'] : 'medium';
                        $thumbnail = @$snippet->thumbnails->{$youtube_thumb};
                        if ( ! $thumbnail )
                            $thumbnail = @$snippet->thumbnails->{'medium'};
                        $thumb = @$thumbnail->url;
                        
                        // comments
                        $meta = array();
                        $meta['comments_data'] = 'fetch';

						// user info
						$userdata = array(
							'name' => $snippet->channelTitle,
							'url' => 'https://www.youtube.com/channel/'.$snippet->channelId
						);
						if (@$feed->userInfo->thumbnails)
							$userdata['image'] = @$feed->userInfo->thumbnails->default->url;
                            
                        $sbi = $this->make_timestr($dateof, $link);
                        $itemdata = array(
                            'id' => $watchID,
                            'thumb' => $thumb,
	                        'thumburl' => ($mediasrc) ? $mediasrc : $link,
	                        'iframe' => $iframe ? 'iframe' : '',
	                        'title' => '<a href="' . $link . '"'.$target.'>' . (@$attr['titles'] ? $this->title_limiter($title) : $title) . '</a>',
	                        'text' => $text,
	                        'url' => $link,
	                        'date' => $dateof,
                            'user' => $userdata,
                            'meta' => @$meta,
	                        'type' => 'youtube-play',
	                        'play' => true,
	                        'icon' => array(@$themeoption['social_icons'][8], @$themeoption['type_icons'][6])
                        );

                        $youtube_video = (@$this->sboption['youtube']['youtube_video']) ? $this->sboption['youtube']['youtube_video'] : '640-360';
                        $ytvsize = explode('-', $youtube_video);
                        $mediasize = "$ytvsize[0],".($ytvsize[1] + 100);
							if (@$mediasize && ($iframe || isset($slideshow) ) )
								$itemdata['size'] = $mediasize;
                            $this->final[$sbi] = $layoutobj->create_item($feed_class, $itemdata, $attr, $youtube_output, $sbi, $i, $ifeed);
                            if ( isset($slideshow) ) {
                                $itemdata['text'] = @$this->format_text(@$snippet->description);
                                if ($mediasrc)
                                    $itemdata['thumb'] = $mediasrc;
                                $this->finalslide[$sbi] = $slidelayoutobj->create_slideitem($feed_class, $itemdata, $attr, $youtube_output, $sbi, $i, $ifeed);
                            }
                        }
                    }
                }
                elseif ( $feed_class == 'vimeo' ) {
                    $vimeo_output = ( ! empty($this->sboption['vimeo']['vimeo_output']) ) ? ss_explode($this->sboption['vimeo']['vimeo_output']) : array('title' => true, 'thumb' => true, 'text' => true, 'user' => true, 'share' => true, 'info' => true);
                    
                    if ( ! empty($feed) ) {
                        // vimeo next page
                        $loadcrawl[$feed_class.$i.$ifeed] = @$feed->page+1;
                        
                        $vimeo_thumb = (@$attr['vimeo_thumb']) ? $attr['vimeo_thumb'] : '295';

                        if ($data = @$feed->data)
                        foreach ($data as $item) {
                            $link = @$item->link;
                            if ( $this->make_remove($link) ) {
                                $thumb = $mediasrc = $mediasize = '';
                                if ($pictures = @$item->pictures->sizes) {
                                    foreach ($pictures as $photo) {
                                        if ($photo->width == $vimeo_thumb) {
                                            $thumb = $photo->link;
                                            break;
                                        }
                                    }
                                }
                                
                                $title = $item->name;
                                $id = preg_replace('/\D/', '', $item->uri);
                                if ($iframe || $slideshow) {
                                    $url = $mediasrc = 'https://player.vimeo.com/video/'. $id;
                                    $mediasize = $item->width.','.$item->height;
                                } else {
                                    $url = $link;
                                }
                                
                                $datetime = (@$item->created_time) ? @$item->created_time : @$item->modified_time;
                                $connections = @$item->metadata->connections;
                                
                                $meta = array();
                                $meta['data'] = '
                                <span class="sb-text">
                                    <span class="sb-meta">
                                        <span class="likes"><i class="sb-bico sb-thumbs-up"></i> ' . @$connections->likes->total . '</span>
                                        <span class="views"><i class="sb-bico sb-play-circle"></i> ' . @$item->stats->plays . '</span>
                                        <span class="comments"><i class="sb-bico sb-comment"></i> ' . @$connections->comments->total . '</span>
                                        <span class="duration"><i class="sb-bico sb-clock-o"></i> ' . @$item->duration . ' secs</span>
                                    </span>
                                </span>';
                                $user_name = @$item->user->name;
                                $user_url = @$item->user->link;
                                $user_image = @$item->user->pictures->sizes[1]->link;

                                $sbi = $this->make_timestr($datetime, $link);
                                $itemdata = array(
                                'thumb' => @$thumb,
                                'thumburl' => @$url,
                                'iframe' => $iframe ? 'iframe' : '',
                                'title' => '<a href="' . $link . '"'.$target.'>' . (@$attr['titles'] ? $this->title_limiter($title) : $title) . '</a>',
                                'text' => (@$attr['words']) ? $this->word_limiter($item->description, $link) : $item->description,
                                'meta' => (@$vimeo_output['share']) ? $meta : null,
                                'url' => $link,
                                'date' => $datetime,
                                'user' => array(
                                    'name' => $user_name,
                                    'url' => $user_url,
                                    'image' => $user_image
                                    ),
                                'type' => 'video-camera',
                                'play' => true,
                                'icon' => array(@$themeoption['social_icons'][9], @$themeoption['type_icons'][6])
                                );
								if (@$mediasize && ($iframe || isset($slideshow) ) )
									$itemdata['size'] = $mediasize;
                                $this->final[$sbi] = $layoutobj->create_item($feed_class, $itemdata, $attr, $vimeo_output, $sbi, $i, $ifeed);
                                if ( isset($slideshow) ) {
                                    $itemdata['text'] = $item->description;
                                    $itemdata['thumb'] = $mediasrc;
                                    $this->finalslide[$sbi] = $slidelayoutobj->create_slideitem($feed_class, $itemdata, $attr, @$vimeo_output, $sbi, $i, $ifeed);
                                }
                            } // end if $link
                        } // end foreach $data
                    }
                }
                elseif ( $feed_class == 'stumbleupon' ) {
                    $stumbleupon_output = ( ! empty($this->sboption['stumbleupon']['stumbleupon_output']) ) ? ss_explode($this->sboption['stumbleupon']['stumbleupon_output']) :  array( 'title' => true, 'thumb' => true, 'text' => true, 'user' => true, 'share' => true, 'info' => true );
                    $stumbleupon_feeds = (@$this->sboption['stumbleupon']['stumbleupon_feeds']) ? ss_explode($this->sboption['stumbleupon']['stumbleupon_feeds']) : array( 'comments' => true, 'likes' => true );
                    $fcount = 0;
                    if (@$feed)
                    foreach ($feed as $dataKey => $data) {
                        if (@$stumbleupon_feeds[$dataKey]) {
                        $channel = $data->channel;
                        $items = ( $dataKey == 'likes' ) ? $channel->item : $data->item;
                        foreach($items as $item) {
                            $link = @$item->link;
                            if ( $this->make_remove($link) ) {
                            $fcount++;
                            
                            // find user
                            $pattern = ( $dataKey == 'likes' ) ? '/http:\/\/www.stumbleupon.com\/stumbler\/(\w+)/i' : '/http:\/\/www.stumbleupon.com\/stumbler\/(\w+)\/comments/i';
                            $replacement = '$1';
                            $user_name = preg_replace($pattern, $replacement, $channel->link);
                            
                            $thumb = $text = '';
                            if ($description = (string) @$item->description) {
                                if (@$attr['words']) {
                                    $thumbarr = $this->getsrc($description);
                                    $thumb = $thumbarr['src'];
                                    $text = $this->word_limiter($description, $link);
                                }
                                else {
                                    $text = $description;
                                }
                            }

                            $sbi = $this->make_timestr($item->pubDate, $link);
                            $itemdata = array(
                            'thumb' => $thumb,
                            'title' => '<a href="' . $link . '"'.$target.'>' . (@$attr['titles'] ? $this->title_limiter($item->title) : $item->title) . '</a>',
                            'text' => $text,
                            'url' => $link,
                            'date' => $item->pubDate,
                            'user' => array(
                                'name' => $user_name,
                                'url' => "https://www.stumbleupon.com/stumbler/$user_name",
                                'title' => @$channel->title
                                ),
                            'type' => ( $dataKey == 'likes' ) ? 'star-o' : 'comment-o',
                            'icon' => array(@$themeoption['social_icons'][10], @$themeoption['type_icons'][( $dataKey == 'likes' ) ? 8 : 9])
                            );
                                $this->final[$sbi] = $layoutobj->create_item($feed_class, $itemdata, $attr, $stumbleupon_output, $sbi, $i, $ifeed);
                                if ( isset($slideshow) ) {
                                    $itemdata['text'] = $description;
                                    $this->finalslide[$sbi] = $slidelayoutobj->create_slideitem($feed_class, $itemdata, $attr, $stumbleupon_output, $sbi, $i, $ifeed);
                                }
                                if ( $fcount >= $results ) break;
                            }
                        }
                        }
                    }
                }
                elseif ( $feed_class == 'deviantart' ) {
                    $deviantart_output = ( ! empty($this->sboption['deviantart']['deviantart_output']) ) ? ss_explode($this->sboption['deviantart']['deviantart_output']) :  array( 'title' => true, 'thumb' => true, 'text' => true, 'user' => true, 'share' => true, 'info' => true );
                    $fcount = 0;
                    $channel = @$feed->channel;
                    if (@$channel->item)
                    foreach($channel->item as $item) {
                        $link = @$item->link;
                        if ( $this->make_remove($link) ) {
                        $fcount++;

                        $description = $item->children('media', true)->description;
                        if ($thumbObj = @$item->children('media', true)->thumbnail->{1})
                            $thumb = @$thumbObj->attributes()->url;
                        
                        $sbi = $this->make_timestr($item->pubDate, $link);
                        $itemdata = array(
                        'thumb' => @$thumb,
                        'title' => '<a href="' . $link . '"'.$target.'>' . (@$attr['titles'] ? $this->title_limiter($item->title) : $item->title) . '</a>',
                        'text' => (@$attr['words']) ? $this->word_limiter($description, $link) : $description,
                        'tags' => '<a href="' . $item->children('media', true)->category . '"'.$target.'>' . $item->children('media', true)->category->attributes()->label . '</a>',
                        'url' => $link,
                        'date' => $item->pubDate,
                        'user' => array(
                            'name' => $item->children('media', true)->credit->{0},
                            'url' => $item->children('media', true)->copyright->attributes()->url,
                            'image' => $item->children('media', true)->credit->{1}),
                        'type' => 'image',
                        'icon' => array(@$themeoption['social_icons'][11], @$themeoption['type_icons'][4])
                        );
                        $this->final[$sbi] = $layoutobj->create_item($feed_class, $itemdata, $attr, @$deviantart_output, $sbi, $i, $ifeed);
                        if ( isset($slideshow) ) {
                            $itemdata['text'] = $description;
                            $this->finalslide[$sbi] = $slidelayoutobj->create_slideitem($feed_class, $itemdata, $attr, @$deviantart_output, $sbi, $i, $ifeed);
                        }
                        if ( $fcount >= $results ) break;
                        }
                    }
                }
                elseif ( $feed_class == 'rss' ) {
                    $rss_output = @isset($this->sboption['rss']['rss_output']) ? ss_explode($this->sboption['rss']['rss_output']) : array('title' => true, 'thumb' => true, 'text' => true, 'user' => true, 'tags' => false, 'share' => true, 'info' => true);

                    // RSS next page
                    $startP = @$_SESSION[$label]['loadcrawl'][$feed_class.$i.$ifeed];
                    $loadcrawl[$feed_class.$i.$ifeed] = $nextP = $startP + $results;
                    
                    $fcount = 0;
                    $MIMETypes = array('image/jpeg', 'image/jpg', 'image/gif', 'image/png');
                    if ( $channel = @$feed->channel ) { // rss
                        if (@$channel->item)
                        foreach($channel->item as $item) {
                            $link = @$item->link;
                            if ( $this->make_remove($link) ) {
                            	$fcount++;
                            	
                            if ($fcount > $startP) {
                            
                            $thumb = $url = '';
                            if (@$item->children('media', true)->thumbnail)
                            foreach($item->children('media', true)->thumbnail as $thumbnail) {
                                $thumb = @$thumbnail->attributes()->url;
                            }
                            if ( ! $thumb && @$item->children('media', true)->content) {
                                foreach($item->children('media', true)->content as $content) {
                                    $thumb = @$content->children('media', true)->thumbnail->attributes()->url;
                                    if ( @in_array($content->attributes()->type, $MIMETypes) )
                                        $url = @$content->attributes()->url;
                                }
                                if ( ! $thumb && $url) {
                                    $thumb = $url;
                                }
                            }
                            
                            if ( ! $thumb) {
                                if ( @in_array($item->enclosure->attributes()->type, $MIMETypes) )
                                    $thumb = @$item->enclosure->attributes()->url;
                            }
                            
                            if (@$item->category && @$rss_output['tags'])
                            foreach($item->category as $category) {
                                $cats[] = (string) $category;
                            }
                            
                            // set Snippet or Full Text
                            $text = $description = '';
                            if (@$this->sboption['rss']['rss_text'])
                                $description = @$item->description;
                            else
                                $description = (@$item->children("content", true)->encoded) ? $item->children("content", true)->encoded : @$item->description;

                            if (@$description) {
                                $description = preg_replace("/<script.*?\/script>/s", "", $description);
                                if (@$attr['words']) {
                                    if ( ! $thumb) {
                                        $thumbarr = $this->getsrc($description);
                                        $thumb = $thumbarr['src'];
                                    }
                                    $text = $this->word_limiter($description, $link);
                                } else {
                                    $text = $description;
                                }
                            }
                            if ($iframe) {
                                if ( ! $url)
                                    $url = (@$thumb) ? $thumb : '';
                            }
                            
                            // resize thumbnails
                            $image_width = @$this->sboption['rss']['rss_image_width'];
                            if (@$thumb && ! empty($image_width) ) {
                            	$token = md5(urlencode($thumb).@$_SERVER['SERVER_ADDR'].@$_SERVER['SERVER_ADMIN'].@$_SERVER['SERVER_NAME'].@$_SERVER['SERVER_PORT'].@$_SERVER['SERVER_PROTOCOL'].@strip_tags($_SERVER['SERVER_SIGNATURE']).@$_SERVER['SERVER_SOFTWARE'].@$_SERVER['DOCUMENT_ROOT']);
                            	$imgStr = 'resize='.$image_width.'&refresh=3600&token='.$token.'&src='.$thumb;
                            	$thumb = SB_DIR.'/ajax.php?sbimg='.base64_encode($imgStr);
                            }

                            $sbi = $this->make_timestr($item->pubDate, $link);
                            $itemdata = array(
	                            'thumb' => (@$thumb) ? $thumb : '',
	                            'thumburl' => $url,
	                            'title' => '<a href="' . $link . '"'.$target.'>' . (@$attr['titles'] ? $this->title_limiter($item->title) : $item->title) . '</a>',
	                            'text' => $text,
	                            'tags' => @implode(', ', $cats),
	                            'url' => $link,
	                            'iframe' => $iframe ? 'icbox' : '',
	                            'date' => $item->pubDate,
	                            'user' => array(
	                                'name' => $channel->title,
	                                'url' => $channel->link,
	                                'image' => @$channel->image->url
	                                ),
	                            'type' => 'pencil',
	                            'icon' => array(@$themeoption['social_icons'][12], @$themeoption['type_icons'][0])
                            );
	                            $this->final[$sbi] = $layoutobj->create_item($feed_class, $itemdata, $attr, $rss_output, $sbi, $i, $ifeed);
	                            if ( isset($slideshow) ) {
	                                $itemdata['text'] = @$this->format_text($description);
	                                if ($url)
	                                    $itemdata['thumb'] = $url;
	                                $this->finalslide[$sbi] = $slidelayoutobj->create_slideitem($feed_class, $itemdata, $attr, $rss_output, $sbi, $i, $ifeed);
	                            }
                            } // end start point
                            	if ($fcount >= $nextP) break;
                            } // end make remove
                        } // end foreach
                    } elseif ( $entry = @$feed->entry ) { // atom
                        // get feed link
                        if (@$feed->link)
                        foreach($feed->link as $link) {
                            if (@$link->attributes()->rel == 'alternate')
                                $user_url = @$link->attributes()->href;
                        }
                        foreach($feed->entry as $item) {
                            $link = @$item->link[0]->attributes()->href;
                            if ( $this->make_remove($link) ) {
                            	$fcount++;
                            	
                            if ($fcount > $startP) {

                            $title = (string) @$item->title;
                            $thumb = $url = '';
                            if (@$item->media)
                            foreach($item->media as $thumbnail) {
                                $thumb = @$thumbnail->attributes()->url;
                            }
                            if ( ! $thumb && @$item->link) {
                                foreach($item->link as $linkitem) {
                                    if (@$linkitem->attributes()->rel == 'enclosure') {
                                        if ( in_array(@$linkitem->attributes()->type, $MIMETypes) )
                                            $thumb = @$content->attributes()->url;
                                    }
                                }
                            }
                            
                            $cats = '';
                            if (@$item->category && @$rss_output['tags']) {
                                foreach($item->category as $category) {
                                    $cats .= @$category->attributes()->term.', ';
                                }
                                $cats = rtrim($cats, ", ");
                            }

                            // set Snippet or Full Text
                            $text = $description = '';
                            if (@$this->sboption['rss']['rss_text']) {
                                $description = (string) @$item->summary;
                            } else {
                                $content = (string) @$item->content;
                                $description = ($content) ? $content : (string) @$item->summary;
                            }
                            
                            if (@$description) {
                                if (@$attr['words']) {
                                    if ( ! $thumb) {
                                        $thumbarr = $this->getsrc($description);
                                        $thumb = $thumbarr['src'];
                                    }
                                    $text = $this->word_limiter($description, $link);
                                }
                                else {
                                    $text = $description;
                                }
                            }
                            if ($iframe)
                                $url = (@$thumb) ? $thumb : '';

                            // resize thumbnails
                            $image_width = @$this->sboption['section_rss']['rss_image_width'];
                            if (@$thumb && ! empty($image_width) ) {
                            	$token = md5(urlencode($thumb).@$_SERVER['SERVER_ADDR'].@$_SERVER['SERVER_ADMIN'].@$_SERVER['SERVER_NAME'].@$_SERVER['SERVER_PORT'].@$_SERVER['SERVER_PROTOCOL'].@strip_tags($_SERVER['SERVER_SIGNATURE']).@$_SERVER['SERVER_SOFTWARE'].@$_SERVER['DOCUMENT_ROOT']);
                            	$imgStr = 'resize='.$image_width.'&refresh=3600&token='.$token.'&src='.$thumb;
                            	$thumb = SB_DIR.'/ajax.php?sbimg='.base64_encode($imgStr);
                            }
                            
                            $sbi = $this->make_timestr($item->published, $link);
                            $itemdata = array(
                            'thumb' => @$thumb,
                            'thumburl' => $url,
                            'title' => '<a href="' . $link . '"'.$target.'>' . (@$attr['titles'] ? $this->title_limiter($title) : $title) . '</a>',
                            'text' => @$text,
                            'tags' => @$cats,
                            'url' => $link,
                            'iframe' => $iframe ? 'icbox' : '',
                            'date' => $item->published,
                            'user' => array(
                                'name' => $feed->title,
                                'url' => @$user_url,
                                'image' => @$feed->logo
                                ),
                            'type' => 'pencil',
                            'icon' => array(@$themeoption['social_icons'][12], @$themeoption['type_icons'][0])
                            );
	                            $this->final[$sbi] = $layoutobj->create_item($feed_class, $itemdata, $attr, $rss_output, $sbi, $i, $ifeed);
	                            if ( isset($slideshow) ) {
	                                $itemdata['text'] = @$this->format_text($description);
	                                if ($url)
	                                    $itemdata['thumb'] = $url;
	                                $this->finalslide[$sbi] = $slidelayoutobj->create_slideitem($feed_class, $itemdata, $attr, $rss_output, $sbi, $i, $ifeed);
	                            }
                            } // end start point
                            	if ($fcount >= $nextP) break;
                            } // end make remove
                        }
                    }
                }
                elseif ( $feed_class == 'soundcloud' ) {
					$soundcloud_output = (@$this->sboption['soundcloud']['soundcloud_output']) ? ss_explode($this->sboption['soundcloud']['soundcloud_output']) : array('title' => true, 'text' => true, 'thumb' => true, 'user' => true, 'share' => true, 'info' => true, 'meta' => true, 'tags' => false);
                    if (@$feed)
                    foreach ($feed as $item) {
                        $link = @$item->permalink_url;
                        if ( $this->make_remove($link) ) {
                        // tags
                        $tags = '';
                        if ( @$soundcloud_output['tags'] ) {
                            if (@$item->tag_list)
                                $tags .= $item->tag_list;
                        }
                        
                        // convert duration to mins
                        $duration = '';
                        if (@$item->duration) {
                            $seconds = floor($item->duration / 1000);
                            $duration = floor($seconds / 60);
                        }
                        
                        $download = '';
                        if (@$item->download_url) {
                            $download .= '<span class="download"><i class="sb-bico sb-cloud-download"></i> ' . @$item->download_count . '</span>';
                        }
                        
                        if (@$item->artwork_url) {
                        	$artwork_url = str_replace('-large', '-t300x300', $item->artwork_url);
                        } else {
							$artwork_url = '';
						}
                        
                        $meta = array();
                        $meta['data'] = '
                        <span class="sb-text">
                            <span class="sb-meta">
                                <span class="likes"><i class="sb-bico sb-thumbs-up"></i> ' . @$item->favoritings_count . '</span>
                                <span class="views"><i class="sb-bico sb-play-circle"></i> ' . @$item->playback_count . '</span>
                                <span class="comments"><i class="sb-bico sb-comment"></i> ' . @$item->comment_count . '</span>
                                <span class="duration"><i class="sb-bico sb-clock-o"></i> ' . @$duration . ' mins</span>
                                ' . $download . '
                            </span>
                        </span>';
                        
                        $sbi = $this->make_timestr($item->created_at, $link);
                        $itemdata = array(
                        'title' => '<a href="' . $link . '"'.$target.'>' . (@$attr['titles'] ? $this->title_limiter($item->title) : $item->title) . '</a>',
                        'text' => (@$attr['words']) ? $this->word_limiter(@$item->description, $link) : @$item->description,
                        'thumb' => $artwork_url,
                        'tags' => $tags,
                        'url' => $link,
                        'meta' => (@$soundcloud_output['meta']) ? $meta : null,
                        'date' => $item->created_at,
                        'user' => array(
                            'name' => $item->user->username,
                            'url' => $item->user->permalink_url,
                            'image' => $item->user->avatar_url
                            ),
                        'type' => 'youtube-play',
                        'icon' => array(@$themeoption['social_icons'][13], @$themeoption['type_icons'][7])
                        );
                        $this->final[$sbi] = $layoutobj->create_item($feed_class, $itemdata, $attr, $soundcloud_output, $sbi, $i, $ifeed);
                            if ( isset($slideshow) ) {
                                $itemdata['text'] = @$item->description;
                                $this->finalslide[$sbi] = $slidelayoutobj->create_slideitem($feed_class, $itemdata, $attr, $soundcloud_output, $sbi, $i, $ifeed);
                            }
                        }
                    }
                }
                elseif ( $feed_class == 'vk' ) {
                    if ( ! empty($feed) ) {
                        $vk_output = (@$this->sboption['vk']['vk_output']) ? ss_explode($this->sboption['vk']['vk_output']) : array( 'thumb' => true, 'text' => true, 'stat' => true, 'user' => true, 'share' => true, 'info' => true );
                        
                    // vk next page start
                    $offset = @$feed->offset;
                    $loadcrawl[$feed_class.$i.$ifeed] = ($offset == 0) ? $results : $results + $offset;
                    
                    if ($groups = @$feed->response->groups) {
                        foreach ($feed->response->groups as $group) {
                            $groupdata['-'.$group->id] = $group;
                        }
                    }
                    if ($profiles = @$feed->response->profiles) {
                        foreach ($feed->response->profiles as $profile) {
                            $userdata[$profile->id] = $profile;
                        }
                    }
                    if (@$feed->response)
                    foreach ($feed->response->items as $entry) {
                        $link = $protocol.'://vk.com/wall'.@$entry->owner_id.'_'.@$entry->id;
                        if ( $this->make_remove($link) ) {
                        
                        // body text
                        $text = @$entry->text;
                        if ( ! $text) {
                            if (@$entry->copy_history)
                                $text = $entry->copy_history[0]->text;
                        }
                        if ( isset($slideshow) ) {
                            $textlong = @$this->format_text($text);
                            $textlong = preg_replace('/#([^\s]+)/', '<a href="'.$protocol.'://vk.com/feed?section=search&q=%23$1"'.$target.'>#$1</a>', $textlong);
                        }
                        $text = (@$attr['words']) ? @$this->word_limiter($text, $link) : @$this->format_text($text);
                        // Add links to all hash tags
                        $text = preg_replace('/#([^\s]+)/', '<a href="'.$protocol.'://vk.com/feed?section=search&q=%23$1"'.$target.'>#$1</a>', $text);
                        
                        // user info
                        $user = (@$userdata[$entry->from_id]) ? $userdata[$entry->from_id] : $groupdata[$entry->from_id];
                        $user_name = (@$user->name) ? $user->name : $user->first_name.' '.$user->last_name;
                        $user_image = $user->photo_50;
                        $user_url = ($user->screen_name) ? $protocol.'://vk.com/' . $user->screen_name : $protocol.'://vk.com/id' . $entry->from_id;
                        
                        // get image
                        $image_width = (@$this->sboption['vk']['vk_image_width']) ? $this->sboption['vk']['vk_image_width'] : '604';
                        $attachments = @$entry->attachments;
                        if ( ! $attachments) {
                            if (@$entry->copy_history)
                                $attachments = @$entry->copy_history[0]->attachments;
                        }
                        $source = $iframe2 = $play = $url = $mediasrc = $mediasize = '';
                        if ( ! empty($attachments) ) {
                            if ($image_width) {
                                foreach ($attachments as $attach) {
                                    if ($attach->type == 'photo') {
                                        $photo_width = "photo_$image_width";
                                        if ( ! @$attach->photo->{$photo_width} ) {
                                            $source = $this->vk_get_photo(@$attach->photo);
                                        } else {
                                            $source = @$attach->photo->{$photo_width};
                                        }
                                        if ($iframe) {
                                            $iframe2 = $iframe;
                                            $photo_width_iframe = "photo_1280";
                                            if ( ! @$attach->photo->{$photo_width_iframe} ) {
                                                $url = $mediasrc = $this->vk_get_photo(@$attach->photo);
                                            } else {
                                                $url = $mediasrc = @$attach->photo->{$photo_width_iframe};
                                                if ($attach->photo->width)
                                                    $mediasize = $attach->photo->width.','.$attach->photo->height;
                                            }
                                        }
                                        break;
                                    } elseif ($attach->type == 'link') {
                                        $source = (@$attach->link->image_big) ? $attach->link->image_big : @$attach->link->image_src;
                                        $url = @$attach->link->url;
                                        break;
                                    } elseif ($attach->type == 'video') {
                                        $play = true;
                                        $source = ($image_width <= 130) ? @$attach->video->photo_130 : @$attach->video->photo_320;
                                        break;
                                    } elseif ($attach->type == 'doc') {
                                        $source = $this->vk_get_photo(@$attach->doc);
                                        break;
                                    }
                                }
                            }
                        }
                        
                        $meta = array();
                        $meta['data'] = (@$vk_output['stat']) ? '
                        <span class="sb-text">
                            <span class="sb-meta">
                                <span class="likes"><i class="sb-bico sb-thumbs-up"></i>' . $entry->likes->count . '</span>
                                <span class="shares"><i class="sb-bico sb-retweet"></i> ' . $entry->reposts->count . '</span>
                                <span class="comments"><i class="sb-bico sb-comment"></i> ' . $entry->comments->count . '</span>
                            </span>
                        </span>' : null;
                        
                        $sbi = $this->make_timestr($entry->date, $link);
                        $itemdata = array(
                        'thumb' => (@$source) ? $source : '',
                        'thumburl' => $url,
                        'text' => @$text,
                        'meta' => (@$vk_output['stat']) ? @$meta : '',
                        'url' => @$link,
                        'iframe' => @$iframe2 ? 'icbox' : '',
                        'date' => $entry->date,
                        'user' => array(
                            'name' => @$user_name,
                            'url' => $user_url,
                            'image' => @$user_image
                            ),
                        'type' => 'pencil',
                        'play' => @$play,
                        'icon' => array(@$themeoption['social_icons'][14], ($i == 2) ? @$themeoption['type_icons'][4] : @$themeoption['type_icons'][0] )
                        );
                        	$this->final[$sbi] = $layoutobj->create_item($feed_class, $itemdata, $attr, $vk_output, $sbi, $i, $ifeed);
                            if ( isset($slideshow) ) {
                                $itemdata['text'] = @$textlong;
                                $itemdata['size'] = @$mediasize;
                                if ($mediasrc)
                                    $itemdata['thumb'] = $mediasrc;
                                $this->finalslide[$sbi] = $slidelayoutobj->create_slideitem($feed_class, $itemdata, $attr, $vk_output, $sbi, $i, $ifeed);
                            }
                        }
                    } // end foreach
                    } // end $feed
                }
        		elseif ( $feed_class == 'linkedin' ) {
                    if (@$feed->elements) {
                        $linkedin_output = ( ! empty($this->sboption['linkedin']['linkedin_output']) ) ? ss_explode($this->sboption['linkedin']['linkedin_output']) : array('title' => true, 'thumb' => true, 'text' => true, 'comments' => true, 'likes' => true, 'user' => true, 'share' => true, 'info' => true);
                    
                    // linkedin next page
                    $loadcrawl[$feed_class.$i.$ifeed] = (@$feed->paging->start) ? @$feed->paging->start + @$feed->paging->count : $results;
                    
                    foreach ( $feed->elements as $data ) {
                        if ( @$data->lifecycleState == 'PUBLISHED' && isset($data->created) ) {
                        
                        $link = 'https://www.linkedin.com/feed/update/'.$data->id.'/';
                        if ( $this->make_remove($link) ) {
                        
                        $url = $thumb = $mediasrc = $title = $longtext = '';
                        $specificContent = $data->specificContent;
                        $share = $specificContent->{'com.linkedin.ugc.ShareContent'};
                        
                        if (@$share->media[0]->title->text) {
                            $titleurl = (@$share->media[0]->originalUrl) ? $share->media[0]->originalUrl : $link;
                            $title = '<a href="' . $titleurl . '"'.$target.'>' . (@$attr['titles'] ? $this->title_limiter($share->media[0]->title->text) : $share->media[0]->title->text) . '</a>';
                        }
                        
                        if (@$share->shareCommentary->text)
                            $longtext .= $share->shareCommentary->text;
                        $text = (@$attr['words']) ? $this->word_limiter($longtext) : @$this->format_text($longtext);
                        
                        $meta = array();
                        
                        // comments
                        $count = 0;
                        $meta['comments_data'] = '';
                        $comments_count = ( @$this->sboption['linkedin']['linkedin_comments'] > 0 ) ? $this->sboption['linkedin']['linkedin_comments'] : 0;
                        if (@$data->commentsContent)
                        if ( ! empty($data->commentsContent->elements) && $comments_count ) {
                            foreach ( $data->commentsContent->elements as $comment ) {
                                if ( ! @$comment->message)
                                    continue;
                                $count++;
                                $comment_message = (@$attr['commentwords']) ? $this->word_limiter(nl2br($comment->message->text), @$link, true) : nl2br($comment->message->text);
                                if (@$comment->{'actor~'}->localizedName)
                                    $comment_title = $comment->{'actor~'}->localizedName;
                                $comment_user_url = (@$comment->{'actor~'}->id) ? 'https://www.linkedin.com/company/'.$comment->{'actor~'}->id : '';
                                $comment_actor_image = @$comment->{'actor~'}->logoV2->{'cropped~'}->elements[0]->identifiers[0]->identifier;
                                $comment_user_img = (@$comment_actor_image) ? '<img class="sb-img sb-commentimg" src="' . $comment_actor_image . '" alt="" />' : '';
                                $comment_no_img = ( ! $comment_user_img) ? ' sb-nocommentimg' : '';
                                $meta['comments_data'] .= '<span class="sb-meta sb-mention'.$comment_no_img.'">'.$comment_user_img.'<a href="' . $comment_user_url . '"'.$target.'>' . @$comment_title . '</a> ' . $comment_message . '</span>';
                                if ( $count >= $comments_count ) break;
                            }
                        }
                        // likes
                        $count = 0;
                        $meta['likes_data'] = '';
                        $likes_count = ( @$this->sboption['linkedin']['linkedin_likes'] > 0 ) ? $this->sboption['linkedin']['linkedin_likes'] : 0;
                        if (@$data->likesContent)
                        if ( ! empty($data->likesContent->elements) && $likes_count ) {
                            $like_title = array();
                            foreach ( $data->likesContent->elements as $like ) {
                                if ( ! @$like->{'actor~'})
                                    continue;
                                $count++;
                                $like_title = (@$like->{'actor~'}->localizedName) ? ' title="' . $like->{'actor~'}->localizedName . '"' : '';
                                $like_user_url = (@$like->{'actor~'}->id) ? 'https://www.linkedin.com/company/'.$like->{'actor~'}->id : '';
                                $like_actor_image = @$like->{'actor~'}->logoV2->{'cropped~'}->elements[0]->identifiers[0]->identifier;
                                $like_image = (@$like_actor_image) ? '<img src="' . $like_actor_image . '"' . $like_title . ' alt="">' : @$like->{'actor~'}->localizedName;
                                $meta['likes_data'] .= '<a href="' . $like_user_url . '"'.$target.'>'.$like_image.'</a>';
                                if ( $count >= $likes_count ) break;
                            }
                        }
                        
                        $meta['comments_total_count'] = @$data->commentsContent->paging->total;
                        $meta['likes_total_count'] = @$data->likesContent->paging->total;
                        
                        // get image
                        if ( ! empty($share->media) ) {
                            if (@$share->media[0]->thumbnails[0]->url)
                                $thumb = $share->media[0]->thumbnails[0]->url;
                            
                            if ($iframe && @$thumb) {
                                $url = $mediasrc = $thumb;
                            } else {
                                if (@$share->media[0]->originalUrl)
                                    $url = $share->media[0]->originalUrl;
                            }
                        }

                        $authorImage = @$data->{'author~'}->logoV2->{'cropped~'}->elements[0]->identifiers[0]->identifier;
                        
                        // create item
                        $sbi = $this->make_timestr($data->created->time, $link);
                        $itemdata = array(
                        'title' => $title,
                        'thumb' => $thumb,
                        'thumburl' => $url,
                        'iframe' => $iframe ? 'icbox' : '',
                        'text' => $text,
                        'url' => $link,
                        'meta' => @$meta,
                        'date' => $data->created->time,
                        'user' => array(
                            'name' => @$data->{'author~'}->localizedName,
                            'url' => 'https://www.linkedin.com/company/'.$data->{'author~'}->id.'/',
                            'image' => @$authorImage ? $authorImage : ''
                            ),
                        'type' => 'pencil',
                        'icon' => array(@$themeoption['social_icons'][1], @$themeoption['type_icons'][0])
                        );
                        $this->final[$sbi] = $layoutobj->create_item($feed_class, $itemdata, $attr, $linkedin_output, $sbi, $i, $ifeed);
                            if ( isset($slideshow) ) {
                                $itemdata['text'] = $longtext;
                                if ($mediasrc)
                                    $itemdata['thumb'] = $mediasrc;
                                $this->finalslide[$sbi] = $slidelayoutobj->create_slideitem($feed_class, $itemdata, $attr, $linkedin_output, $sbi, $i, $ifeed);
                            }
                        }
                        }
                    } // end foreach
                    }
        		} // end linkedin
        		elseif ( $feed_class == 'vine' ) {
                    if (@$feed->data->records) {
                        $vine_output = ( ! empty($this->sboption['vine']['vine_output']) ) ? ss_explode($this->sboption['vine']['vine_output']) : array('title' => true, 'thumb' => true, 'text' => true, 'comments' => true, 'likes' => true, 'user' => true, 'share' => true, 'info' => true);
                    
                    // vine next page
                    $loadcrawl[$feed_class.$i.$ifeed] = @$feed->data->nextPage;
                    
                    foreach ( $feed->data->records as $data ) {
                        if ( isset($data->created) ) {
                        
                        $link = $data->permalinkUrl;
                        if ( $this->make_remove($link) ) {
                        
                        $url = $thumb = $object = $text = '';
                        if (@$data->description) {
                            $text = (@$attr['words']) ? $this->word_limiter($data->description) : @$this->format_text($data->description);
                            $text = preg_replace('/#([\\d\\w]+)/', '<a href="https://vine.co/tags/$1">$0</a>', $text);
                        }

                        $meta = array();
                        
                        // comments
                        $count = 0;
                        $meta['comments_data'] = '';
                        $comments_count = ( @$attr['comments'] > 0 ) ? $attr['comments'] : 0;
                        if ($comments = $data->comments)
                        if ( ! empty($comments->records) && $comments_count ) {
                            foreach ( $comments->records as $comment ) {
                                if ( ! @$comment->comment)
                                    continue;
                                $count++;
                                $comment_message = (@$attr['commentwords']) ? $this->word_limiter(nl2br($comment->comment), @$link, true) : nl2br($comment->comment);
                                $comment_user_img = (@$comment->avatarUrl) ? '<img class="sb-img sb-commentimg" src="' . $comment->avatarUrl . '" alt="" />' : '';
                                $meta['comments_data'] .= '<span class="sb-meta sb-mention">'.$comment_user_img.'<a href="https://vine.co/u/' . $comment->userId . '"'.$target.'>' . @$comment->username . '</a> ' . $comment_message . '</span>';
                                if ( $count >= $comments_count ) break;
                            }
                        }
                        // likes
                        $count = 0;
                        $meta['likes_data'] = '';
                        $likes_count = ( @$attr['likes'] > 0 ) ? $attr['likes'] : 0;
                        if (@$data->likes)
                        if ( ! empty($data->likes->records) && $likes_count ) {
                            $like_title = array();
                            foreach ( $data->likes->records as $like ) {
                                if ( ! @$like->username)
                                    continue;
                                $count++;
                                $like_title[] = '<a href="https://vine.co/u/'.@$like->userId.'" title="'.@$like->created.'"'.$target.'>'.$like->username.'</a>';
                                if ( $count >= $likes_count ) break;
                            }
                            $meta['likes_data'] .= implode(', ', $like_title);
                        }
                        
                        $meta['comments_total_count'] = @$data->likes->count;
                        $meta['likes_total_count'] = @$data->comments->count;
                        
                        // get image
                        if (@$data->thumbnailUrl)
                            $thumb = (@$attr['https']) ? str_replace('http:', 'https:', $data->thumbnailUrl) : $data->thumbnailUrl;
                        
                        $url = $link;
                        if ($iframe && @$data->shareUrl) {
                            $object = '<iframe src="'.$data->shareUrl.'/embed/simple" width="600" height="600" frameborder="0"></iframe><script src="https://platform.vine.co/static/scripts/embed.js"></script>';
                            $play = true;
                        }
                        
                        $sbi = $this->make_timestr($data->created, $link);
                        $itemdata = array(
                        'thumb' => $thumb,
                        'thumburl' => $url,
                        'iframe' => $iframe ? 'iframe' : '',
                        'text' => $text,
                        'url' => $link,
                        'meta' => @$meta,
                        'date' => $data->created,
                        'user' => array(
                            'name' => $data->username,
                            'url' => 'https://vine.co/u/' . $data->userId,
                            'image' => (@$attr['https']) ? str_replace('http:', 'https:', $data->avatarUrl) : $data->avatarUrl
                            ),
                        'type' => 'play-circle',
                        'play' => @$play,
                        'icon' => array(@$themeoption['social_icons'][1], @$themeoption['type_icons'][0])
                        );
							$mediasize = '560,460';
							if (@$mediasize && ($iframe || isset($slideshow) ) )
								$itemdata['size'] = $mediasize;
                            $this->final[$sbi] = $layoutobj->create_item($feed_class, $itemdata, $attr, $vine_output, $sbi, $i, $ifeed);
                            if ( isset($slideshow) ) {
                                $itemdata['text'] = $data->description;
                                if ($object)
                                    $itemdata['object'] = $object;
                                $this->finalslide[$sbi] = $slidelayoutobj->create_slideitem($feed_class, $itemdata, $attr, $vine_output, $sbi, $i, $ifeed);
                            }
                        }
                        }
                    } // end foreach
                    }
        		} // end vine

				$final = $this->final;

                // each network sorting
                if ( ! empty($final) ) {
                    if ( $order == 'date' )
                        krsort($final);
                    elseif ( $order == 'date_asc' )
                        ksort($final);

                    reset($final);
                    $ifeedclass = $feed_class.$i.$ifeed;
                    
                    if ( ! empty($loadmore) ) {
                        // filter last items
                        if ( $lastloaditem = @$loadmore[$ifeedclass] ) {
                            $final_keys = array_keys($final);
                            $loadremovefrom = array_search( $lastloaditem, $final_keys );
                            if ( ! @$loadremovefrom)
                                $loadremovefrom = 0;
                            if ( empty($_SESSION[$label]['loadcrawl'][$ifeedclass])
                                || $final_keys[$loadremovefrom] == $_SESSION[$label]['loadmore'][$ifeedclass] )
                                $loadremovefrom++;
                            $final = array_slice($final, $loadremovefrom);
                        }
                    }
                    
                    $finals[$ifeedclass] = $final;
                    $rankcount[$ifeedclass] = count($final);
					
					if ( ! empty( $attr['custom_results'] ) ) {
						if ( $ifeed_limit = (int) @$attr['network'][$feed_class][$feed_class.'_id_'.$i.'_results'][$attr['network'][$feed_class][$feed_class.'_id_'.$i][$ifeed]] ) {
							$fresults[$ifeedclass] = $ifeed_limit;
						} else {
							$fresults[$ifeedclass] = $attr['custom_results_default'];
						}
					} else {
						$ranking[key($final)] = $ifeedclass;
					}
                }
                $final = $this->final = array();
                
                } // end foreach
                }
            } // end foreach $feeds

			if ( empty( $attr['custom_results'] ) ) {
				if (@$ranking) {
					// defining limits by recent basis
					krsort($ranking);
					$rsum = 0;
					$rnum = count($ranking);
					for ($i = 1; $i <= $rnum; $i++) {
						$rsum += $i;
					}
					$i = $rnum;
					foreach ($ranking as $cfeed) {
						$rank[$cfeed] = number_format(($i * 100) / $rsum, 0, '.', '');
						$i--;
					}
				}
				if (@$rankcount) {
					$maxcountkey = array_search(max($rankcount), $rankcount);
					foreach ($rankcount as $rkey => $rval) {
						$fresults[$rkey] = @number_format($rank[$rkey] * $results / 100, 0, '.', '');
					}
					foreach ($rankcount as $rkey => $rval) {
						if ($fresults[$rkey] > $rval) {
							$diffrankcount = $fresults[$rkey] - $rval;
							$fresults[$rkey] -= $diffrankcount;
							$fresults[$maxcountkey] += $diffrankcount;
						}
					}
				}
			}
            
            if ( @$finals ) {
                // filnal sorting and adding
                $rescount = 0;
                foreach ($finals as $fkey => $fval) {
                    $fcount = 0;
                    // limit last result
                    foreach ($fval as $key => $val) {
                        $rescount++;
                        $fcount++;
                        $final[$key] = $val;
                        $loadmore[$fkey] = $key;
                        if ( $fcount >= $fresults[$fkey] || $rescount == $results ) break;
                    }
                }

                // set next pages if exist
                if ( array_sum($rankcount) <= $results && ! $is_feed && ( ! $GLOBALS['islive'] || @$_REQUEST['action'] == "sb_loadmore" ) ) {
                    foreach ($rankcount as $rkey => $rval) {
                        if (@$loadcrawl[$rkey])
                            $_SESSION[$label]['loadcrawl'][$rkey] = $loadcrawl[$rkey];
                        else
                            $_SESSION[$label]['loadcrawl'][$rkey] = null;
                    }
                }
                
                if ( $order == 'date' )
                    krsort($final);
                elseif ( $order == 'date_asc' )
                    ksort($final);
                elseif ( $order == 'random' )
                    $final = ss_shuffle_assoc($final);
                    
                // get related ads
                if ($adposts = @$GLOBALS['ads'][$id]) {
	                foreach ($adposts as $adKey => $adData) {
	                    $ad_position = (@$adData['ad_position']) ? $adData['ad_position'] : 0;
	                    $ads[$ad_position][] = $adData;
	                }
                }

                // Output board items
                if (@$attr['loadmore'] && ! $ajax_feed) {
                    $_SESSION[$label]['loadcount'] = 0;
                }

                $display_ads = (@$attr['display_ads']) ? true : false;
                $i = (@$_SESSION[$label]['loadcount']) ? $_SESSION[$label]['loadcount'] : 0;
                foreach ($final as $key => $val) {
                    if ( ! empty($ads[$i]) && $display_ads) {
                        foreach ($ads[$i] as $ad) {
                            $adstyle = '';
                            if (@$ad['ad_height'])
                                $adstyle .= 'height: '.$ad['ad_height'].'px;';
                            if ( isset($ad['ad_border_size']) ) {
                                $adstyle .= 'border-style: solid;';
                                $adstyle .= 'border-width: '.$ad['ad_border_size'].'px;';
                            }
                            if (@$ad['ad_border_color'] && @$ad['ad_border_size'])
                                $adstyle .= 'border-color: '.$ad['ad_border_color'].';';
                            if (@$ad['ad_background_color'])
                                $adstyle .= 'background-color: '.$ad['ad_background_color'].';';
                            if (@$ad['ad_text_align'])
                                $adstyle .= 'text-align: '.$ad['ad_text_align'].';';
                            if ($ad['ad_type'] == 'image')
                                $adstyle .= 'line-height: 0;';
                            $adtag = ($type != 'feed' || @$attr['carousel']) ? 'div' : 'li';
                            $adgrid = (@$ad['ad_grid_size']) ? ($ad['ad_grid_size'] == 'solo' ? '' : ' sb-'.$ad['ad_grid_size']) : '';
                            // get ad content
                            $adcontent = '';
                            switch ($ad['ad_type']) {
                                case "text":
                                    $adinnerstyle = '';
                                    if (@$ad['ad_border_size']) {
                                        $border_radius = 5+$ad['ad_border_size']-1;
                                        $adinnerstyle .= 'border-radius: '.$border_radius.'px;-moz-border-radius: '.$border_radius.'px;-webkit-border-radius: '.$border_radius.'px;';
                                    }
                                    $adinnerstyle .= 'background-color: transparent !important;';
                                    $adcontent = '<div class="sb-inner"'.(@$adinnerstyle ? ' style="'.$adinnerstyle.'"' : '').'>'.nl2br($ad['ad_text']).'</div>';
                                break;
                                case "code":
                                    $adcontent = $ad['ad_custom_code'];
                                break;
                                case "image":
                                    if (@$ad['ad_text_align']) {
                                        if ($ad['ad_text_align'] == 'left')
                                            $admargin = 'margin-right: auto;';
                                        elseif ($ad['ad_text_align'] == 'right')
                                            $admargin = 'margin-left: auto;';
                                        else
                                            $admargin = 'margin: auto;';
                                    }
                                    if ( ! empty($ad['ad_image']) ) {
                                        $ad_link_target = (@$ad['ad_link_target'] == 'blank') ? ' target="_blank"' : '';
                                        $ad_image = '<img class="sb-img" src="'.$ad['ad_image'].'"'.(@$admargin ? ' style="'.$admargin.'max-height: 100%;"' : '').'>';
                                        $adcontent = ! empty($ad['ad_link']) ? '<a href="'.$ad['ad_link'].'"'.$ad_link_target.'>'.$ad_image.'</a>' : $ad_image;
                                    }
                                break;
                            }
                            if ( $is_timeline ) {
                                $adout = '
                                <div class="timeline-row">
                                    <div class="timeline-icon">
                                      <div class="bg-ad">
                                        <i class="sb-bico sb-wico sb-star"></i>
                                      </div>
                                    </div>
                                    <div class="timeline-content">
                                      <div class="panel-body sb-item sb-advert">
                                        <div class="sb-container"'.(@$adstyle ? ' style="'.$adstyle.'"' : '').'>';
                                        $adout .= $adcontent;
                                        $adout .= '
                                        </div>
                                      </div>
                                    </div>
                                </div>' . "\n";
                            } else {
                                $adout1 = '
                                <'.$adtag.' class="sb-item sb-advert'.$adgrid.'">
                                    <div class="sb-container"'.(@$adstyle ? ' style="'.$adstyle.'"' : '').'>';
                                    $adout1 .= $adcontent;
                                $adout1 .= '
                                    </div>
                                </'.$adtag.'>' . "\n";
                                $adout = (@$attr['carousel']) ? '<li>'.$adout1.'</li>' : $adout1;
                            }
                            $output .= $adout;
                        }
                    }
                    $i++;
                    
                    if ( method_exists($layoutobj, 'filter_item') )
                        $val = @$layoutobj->filter_item($val);
                    $output .= $val;
                    
                    if ( isset($slideshow) ) {
                        $ss_output .= $this->finalslide[$key];
                    }
                } // end foreach $final
                if (@$attr['loadmore']) {
                    $_SESSION[$label]['loadcount'] = $i;
                }
            } else {
                if ( empty($loadmore) )
                    $output_error = '<p class="sboard-nodata"><strong>PHP Social Stream:</strong> There is no feed data to display!</p>';
            }
        } else {
            if ( empty($loadmore) )
                $output_error = '<p class="sboard-nodata"><strong>PHP Social Stream: </strong>There is no feed to show!</p>';
        }

        if (@$attr['loadmore']) {
            $_SESSION[$label]['loadmore'] = $loadmore;
        }
        
        if ($ajax_feed && $is_feed) {
            if (@$output_error)
                $output .= $output_error;
        }
        
    	if ( ! $ajax_feed) {
            if ( $is_feed ) {
                if (@$output_error) {
                    $output .= $output_error;
                }
				$output .= (@$themeoption['layout'] == "hero") ? "</div></div>" : "</ul></div>";
                
                if ( ! @$output_error) {
                    if ( ! @$attr['carousel']) {
                        if (@$attr['autostart']) {
                            $play_none = ' style="display: none;"';
                        } else {
                            $pause_none = ' style="display: none;"';
                        }
                        $controls = (@$attr['controls']) ? '
                        <div class="control">
                            <span class="sb-hover" id="ticker-next-'.$label.'"><i class="sb-bico sb-wico sb-arrow-down"></i></span>
                            <span class="sb-hover" id="ticker-prev-'.$label.'"><i class="sb-bico sb-wico sb-arrow-up"></i></span>
                            <span class="sb-hover" id="ticker-pause-'.$label.'"'.@$pause_none.'><i class="sb-bico sb-wico sb-pause"></i></span>
                            <span class="sb-hover" id="ticker-play-'.$label.'"'.@$play_none.'><i class="sb-bico sb-wico sb-play"></i></span>
                        </div>' : '';
                        
                    $filters = '';
                    if ( ! @$attr['tabable'] && @$filterItems && ! empty($feeds) ) {
                        $filters = (@$attr['filters']) ? '
                        <div class="filter">
                            <span class="sb-hover'.(@$attr['default_filter'] ? '' : ' active').'" data-filter="all"><i class="sb-bico sb-wico sb-ellipsis-h" title="'.ss_lang( 'show_all' ).'"></i></span>
                            '.implode("\n", $filterItems).'
                        </div>' : '';
                    }
                    
                        if (@$attr['filters'] or @$attr['controls'])
                        $output .= '
                        <div class="toolbar">
                            '.$controls.'
                            '.$filters.'
                        </div>'."\n";
                    }
                }
            }
        }
        
        if ($is_wall || $is_timeline || $is_grid) {
            if (@$output_error) {
                $output = str_replace(' timeline ', ' ', $output);
                $output .= $output_error;
            }
        }

        if ( ! $ajax_feed) {
        $output .= "</div>\n";
        $loadmoretxt = (@$attr['loadmore'] == 2) ? '' : '<p>'.ss_lang( 'load_more' ).'</p>';
        $loadmorecls = (@$attr['loadmore'] == 2) ? ' sb-infinite' : '';
        if ( ( ! $is_feed && ! @$output_error ) && @$attr['loadmore'] )
            $output .= '<div class="sb-loadmore'.$loadmorecls.'" data-nonce="'.ss_nonce_create( 'loadmore', $label ).'">'.$loadmoretxt.'</div>'."\n";
        if ($is_wall || $is_timeline || $is_grid)
            $output .= "</div>\n";
            
        $iframe_output = $iframe_slideshow = $iframe_media = '';
        if (@$attr['slideshow']) {
            $slideOptions = 'slideshow: true, slideshowSpeed: "'.@$attr['slideshowSpeed'].'",';
        }
        if (@$attr['iframe'] == 'slide') {
            $iframe_output = $iframe_slideshow = '
                $(".sb-inline").colorbox({
                    inline: true,
                    rel: "sb-inline",
                    href: function(){
                      return $(this).data("href");
                    },
                    '.@$slideOptions.'
                    maxHeight: "95%",
					width: "85%",
                    current: "slide {current} of {total}",
                    onComplete: function() {
                        var href, attrwidth, aspectratio, newheight = "";
						var winCurrentWidth = $(window).width();
                        var winCurrentHeight = $(window).height();
                        href = $(this).data("href");
						if (winCurrentWidth >= 768) {
							thumbimg = $(href + " .sb-inner .sb-thumb img," + href + " .sb-inner .sb-thumb iframe");
							attrwidth = thumbimg.attr("width");
							if (!attrwidth) {
								sizearrY = thumbimg.height();
								sizearrX = thumbimg.width();
								if (sizearrY) {
								    var gapHeight = Math.round((winCurrentHeight * 5) / 100);
								    var currentHeight = winCurrentHeight-gapHeight-30;
    								if (currentHeight < sizearrY) {
    									var newheight = currentHeight;
    									
    									aspectratio = sizearrX * newheight;
    									newwidth = Math.round(aspectratio / sizearrY);
    									sizearrX = newwidth;
    									sizearrY = newheight;
    									
    									thumbimg.height(newheight);
    								} else {
    									var newheight = "500";
    								}
    								$(href + " .sb-inner .sb-body").innerHeight(newheight);
    								
    								if (thumbimg.height() > 500) {
    									thumbimg.height(newheight);
    								}
								}
								$(this).colorbox.resize({innerHeight:newheight});
							}
                        }
                        /* Auto-trigger comments */
                        if ( $(href + " .sb-fetchcomments a.sb-triggercomments").length )
                            $(href + " .sb-fetchcomments a.sb-triggercomments").trigger("click");
                    },
                    onLoad:function(){
                        $(".sb-slide .sb-thumb").empty();
                        var sizestr, href, inner, type, media, size = "";
						var wsize = sb_getwinsize();
						var bheight = (wsize.newHeight < 500) ? wsize.newHeight : 500;
                        href = $(this).data("href");
                        inner = $(href + " .sb-inner");
                        type = inner.data("type");
                        if (type) {
                            media = inner.data("media");
                            size = inner.data("size");
                            sizearr = size.split(",");
                            sizearrX = sizearr[0];
                            sizearrY = sizearr[1];
							thumb = inner.children(".sb-thumb");
							newConWidth = Math.round((wsize.newWidth * 70) / 100);
                            
							if ( (sizearrX && sizearrY) && (sizearrX > 400 || sizearrY > 400) ) {
								if (wsize.winCurrentWidth > 768) {
									if (sizearrY < 400) {
										thumb.width("50%");
										inner.children(".sb-body").width("50%").children(".sb-slide-footer").width("50%");
									}
									
									if (wsize.winCurrentHeight < sizearrY || newConWidth < sizearrX) {
										aspectratio = sizearrX * wsize.newHeight;
										sizearrX = Math.round(aspectratio / sizearrY);
										sizearrY = wsize.newHeight;
										
										if (sizearrX > newConWidth) {
											aspectratio = sizearrY * newConWidth;
											sizearrY = Math.floor(aspectratio / sizearrX);
											sizearrX = newConWidth;
											$(href + " .sb-inner .sb-body").innerHeight(sizearrY);
										} else {
											$(href + " .sb-inner .sb-body").innerHeight(wsize.newHeight);
										}
									} else {
										if (sizearrY && sizearrY > 400) {
											$(href + " .sb-inner .sb-body").innerHeight(sizearrY);
										}
									}
								} else {
									if (wsize.newWidth < sizearrX) {
										aspectratio = sizearrY * wsize.newWidth;
										sizearrY = Math.round(aspectratio / sizearrX);
										sizearrX = wsize.newWidth;
									} else if (newConWidth < sizearrX) {
										aspectratio = sizearrY * newConWidth;
										sizearrY = Math.round(aspectratio / sizearrX);
										sizearrX = newConWidth;
                                    }
                                    $(href + " .sb-inner .sb-body").innerHeight("auto");
								}
							} else {
								sizestr = "";
								thumb.width("50%");
								inner.children(".sb-body").width("50%").children(".sb-slide-footer").width("50%");
							}
							
                            if (type == "image") {
                                if ( (sizearrX && sizearrY) && (sizearr[0] > 400 || sizearr[1] > 400) ) {
                                    sizestr = " style=\'width:" + sizearrX + "px;height:" + sizearrY + "px\' width=\'" + sizearr[0] + "\' height=\'" + sizearr[1] + "\'";
                                    thumb.html("<img class=\"sb-img\" src=\"" + media + "\"" + sizestr + " alt=\"\">");
                                } else {
                                    thumb.html("<span><img src=\"" + media + "\" class=\"sb-img sb-imgholder\" alt=\"\"></span>");
                                }
                            } else if (type == "video") {
                                if ( (sizearrX && sizearrY) && (sizearrX > 400 || sizearrY > 400) ) {
                                        sizestr = " style=\'width:" + sizearrX + "px;height:" + sizearrY + "px\' width=\'" + sizearr[0] + "\' height=\'" + sizearr[1] + "\'";
                                } else {
                                    sizestr = " width=\'560\' height=\'315\'";
                                }
                                var imedia = "<iframe" + sizestr + " src=\"" + media + "\" allowfullscreen=\"\" webkitallowfullscreen=\"\" mozallowfullscreen=\"\" autoplay=\"0\" wmode=\"opaque\" frameborder=\"0\"></iframe>";
								if (sizearr[1] && sizearr[1] > 400) {
									thumb.html(imedia);
								} else {
									thumb.html("<span>" + imedia + "</span>");
									$(href + " .sb-inner .sb-body").innerHeight(bheight);
								}
                            } else {
                                if (sizearrY && sizearrY > 400) {
									thumb.html(media);
								} else {
									thumb.html("<span>" + media + "</span>");
									if (wsize.winCurrentWidth > 768)
										$(href + " .sb-inner .sb-body").innerHeight(bheight);
								}
                            }
                        } else {
							$(href + " .sb-inner .sb-body").innerHeight(bheight);
						}
                    },
                    onClosed: function() {
                        $(".sb-slide .sb-thumb").empty();
                    }
                });';
            } else {
                $iframe_output = $iframe_media = '
				$(".sboard .sb-thumb .iframe").colorbox({
                    iframe: true,
                    '.@$slideOptions.'
                    maxWidth: "85%",
                    maxHeight: "95%",
					width: function() {
                        var size = $(this).data("size");
                        if (size) {
                            sizearr = size.split(",");
				            return parseInt(sizearr[0])+10;
                        } else {
                            return 640;
                        }
					},
					height: function() {
                        var size = $(this).data("size");
                        if (size) {
                            sizearr = size.split(",");
                            return parseInt(sizearr[1])+10;
                        } else {
                            return 460;
                        }
					},
					onComplete: function() {
						var size = $(this).data("size");
                        if (size) {
    						var sizearr = size.split(",");
    						var iframebox = $( "#cboxLoadedContent iframe" );
    						if (iframebox.length) {
    							iframebox.attr("width", sizearr[0]).attr("height", sizearr[1]);
    						}
                        }
					}
				});
				$(".sboard .sb-thumb .icbox , .sboard .icbox").colorbox({
                    photo: true,
                    href: function() {
                        return $(this).attr("href") ? $(this).attr("href") : $(this).data("href");
                    },
                    maxWidth: "95%",
                    maxHeight: "95%"
                });
				$(".sboard .sb-thumb .inline").colorbox({
                    inline: true,
                    maxWidth: "95%",
                    maxHeight: "95%"
                });';
            }
        
        // Lazy load images
        $lazyload_output = '';
        $layout_container = (@$attr['wall_height'] != '') ? '"#sb_'.$label.'"' : 'window';
        if (@$attr['lazyload']) {
            $lazyload_output = '
                $(".sb-thumb img").lazyload({
                    effect: "fadeIn",
                    skip_invisible: true,';
                if (@$attr['wall_height'] != '')
                    $lazyload_output .= 'container: $("#sb_'.$label.'"),';
                $lazyload_output .= '
                    appear: function() {
                        $wall.isotope("layout");
                    }
                });';
        }
            
        // loadmore ajax function
        $more_output = '';
        if (@$attr['loadmore']) {
            if (@$attr['loadmore'] == 2) {
                $fail_func = 'console.log';
                $more_output .= '
                var sbwin = $(window);
                /* Each time the user scrolls */
                sbwin.scroll(function() {
                  /* End of the document reached? */
                  if ( $(document).height() - sbwin.height() <= Math.ceil(sbwin.scrollTop()) ) {';
            } else {
                $fail_func = 'alert';
                $more_output .= '
                $("#sb_'.$label.'").on("click", ".sb-loadmore", function() {';
            }
            $lazyload_output_append = (@$attr['lazyload']) ? str_replace('$(".sb-thumb img")', 'lmdata.find(".sb-thumb img")', $lazyload_output) : '$('.$layout_container.').trigger("resize");';
            $more_output .= '
                lmobj = $("#sb_'.$label.' .sb-loadmore");
                lmnonce = lmobj.attr("data-nonce");';
            $more_output .= "$('#sb_".$label." .sb-loadmore').html('<p class=\"sb-loading\">&nbsp;</p>');";
            $more_output .= '
                $.ajax({
                type: "GET",
                url: "social-wall",
                data: {
                    action: "sb_loadmore",
                    attr: '.$attr_ajax.',
                    nonce: lmnonce,
                    label: "'.$label.'"
                },
                cache: false
                })
                .done(function( response ) {
                    /* append and layout items */';
                if ( $is_wall ) {
                    $more_output .= '
                    var lmdata = $(response);
                    var $items = lmdata.filter(".sb-item");
                    var $slides = lmdata.filter(".sb-slide");
                    $wall.append( $items ).isotope( "appended", $items );
                    $("#sb_slides_'.$label.'").append( $slides );
                    $(window).one("transitionend webkitTransitionEnd oTransitionEnd otransitionend MSTransitionEnd", function(e) {
                        '.$lazyload_output_append.'
                        $wall.one( "layoutComplete", function() {
                            $('.$layout_container.').trigger("resize");
                        });
                        $wall.isotope("layout");
                    });';
                } elseif ( $is_grid ) {
                    $more_output .= '
                    $("#timeline_'.$label.'").append(response).find(".sb-thumb img").lazyload();
                    $('.$layout_container.').trigger("resize");';
                } else {
                    $more_output .= '
					$("#timeline_'.$label.'").append(response).find(".sb-thumb img").lazyload();
					$('.$layout_container.').trigger("scroll");';
                }
                $more_output .= $iframe_output . '
                    $("#sb_'.$label.' .sb-loadmore").html("'.$loadmoretxt.'");
                })
                .fail(function() {
                    '.$fail_func.'("Problem reading the feed data!");
                });';
                if (@$attr['loadmore'] == 2) {
                    $more_output .= '
                    }';
                }
            $more_output .= '
            });';
        }
        
        // Fetch comments ajax function
        $more_output .= '
        jQuery(".sboard").on("click", ".sb-fetchcomments a.sb-triggercomments", function() {';
        $more_output .= '
            fcobj = $(this).parent();
            fcnonce = fcobj.attr("data-nonce");';
        $more_output .= "fcobj.html('<p class=\"sb-loading\">&nbsp;</p>');";
        $more_output .= '
            $.ajax({
            type: "GET",
            url: "social-wall",
            data: {
                action: "sb_fetchcomments",
                network: fcobj.attr("data-network"),
                attr: '.$attr_ajax.',
                id: fcobj.attr("data-id"),
                feed: fcobj.attr("data-feed"),
                link: fcobj.attr("data-link"),
                nonce: fcnonce,
                label: "'.$label.'"
            },
            cache: false
            })
            .done(function( response ) {
                /* replace comments */';
            if ( $is_wall ) {
                $more_output .= '
                /* re-layout wall items */
                fcobj.html(response).promise().done(function() {
                    $wall.isotope("layout");
                    $('.$layout_container.').trigger("scroll");
                });';
            } else {
                $more_output .= '
                fcobj.html(response);';
            }
            $more_output .= '
            })
            .fail(function() {
                fcobj.html(\'<a href="javascript:void(0)" class="sb-triggercomments">'.ss_lang( 'show_comments' ).'</a>\');
                alert("Problem reading the comments feed data!");
            });';
        $more_output .= '
        });';
        // END: fetch comments ajax function

        $output .= '
        <script type="text/javascript">';
        if ( ! empty($attr['add_files']) )
            $output .= 'jQuery.noConflict();';
        $output .= '
            jQuery(document).ready(function($) {
				function sb_getwinsize() {
					var wsize = {
						winCurrentWidth: $(window).width(),
						newWidth: 0,
						winCurrentHeight: $(window).height(),
						newHeight: 0
					};
					var gapWidth = Math.round((wsize.winCurrentWidth * 15) / 100);
					var currentWidth = wsize.winCurrentWidth-gapWidth;
					wsize.newWidth = currentWidth-10;
					
					var gapHeight = Math.round((wsize.winCurrentHeight * 5) / 100);
					var currentHeight = wsize.winCurrentHeight-gapHeight;
					wsize.newHeight = currentHeight-30;
					return wsize;
				}';
             
        $ticker_id_t = '';
        if ( $is_feed ) {
            if (@$attr['carousel']) {
                if ( ! @is_array($attr['cs_item']) ) {
                    if ($attr['cs_item'] == '')
                        $attr['cs_item'] = $setoption['carouselsetting']['cs_item'];
                    else
                        $attr['cs_item'] = explode(',', trim($attr['cs_item']) );
				}
				
				$output .= '
				var slider_options = {
                    accessibility: true,
                    waitForAnimate: false,
                    variableWidth: false,
                    initialSlide: 0,
                    adaptiveHeight: false,
                    infinite: '.(@$attr['cs_loop'] ? 'true' : 'false').',
                    dots: '.(@$attr['cs_pager'] ? 'true' : 'false').',
                    arrows: '.(@$attr['cs_controls'] ? 'true' : 'false').',
                    speed: '.@$attr['cs_speed'].',
                    autoplay: '.(@$attr['cs_auto'] ? 'true' : 'false').',
                    autoplaySpeed: '.@$attr['cs_autospeed'].',
                    pauseOnHover: '.(@$attr['cs_pause'] ? 'true' : 'false').',
                    rows: '.@$attr['cs_rows'].',
                    slidesPerRow: 1,
                    slidesToShow: '.@$attr['cs_item'][0].',
                    slidesToScroll: '.@$attr['cs_item'][0].',
                    rtl: '.@$attr['cs_rtl'].',
                    lazyLoad: "ondemand",
                    prevArrow: "<button type=\'button\' class=\'slick-prev ax-slider__arrow\'>></button>",
                    nextArrow: "<button type=\'button\' class=\'slick-next ax-slider__arrow\'><</button>",';

					$output .= '
                    /* responsive options */
                    respondTo: "slider",
                    responsive: [{
                        breakpoint: 960,
                        settings: {
                          slidesToShow: '.@$attr['cs_item'][1].',
                          slidesToScroll: '.@$attr['cs_item'][1].'
                        }
                    }, {
                        breakpoint: 768,
                        settings: {
                          slidesToShow: '.@$attr['cs_item'][2].',
                          slidesToScroll: '.@$attr['cs_item'][2].'
                        }
                    }, {
                        breakpoint: 600,
                        settings: {
                            slidesToShow: '.@$attr['cs_item'][3].',
                            slidesToScroll: '.@$attr['cs_item'][3].'
                        }
                    }, {
                        breakpoint: 480,
                        settings: {
                            slidesToShow: '.@$attr['cs_item'][4].',
                            slidesToScroll: '.@$attr['cs_item'][4].'
                        }
                    }]
                };
                $("#ticker_'.$label.'").slick(slider_options);';
                    
            
                // if (@$attr['lazyload']) {
                //     $output .= '
                //     $("#ticker_'.$label.' .sb-thumb .sb-crop").lazyload({
                //         effect: "fadeIn",
                //         skip_invisible: true,
                //         threshold: '.$block_height.',
                //         placeholder: "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iLTIzIC0yMyA4NCA4NCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiBzdHJva2U9IiNmZmYiPiAgICA8ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPiAgICAgICAgPGcgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMSAxKSIgc3Ryb2tlLXdpZHRoPSIyIj4gICAgICAgICAgICA8Y2lyY2xlIHN0cm9rZS1vcGFjaXR5PSIuNSIgY3g9IjE4IiBjeT0iMTgiIHI9IjE4Ii8+ICAgICAgICAgICAgPHBhdGggZD0iTTM2IDE4YzAtOS45NC04LjA2LTE4LTE4LTE4Ij4gICAgICAgICAgICAgICAgPGFuaW1hdGVUcmFuc2Zvcm0gICAgICAgICAgICAgICAgICAgIGF0dHJpYnV0ZU5hbWU9InRyYW5zZm9ybSIgICAgICAgICAgICAgICAgICAgIHR5cGU9InJvdGF0ZSIgICAgICAgICAgICAgICAgICAgIGZyb209IjAgMTggMTgiICAgICAgICAgICAgICAgICAgICB0bz0iMzYwIDE4IDE4IiAgICAgICAgICAgICAgICAgICAgZHVyPSIxcyIgICAgICAgICAgICAgICAgICAgIHJlcGVhdENvdW50PSJpbmRlZmluaXRlIi8+ICAgICAgICAgICAgPC9wYXRoPiAgICAgICAgPC9nPiAgICA8L2c+PC9zdmc+"
                //     });';
                // }
            }

            if ( ! @$attr['carousel']) {
                $ticker_id = '#ticker_'.$label;
                
                $ticker_lazyload_output = '';
                if (@$attr['lazyload']) {
                    $ticker_lazyload_output = '
                    $(".sb-thumb img").lazyload({
                        effect: "fadeIn",
                        skip_invisible: true,';
                    if (@$attr['wall_height'] != '')
                        $ticker_lazyload_output .= 'container: $("'.$ticker_id.'"),';
                    $ticker_lazyload_output .= '
                        threshold: '.($block_height * 2).',
                        failure_limit: '.$results.'
                    });';
                    
                    $output .= '
                    function sb_tickerlazyload() {
                        var lielem = $("'.$ticker_id.' li:last-child");
                        var lix = lielem.index();
                        for (i = 0; i < 4; i++) {
                            var inum = lix-i;
                            var imgelem = $("'.$ticker_id.' li").eq(inum).find(".sb-thumb img");
                            if (typeof imgelem.attr("data-original") !== "undefined" && imgelem.attr("data-original") !== null)
                                $( "img[data-original=\'"+imgelem.attr("data-original")+"\']" ).trigger("appear");
                        }
                    }';
                }
            
                $output .= '
                var $sbticker = $("'.$ticker_id.'").newsTicker({
                    row_height: '.$block_height.',
                    max_rows: 1,
                    speed: '.@$attr['rotate_speed'].',
                    duration: '.@$attr['duration'].',
                    direction: "'.@$attr['direction'].'",
                    autostart: '.@$attr['autostart'].',
                    pauseOnHover: '.@$attr['pauseonhover'].',
                    prevButton: $("#ticker-prev-'.$label.'"),
                    nextButton: $("#ticker-next-'.$label.'"),
                    stopButton: $("#ticker-pause-'.$label.'"),
                    startButton: $("#ticker-play-'.$label.'"),
                    start: function() {
                    	$("#timeline_'.$label.' #ticker-pause-'.$label.'").show();
                        $("#timeline_'.$label.' #ticker-play-'.$label.'").hide();
                    },
                    stop: function() {
                    	$("#timeline_'.$label.' #ticker-pause-'.$label.'").hide();
                        $("#timeline_'.$label.' #ticker-play-'.$label.'").show();
                    },
					movingUp: function() {
						$("'.$ticker_id.'").trigger("scroll");
					},
                    movingDown: function() {';
                if (@$attr['lazyload']) {
                    $output .= '
                    sb_tickerlazyload();';
                }
                $output .= '
					}
                });';
                if (@$attr['tabable'] && @$attr['autoclose'] ) {
				    $output .= '$sbticker.newsTicker("pause");';
                }
                if (@$attr['lazyload']) {
                    $output .= $ticker_lazyload_output . '
                    sb_tickerlazyload();';
                }
                
                // Filtering rotating feed
                if ( ! @$attr['tabable'] && @$attr['filters'] ) {
                $output .= "
                $('#timeline_$label .filter span').click(function() {
            		/* fetch the class of the clicked item */
            		var ourClass = $(this).data('filter');
            		
            		/* reset the active class on all the buttons */
            		$('#timeline_$label .filter span').removeClass('active');
            		/* update the active state on our clicked button */
            		$(this).addClass('active');
            		
            		if (ourClass == 'all') {
            			/* show all our items */
            			$('$ticker_id').children('li.sb-item').show();
            		} else {
            			/* hide all elements that don't share ourClass */
            			$('$ticker_id').children('li:not(' + ourClass + ')').fadeOut('fast');
            			/* show all elements that do share ourClass */
            			$('$ticker_id').children('li' + ourClass).fadeIn('fast');
            			
        				setTimeout(function() {
                            $('$ticker_id').trigger('scroll');
        				}, 500);
            		}
            		return false;
            	});";
                }
            }
             
            if ( @$attr['slide'] && ! (@$attr['tabable'] && @$attr['position'] == 'normal') ) {
                if ( $location == 'left' || $location == 'right' ) {
                    $getsizeof = 'Width';
                    $opener = 'sb-opener';
                    $padding = '';
                } else {
                    $getsizeof = 'Height';
                    $opener = 'sb-heading';
                    $padding = ( @$attr['showheader'] || ($location == 'bottom' && ! @$attr['tabable']) ) ? ' - 30' : '';
                }
                $openid = (@$attr['tabable']) ? "#timeline_$label .sb-tabs li" : "#timeline_$label .$opener";
                $output .= "
                /* slide in-out */
                var padding = $('#timeline_$label').outer$getsizeof();
                padding = parseFloat(padding)$padding;";
                $output .= ( @$attr['autoclose'] ) ? "$('#timeline_$label').animate({ '$location': '-='+padding+'px' }, 'fast' );" : '';
                $output .= "
                $('$openid').click(function(event) {
                    if ( $('#timeline_$label').hasClass('open') ) {
                        if ( $(this).hasClass( 'active' ) ) {
                            $('$openid').removeClass('active');
                            $('#timeline_$label').animate({ '$location': '-='+padding+'px' }, 'slow' ).removeClass('open');
                        } else {
                            $('$openid').removeClass('active');
                            $(this).addClass('active');
                        }
                    } else {
                        $(this).addClass('active');
                        $('#timeline_$label').animate({ '$location': '+='+padding+'px' }, 'slow' ).addClass('open');
                    }
                    event.preventDefault();
                });";
                } else {
                    // only for normal tabable
                    $openid = "#timeline_$label .sb-tabs li";
                    $output .= "
                    $('$openid').click(function(event) {
                        $('$openid').removeClass('active');
                        if ( $('#timeline_$label').hasClass('open') ) {
                            if ( $(this).hasClass( 'active' ) ) {
                                $('#timeline_$label').removeClass('open');
                            } else {
                                $(this).addClass('active');
                            }
                        } else {
                            $(this).addClass('active');
                            $('#timeline_$label').addClass('open');
                        }
                        event.preventDefault();
                    });";
                }
                
            if (@$ticker_id)
                $ticker_id_t = ' '.$ticker_id;
        } elseif ( $is_wall ) {
            if ( ! empty($feeds) ) {
                if (@$attr['stagger'])
                	$wallExt['stagger'] = 'stagger: '.$attr['stagger'];
                if (@$attr['default_filter'])
                	$wallExt['filter'] = 'filter: ".sb-'.$attr['default_filter'].'"';
                if ( ! empty($wallExt) )
                	$wallExtStr = implode(',', $wallExt);
	            $columnWidth = (@$attr['fixWidth'] == 'false') ? '".sb-isizer"' : $itemwidth;
	            $gutter = (@$attr['fixWidth'] == 'false') ? '".sb-gsizer"' : $gutterX;
	            $percentPosition = (@$attr['fixWidth'] == 'false') ? 'true' : 'false';
                if (@$attr['fixWidth'] == 'block') {
                $output .= '
                function sb_setwallgrid($wall) {
                	var wallw = $wall.width();
                	if (wallw >= 960 && wallw < 1200) {
                		var ncol = '.$attr['breakpoints'][1].';
                		'.$bpcol[1].'
                	}
                	else if (wallw >= 768 && wallw < 959) {
                		var ncol = '.$attr['breakpoints'][2].';
                		'.$bpcol[2].'
                	}
                	else if (wallw >= 600 && wallw < 767) {
                		var ncol = '.$attr['breakpoints'][3].';
                		'.$bpcol[3].'
                	}
                	else if (wallw >= 480 && wallw < 599) {
                		var ncol = '.$attr['breakpoints'][4].';
                		'.$bpcol[4].'
                	}
                	else if (wallw >= 320 && wallw < 479) {
                		var ncol = '.$attr['breakpoints'][5].';
                		'.$bpcol[5].'
                	}
                	else if (wallw <= 319) {
                		var ncol = '.$attr['breakpoints'][6].';
                		'.$bpcol[6].'
                	} else {
						var ncol = '.$attr['breakpoints'][0].';
						'.$bpcol[0].'
					}
					var twgut = '.$gutterX.' * (ncol-1);
                	var itemw = (wallw - twgut) / ncol;
                	$wall.isotope({
                		masonry: {
                			columnWidth: parseFloat(itemw.toFixed(3)),
                			gutter: '.$gutterX.'
                		}
                	});
                }';
                }
            	$output .= '
    			var $wall = $("#timeline_'.$label.$ticker_id_t.'").isotope({
                    itemSelector: ".sb-item",
                    layoutMode: "masonry",
					getSortData: {
                      dateid: function( itemElem ) {
                      	return $( itemElem ).attr("id");
                      }
					},
                    percentPosition: '.$percentPosition.',';
                    if (@$attr['fixWidth'] == 'false') {
                    	$output .= '
                    masonry: {
                      columnWidth: '.$columnWidth.',
                      gutter: '.$gutter.'
                    },';
                    }
                    $output .= '
                    transitionDuration: '.@$attr['transition'].',
                    originLeft: '.@$attr['originLeft'].',
                    '.@$wallExtStr.'
    			});';
    			if (@$attr['fixWidth'] == 'block')
                    $output .= 'sb_setwallgrid($wall);';
    			$output .= str_replace('.sb-thumb img', '#timeline_'.$label.$ticker_id_t.' .sb-thumb img', $lazyload_output);
                
                $output .= '
                /* layout wall on first load */
                $(window).one("transitionend webkitTransitionEnd oTransitionEnd otransitionend MSTransitionEnd", function(e) {
                    $('.$layout_container.').trigger("scroll");
                    $('.$layout_container.').trigger("resize");
                    $wall.isotope("layout");
                });';

                if ( ! @$attr['lazyload']) {
                    $output .= '
                    /* layout wall after each image loads */
                    $wall.imagesLoaded().progress( function() {
                        $wall.isotope("layout");
                    });';
                }
                
                $output .= '
                /* set wall grid on container resize */
                $(window).resize(function() {';
                if (@$attr['fixWidth'] == 'block')
                    $output .= 'sb_setwallgrid($wall);';
                $output .= '
                    setTimeout(function() {
                        $(window).trigger("scroll");
                    }, 500);
                });';

                $output .= '
    			/* Filter wall by networks */
                $("#sb_'.$label.$ticker_id_t.' .filter-items").on("click", "span", function() {
                    $(".filter-label,.sb-filter").removeClass("active");
                    var filterValue = $(this).addClass("active").attr("data-filter");';
                    if ( ! @$attr['filter_ads'] )
                        $output .= 'filterValue = (filterValue != "*") ? filterValue + ", .sb-advert" : filterValue;';
                    $output .= '
                    if ( $(this).hasClass( "filter-label" ) ) {
						$wall.isotope({ filter: filterValue });
	                    $wall.one( "arrangeComplete", function() {
                            $('.$layout_container.').trigger("resize");
                    	});
                    }
    			});';
                
                // fix lazyload after live update interval
                if (@$GLOBALS['islive'] && ! $is_feed) {
                    $output .= '
                    $wall.one( "removeComplete", function() {
                        $('.$layout_container.').trigger("scroll");
                    });';
                }
                
                // filter wall with a text phrase
				if ( @$attr['filter_search'] ) {
                    $output .= '
                $("#sb_'.$label.$ticker_id_t.' .sb-search").keyup(function(){
                    var filterValue = $(this).val();
                    if (filterValue != "") {
                        $wall.isotope({
                            filter: function() {
                                return ($(this).text().search(new RegExp(filterValue, "i")) > 0);
                            }
                        });
                    } else {
                        $wall.isotope({ filter: "*" });
                    }
                	$wall.one( "arrangeComplete", function() {
                        $('.$layout_container.').trigger("resize");
                	});
                });';
                }
                
                if ( ! empty($attr['filtering_tabs']) ) {
                    $output .= '
                $("#sb_'.$label.$ticker_id_t.' .sb-filter").click(function(){
                    var filterTerm = $(this).attr("data-filter");
                    if (filterTerm != "") {
						var filterRegex = /^\.+[a-z]+-\d+-[\s\S]+$/ig;
						if (filterRegex.test(filterTerm)) {
							$wall.isotope({ filter: filterTerm });
						} else {
	                        $wall.isotope({
	                            filter: function() {
	                                return ($(this).text().search(new RegExp(filterTerm, "ig")) > 0);
	                            }
	                        });
                        }
                    	$wall.one( "arrangeComplete", function() {
                            $('.$layout_container.').trigger("resize");
                    	});
                    }
                });';
                }
                
                $relayout_scroll = '$wall.isotope("layout");
                $(window).trigger("resize");';
                if (@$attr['wall_height'] != '') {
                    $output .= 'scrollStop(function () {
                        '.$relayout_scroll.'
                    }, "sb_'.$label.'");';
                } else {
                    $output .= 'scrollStop(function () {
                        '.$relayout_scroll.'
                    });';
                }
                $output .= $more_output;
            }
        } elseif ( $is_timeline || $is_grid ) {
            if (@$attr['fixWidth'] == 'block') {
                $output .= '
                var $grid = $("#timeline_'.$label.$ticker_id_t.'");
                function sb_setgridcolumns($grid) {
                    var gridw = $grid.width();
                	if (gridw >= 960 && gridw < 1200) {
                		'.$bpcol[1].'
                	}
                	else if (gridw >= 768 && gridw < 959) {
                		'.$bpcol[2].'
                	}
                	else if (gridw >= 600 && gridw < 767) {
                		'.$bpcol[3].'
                	}
                	else if (gridw >= 480 && gridw < 599) {
                		'.$bpcol[4].'
                	}
                	else if (gridw >= 320 && gridw < 479) {
                		'.$bpcol[5].'
                	}
                	else if (gridw <= 319) {
                		'.$bpcol[6].'
                	} else {
						'.$bpcol[0].'
                    }
                }';
                $output .= '
                $(window).resize(function() {
                    sb_setgridcolumns($grid);
                });
                sb_setgridcolumns($grid);';
            }
            if (@$attr['lazyload']) {
			    $output .= '
				$(".sb-thumb img").lazyload({
					effect: "fadeIn",
					skip_invisible: true
                });';
            }
            if (@$more_output)
                $output .= $more_output;
        }

        // load tabs and rebuild feed ticker
        if (@$attr['tabable']) {
        	$output .= '
               $("#timeline_'.$label.' .sb-tabs").on("click", "li", function() {
                if ( $(this).hasClass( "active" ) ) {
                  feed = $(this).attr("data-feed");
                  tabnonce = $(this).parent().attr("data-nonce");';
               $output .= "
                  $('#timeline_".$label." .sb-content ul').html('<p class=\"sb-loading\"><i class=\"sb-icon sb-'+feed+'\"></i></p>');";
               $output .= '
                  $.ajax({
                    type: "GET",
                    url: "social-wall",
                    data: { action: "sb_tabable", feed: feed, attr: '.$attr_ajax.', nonce: tabnonce, label: "'.$label.'" },
                    cache: false
                  })
                  .done(function( response ) {
                    $("#timeline_'.$label.$ticker_id_t.'").html(response);
                    $sbticker.newsTicker();
                    ' . $ticker_lazyload_output . $iframe_output . '
                  })
                  .fail(function() {
                    alert( "Problem reading the feed data!" );
                  });
                }
               });';
            }
            
            if (@$iframe) {
				if (@$attr['iframe'] == 'slide') {
					if ( ! isset($GLOBALS['sb_scripts']['iframe_slideshow']) ) {
						$output .= $iframe_slideshow;
						$GLOBALS['sb_scripts']['iframe_slideshow'] = true;
					}
				} else {
					if ( ! isset($GLOBALS['sb_scripts']['iframe_media']) ) {
						$output .= $iframe_media;
						$GLOBALS['sb_scripts']['iframe_media'] = true;
					}
				}

				if ( isset($slideshow) ) {
					$colorbox_resize = 'width:"85%"';
					$slicepoint = (@$attr['slicepoint']) ? $attr['slicepoint'] : 300;
					$output .= '
					  $("div.sb-body .sb-text").expander({
						slicePoint: '.$slicepoint.',
						expandText: "'.ss_lang( 'read_more' ).'",
						userCollapseText: "'.ss_lang( 'read_less' ).'"
					  });';
				} else {
					$colorbox_resize = 'maxWidth:"95%", maxHeight:"95%"';
				}
				
				// resize colorbox on screen rotation
				$resize_part1 = '
                $(window).on("resize", function() {
                    if (jQuery("#cboxOverlay").is(":visible")) {
						var wsize = sb_getwinsize();
						var cbox = $( "#cboxLoadedContent" );';
					// Slide autosize
					if ( isset($slideshow) ) {
						$resize_part2 = '
						var slidespan = $("#cboxLoadedContent .sb-slide .sb-thumb");
						if (slidespan.length > 0) {
							var slidethumb = $(".sb-slide .sb-thumb iframe, .sb-slide .sb-thumb img");
							if ( slidethumb.attr("height") ) {
								var cwidth = ( cbox.width() < slidethumb.attr("width") ) ? cbox.width() : slidethumb.width();
								var wwidth = Math.round((wsize.newWidth * 70) / 100);
								if (cwidth < wwidth && wsize.newHeight > slidethumb.attr("height")) {
									cwidth = wwidth;
								}
                                if (slidethumb.attr("width") < wwidth) {
                                    var newheight = slidethumb.attr("height") ? slidethumb.attr("height") : wsize.newHeight;
                                    cwidth = Math.floor( (slidethumb.attr("width") * newheight ) / slidethumb.attr("height") );
                                } else {
                                    if ( $(window).width() > 768 ) {
								        var newheight = Math.floor( (wwidth * slidethumb.attr("height") ) / slidethumb.attr("width") );
                                        slidethumb.width(wwidth);
                                    } else {
                                        var newheight = Math.floor( (wsize.newWidth * slidethumb.attr("height") ) / slidethumb.attr("width") );
                                    }
                                }
                                    
                                if (newheight > wsize.newHeight) {
                                    slidethumb.height(wsize.newHeight);
                                    slidethumb.width("auto");
                                } else {
                                    slidethumb.height(newheight);
                                    if (slidethumb.width() < cwidth)
                                        slidethumb.width(cwidth);
                                }
							}
							
							if ( $(window).width() >= 768 ) {
								if (slidespan.children("span").length > 0) {
									$(".sb-slide .sb-inner .sb-body").innerHeight(500);
								} else {
									$(".sb-slide .sb-inner .sb-body").innerHeight( (newheight > wsize.newHeight) ? wsize.newHeight : newheight );
								}
							} else {
								var bheight = wsize.newHeight - newheight;
								if (bheight < 150) {
									bheight = 150;
								}
								$(".sb-slide .sb-inner .sb-body").css("height", "auto").css("min-height", bheight);
							}
						} else {
							var bheight = (wsize.newHeight < 500) ? wsize.newHeight : 500;
							$(".sb-slide .sb-inner .sb-body").innerHeight(bheight);
						}';
					}
					$resize_part3 = '
						var iframebox = $( "#cboxLoadedContent iframe" );
						if ( iframebox.length ) {
							var iframeWidth = iframebox.attr("width");
							var iframeHeight = iframebox.attr("height");
                            if ( $(window).width() <= 767 ) {
                                var pheight = Math.round( (iframeHeight / iframeWidth) * 95 );
                                jQuery.colorbox.resize({width: "95%", height: pheight+"%"});
                            } else {
								if ( cbox.children("div.sb-slide").length > 0) {
									jQuery.colorbox.resize({'.$colorbox_resize.'});
								} else {
									if ( iframeHeight > wsize.newHeight ) {
										var newWidth = Math.round( (wsize.newHeight * iframeWidth) / iframeHeight);
										iframeWidth = newWidth;
										iframeHeight = wsize.newHeight;
										
										if ( iframeWidth > wsize.newWidth ) {
											iframeWidth = wsize.newWidth;
											iframeHeight = wsize.newHeight;
										}
									}
									jQuery.colorbox.resize({ width: parseInt(iframeWidth)+10, height: parseInt(iframeHeight)+10 });
								}
							}
                        } else {
                            jQuery.colorbox.resize({'.$colorbox_resize.'});
                        }
                    }
                });';
				
			if ( isset($slideshow) ) {
				if ( ! isset($GLOBALS['sb_scripts']['resize_slideshow']) ) {
					$output .= $resize_part1.$resize_part2.$resize_part3;
					$GLOBALS['sb_scripts']['resize_slideshow'] = true;
				}
			} else {
				if ( ! isset($GLOBALS['sb_scripts']['resize_media']) && ! isset($GLOBALS['sb_scripts']['resize_slideshow']) ) {
					$output .= $resize_part1.$resize_part3;
					$GLOBALS['sb_scripts']['resize_media'] = true;
				}
			}
        }
            
            if (@$GLOBALS['islive'] && ! $is_feed) {
                $timeinterval = (@$attr['live_interval'] ? intval($attr['live_interval']) * 60000 : 5 * 60000); // 60000 = 1 Min
                $stdiv = ($is_wall) ? 'div.sb-item' : 'div.timeline-row';
                $output .= '
              setInterval(function(){
                  var stlen = $("#timeline_'.$label.' '.$stdiv.'").length;
                  $.ajax({
                    type: "GET",
                    url: "social-wall",
                    data: {action: "sb_liveupdate", attr: '.$attr_ajax.', nonce: "'.ss_nonce_create( 'liveupdate' ).'", results: stlen, label: "'.$label.'"},
                    cache: false
                  })
                  .done(function( data ) {
                    if (data != "") {
                        var $elems = $(data).filter("'.$stdiv.'");
                        if ( $elems.first().attr("id") != $("#timeline_'.$label.' '.$stdiv.'").first().attr("id") ) {
                            var rm = 0;
                            var rms = false;
                            var rmcount = $elems.length;
                            var items = [];
                            $elems.each(function() {
                                if ( $("#timeline_'.$label.' '.$stdiv.'#" + $(this).attr("id") ).length == 0 ) {
                                    items.push(this);
                                    rm++;
                                } else {
                                    rms = true;
                                }
                            });';
                        if ( $is_wall ) {
                            $output .= '
                            if (rm > 0) {
                                $wall.isotope( "remove", $("#timeline_'.$label.'").find("'.$stdiv.'").slice(-rm) );
                            }
                            if (rms == true || rm == rmcount ) {
                                $wall.prepend( items ).isotope( "prepended", items );
                            }
                            $wall.isotope({
                            	sortBy: "dateid",
                            	sortAscending: false
                            });';
                        } elseif ( $is_timeline ) {
                            $output .= '
                            if (rm > 0) {
                                $("#timeline_'.$label.'").find("'.$stdiv.'").slice(-rm).remove();
                            }
                            if (rms == true || rm == rmcount ) {
                                $("#timeline_'.$label.'").prepend(items);
                            }
                            $(window).trigger("scroll");';
                        }
                        $output .= $lazyload_output . $iframe_output.'
                        }
                    }
                  });
              }, '.$timeinterval.');';
            }
            
            $output .= '
            });
        </script>';
        }
		
        if ($is_grid && $attr["columns_style"] == "1-2") {
			$output .= '<script>jQuery("#timeline_'.$label.$ticker_id_t.'").GridTwoFold();</script>';
		}
        if ( ! $ajax_feed)
            $output .= @$forceCrawl ? "\t<!-- End PHP Social Stream - cache is disabled. -->\n" : "\t<!-- End PHP Social Stream - cache is enabled - duration: " . $attr['cache'] . " minutes -->\n";

		$SCRIPT_DEBUG = defined( 'SB_DEBUG' ) && SB_DEBUG;
		if ( ! $SCRIPT_DEBUG )
            $output = str_replace( array("\r\n","\r","\t","\n"), '', $output );

        // slideshow output
        if (@$attr['iframe'] == 'slide' && @$ss_output) {
            if ( ! $ajax_feed)
	            $output .= '
	    		<div id="sb_slides_'.$label.'" style="display:none">
	                '.$ss_output.'
	    		</div>';
    		else
            	$output .= $ss_output;
        }
            
    	if ( $echo )
    		echo $output;
    	else
    		return $output;
    }
    
    // function for retrieving data from feeds
    public function get_feed( $feed_key, $i, $key2, $feed_value, $results, $sboption, $cache, $forceCrawl = false, $sb_label = null ) {
        $feed_value = trim($feed_value);
        switch ( $feed_key ) {
            case 'facebook':
                $pageresults = 9; // the max results that is possible to fetch from facebook API - for group = 9 for page = 30 - we used 9 to support both
                $stepresults = ceil($results / $pageresults);

                // If using 3 album ID
                $feedValue = explode('/', $feed_value);
                if ( count($feedValue) == 2 ) {
                    $feed_value = $feedValue[0];
                }

                // Find access token
                if ( ! empty($GLOBALS['api']['facebook']['facebook_accounts']) ) {
                    $resetAccounts = reset($GLOBALS['api']['facebook']['facebook_accounts']);
                    $resetPages = reset($resetAccounts['pages']);
                    $facebook_access_token = $resetPages['access_token'];
                    // Search account's access token by profile ID
                    if ( isset($GLOBALS['api']['facebook']['facebook_accounts'][$feed_value]) ) {
                        $facebook_access_token = $GLOBALS['api']['facebook']['facebook_accounts'][$feed_value]['access_token'];
                    } else {
                        foreach ($GLOBALS['api']['facebook']['facebook_accounts'] as $faccount) {
                            // Search page's access token by page ID
                            if ( isset($faccount['pages'][$feed_value]) ) {
                                $facebook_access_token = $faccount['pages'][$feed_value]['access_token'];
                                break;
                            }
                            // If not found, search by page username
                            foreach ($faccount['pages'] as $fpage) {
                                if ( @$fpage['username'] == $feed_value ) {
                                    $facebook_access_token = $fpage['access_token'];
                                    break;
                                }
                            }

                            // Search group's access token by group ID
                            if ( isset($faccount['groups'][$feed_value]) ) {
                                $facebook_access_token = $faccount['groups'][$feed_value]['access_token'];
                                break;
                            }
                            // If not found, search by group username
                            foreach ($faccount['groups'] as $fgroup) {
                                if ( @$fgroup['username'] == $feed_value ) {
                                    $facebook_access_token = $fgroup['access_token'];
                                    break;
                                }
                            }

                        }
                    }
                } else {
                    $facebook_access_token = @$GLOBALS['api']['facebook']['facebook_access_token'];
                }

                if ($locale = SB_LOCALE)
                    $locale_str = '&locale='.$locale;
                
                if ($datetime_from = @$sboption['facebook_datetime_from'])
                    $since_str = '&since='.strtotime($datetime_from);
                    
                if ($datetime_to = @$sboption['facebook_datetime_to'])
                    $until_str = '&until='.strtotime($datetime_to);
                
                if ($i == 3 || $i == 4) {
                    if ($after = @$_SESSION[$sb_label]['loadcrawl'][$feed_key.$i.$key2])
                        $after_str = '&after='.$after;
                } else {
                    if ($until = @$_SESSION[$sb_label]['loadcrawl'][$feed_key.$i.$key2])
                        $until_str = '&until='.$until;
                }
                // Define fields
                $afields = array(
                    'id', 'created_time', 'updated_time', 'permalink_url',
                    'message', 'story', 'picture', 'full_picture', 'status_type',
                    'from{id,name,picture,link}',
                    'comments.limit(5).summary(true){id,from{id,name,picture,link},message,permalink_url,comments.limit(5).summary(true){id,from{id,name,picture,link},message,permalink_url}}',
                    'likes.limit(5).summary(true){id,name,username,pic,link}',
                    'attachments.limit(5){media_type,title,type,description,url,description_tags,media{image,source},target,unshimmed_url,subattachments}'
                );
                $pagefeed = @$sboption['facebook_pagefeed'] ? $sboption['facebook_pagefeed'] : 'posts';
                if ($i == 1) {
                    if ($pagefeed == 'tagged')
                        $afields[] = 'name';
                }
                if ( ! in_array($pagefeed, array('posts', 'feed') ) ) {
                    array_push($afields, 'type', 'link', 'object_id', 'source', 'description');
                }
                // define the feed url
                if ($i == 1) {
                    // Page Feed
                    $feedType = $pagefeed;
                } elseif ($i == 2) {
                    // Group Feed
                    $feedType = 'feed';
                    unset($afields[11]);
                } elseif ($i == 3) {
					// Album/Page feed
                    $feedType = 'photos';
                    $afields[] = 'images';
                    if ( count($feedValue) == 2 ) {
                        $feed_value = $feedValue[1];
                    }
                } elseif ($i == 4) {
					// Page videos feed
                    $feedType = 'videos';
                    $afields[] = 'images';
                    $afields[] = 'title';
                    $afields[] = 'format';
                } elseif ($i == 6) {
                    // User posts feed
                    $feedType = 'feed';
                    array_splice($afields, array_search('likes.limit(5).summary(true){id,name,username,pic,link}', $afields ), 1);
                }
                $fields = implode(',', $afields);
                $feed_url = 'https://graph.facebook.com/v4.0/' . $feed_value . '/' . $feedType
                    . '?limit=' . ( ($i == 2) ? $pageresults : $results ) . @$since_str . @$until_str . @$after_str . @$locale_str
                    . '&fields=' . $fields . '&access_token=' . $facebook_access_token;
                // if group feed
                if ($i == 2) {
                    // Deprecated
                    
                    // crawl the feed or read from the cache
                    $label = 'https://graph.facebook.com/' . $feed_value . '/' . $feedType . '?limit=' . $results;
                    $get_feed = TRUE;
                    if ( ! $forceCrawl ) {
                        if ( $cache->is_cached($label) ) {
                            $content = $cache->get_cache($label);
                            $get_feed = FALSE;
                        }
                    }
                    if ($get_feed) {
                        $feed = array();
                        for ($i = 1; $i <= $stepresults; $i++) {
                            $content = $cache->do_curl($feed_url);
                            $pagefeed = @json_decode($content);
                            if ( ! empty($pagefeed) ) {
                                $feed[] = $pagefeed->data;
                                if ( count($pagefeed->data) < $pageresults )
                                    break;
                                $feed_url = $pagefeed->paging->next;
                            }
                        }
               			if ( ! $forceCrawl )
                            $cache->set_cache($label, json_encode($feed));
                    } else {
                        $feed = @json_decode($content);
                    }
                    
                } else {
                    $content = ( ! $forceCrawl ) ? $cache->get_data($feed_url, $feed_url) : $cache->do_curl($feed_url);
                    if ( $pagefeed = @json_decode($content) ) {
                        if ( isset( $pagefeed->error ) ) {
                            if (@$this->attr['debuglog'])
                                ss_debug_log( 'Facebook error: '.@$pagefeed->error->message.' - ' . $feedType, SB_LOGFILE );
                            $feed[] = null;
                        } else {
                            if ($i == 3 || $i == 4) {
                                $feed[] = $pagefeed;
                            } else {
                                $feed['data'][] = @$pagefeed->data;
                                $feed['next'] = @$pagefeed->paging->next;
                            }
                        }
                    }
                }
            break;
            case 'twitter':
                // define what type of tweets to filter from the feed
                if ( isset($sboption['twitter_feeds']) )
                    $twitter_feeds = explode(',', str_replace(' ', '', $sboption['twitter_feeds']) );
                else
                    $twitter_feeds = array('retweets', 'replies');
                switch($i)
                {
                	case 1:
                        $rest = 'statuses/user_timeline';
                        $params = array(
                        	// define what type of tweets to filter from the feed
                            'exclude_replies' => in_array('replies', $twitter_feeds) ? 'false' : 'true',
                            'screen_name' => $feed_value
                            );
                        if ( ! in_array('retweets', $twitter_feeds) )
                            $params['include_rts'] = 'false';
                	break;
                	case 2:
                        $rest = "lists/statuses";
                        if ( is_numeric($feed_value) )
                            $params = array('list_id' => $feed_value);
                        else {
                            $feedvalarr = explode('/', $feed_value);
                            $params = array('owner_screen_name' => $feedvalarr[0], 'slug' => $feedvalarr[1]);
                            if ( in_array('retweets', $twitter_feeds) )
                                $params['include_rts'] = 'true';
                        }
                	break;
                	case 3:
                        // The Search API is not complete index of all Tweets, but instead an index of recent Tweets. The index includes between 6-9 days of Tweets.
                        $rest = "search/tweets";
                        $params = array('include_entities' => 'true');
                        // Check for extra params
                        foreach (array('lang', 'locale') as $sparam) {
                            if (stristr($feed_value, "{$sparam}:") == true) {
                                $qparam = explode("{$sparam}:", $feed_value);
                                $qparam = explode(' ', $qparam[1]);
                                $feed_value = str_replace("{$sparam}:{$qparam[0]}", '', $feed_value);
                                $params[$sparam] = $qparam[0];
                            }
                        }
                        $feed_value = urlencode( trim($feed_value) );
                        if ( ! in_array('retweets', $twitter_feeds) )
                            $feed_value .= ' AND -filter:retweets';
                        $params['q'] = $feed_value;
                	break;
                }
                // Specifies the number of Tweets to try and retrieve.
                // Deleted content is removed after the count has been applied. Retweets are also include in the count, even if include_rts is not supplied.
                $params['count'] = $results;
                $params['tweet_mode'] = 'extended';
                
                // Restricts tweets to the given language, given by an ISO 639-1 code.
                if (isset($sboption['twitter_lang']) ) {
                	if ( ! $locale = $sboption['twitter_lang'])
                		$locale = SB_LOCALE;
                	$params['lang'] = $locale;
                }
                
                if ($id_from = @$sboption['twitter_since_id'])
                    $params['since_id'] = $id_from;
                    
                if ($id_to = @$sboption['twitter_max_id'])
                    $params['max_id'] = $id_to;
                    
                if ($max_id = @$_SESSION[$sb_label]['loadcrawl'][$feed_key.$i.$key2])
                    $params['max_id'] = $max_id;
        		
                // Fetch the feed
                $feed = $this->twitter_get_feed($rest, $params, $forceCrawl, $cache);
    		break;
            // Google+ API deprecated
            /*
            case 'google':
    			$google_api_key = @$GLOBALS['api']['google']['google_api_key'];
                if ($nextPageToken = @$_SESSION[$sb_label]['loadcrawl'][$feed_key.$i.$key2])
                    $pageToken = '&pageToken='.$nextPageToken;
                $feed_url = 'https://www.googleapis.com/plus/v1/people/' . $feed_value . '/activities/public?maxResults=' . $results . @$pageToken . '&key=' . $google_api_key;
                $content = ( ! $forceCrawl ) ? $cache->get_data($feed_url, $feed_url) : $cache->do_curl($feed_url);
                $feed = @json_decode($content);
                if (@$feed->error) {
                    $feed = null;
                }
            break;
            */
            case 'flickr':
                $flickr_api_key = @$GLOBALS['api']['flickr']['flickr_api_key'];
                if ($nextPage = @$_SESSION[$sb_label]['loadcrawl'][$feed_key.$i.$key2])
                    $pageToken = '&page='.$nextPage;
                if ($i == 1) {
                    $feedType = 'flickr.people.getPublicPhotos';
                    $feedID = '&user_id='.$feed_value;
                } elseif ($i == 2) {
                    $feedType = 'flickr.groups.pools.getPhotos';
                    $feedID = '&group_id='.$feed_value;
                } elseif ($i == 3) {
                    $feedType = 'flickr.photosets.getPhotos';
                    $feedID = '&photoset_id='.$feed_value;
                }
                $feed_url = 'https://api.flickr.com/services/rest/?method='.$feedType.'&api_key='.$flickr_api_key . $feedID . '&per_page=' . $results . @$pageToken . '&extras=date_upload,date_taken,owner_name,icon_server,tags,o_dims,views,media,url_c&format=json&nojsoncallback=1';
                $content = ( ! $forceCrawl ) ? $cache->get_data($feed_url, $feed_url) : $cache->do_curl($feed_url);
                $feed = @json_decode($content);
    		break;
            case 'delicious':
                if ( empty($_SESSION[$sb_label]['loadcrawl']) ) {
                    $feed_url = "https://feeds.del.icio.us/v2/json/" . $feed_value . '?count=' . $results;
                    $content = ( ! $forceCrawl) ? $cache->get_data($feed_url, $feed_url) : $cache->do_curl($feed_url);
                    $feed = @json_decode($content);
                }
            break;
    		case 'pinterest':
                if ( empty($_SESSION[$sb_label]['loadcrawl']) ) {
                    // get json data
                    $json_uri = ($i == 1) ? 'users' : 'boards';
                    $feed_url = "https://api.pinterest.com/v3/pidgets/$json_uri/" . $feed_value . "/pins/";
                    $content = ( ! $forceCrawl) ? $cache->get_data($feed_url, $feed_url) : $cache->do_curl($feed_url);
                    $feed[0] = @json_decode($content);
                    if (@$feed[0]->status == 'success') {
                        // get rss data
                        $rss_uri = ($i == 1) ? '/feed.rss' : '.rss';
                        $feed_url = "https://www.pinterest.com/" . $feed_value . "$rss_uri";
                        $content = ( ! $forceCrawl) ? $cache->get_data($feed_url, $feed_url) : $cache->do_curl($feed_url);
                        $feed[1] = @simplexml_load_string(trim($content));
                    } else {
                        if ( ! empty($feed[0]->message) )
                        	ss_debug_log( 'Pinterest error: '.$feed[0]->message.' - ' . $feed_url, SB_LOGFILE );
                        $feed = null;
                    }
                }
    		break;
            case 'instagram':
                $next_max_id = @$_SESSION[$sb_label]['loadcrawl'][$feed_key.$i.$key2];
                // If is username, search for related ID
                if ( ! is_numeric($feed_value) && ! empty($GLOBALS['api']['instagram']['instagram_accounts']) ) {
                    foreach ($GLOBALS['api']['instagram']['instagram_accounts'] as $ikey => $iaccount) {
                        if ( @$iaccount['username'] == $feed_value ) {
                            $feed_value = $ikey;
                            break;
                        }
                    }
                }
                $feed_token = $this->instagram_access_token($feed_value);
                $max_str = 'max_id';
                $feed_url = '';
                $use_official = ($this->instagram_is_access_token($feed_value) || $feed_value == 'self');
                if ($i == 1) {
                    $feed_url = 'https://api.instagram.com/v1/users/self/media/recent?count=' . $results;
                    if ($use_official) {
                        $feed_url .= '&access_token=' . $feed_token;
                    } else {
                        // Deprecated - Just to be used as cache label
                        $feed_url = 'https://api.instagram.com/v1/users/' . urlencode($feed_value) . '/media/recent?count=' . $results;
                    }
                } elseif ($i == 2) {
                    // Deprecated - Just to be used as cache label
                    $feed_url = 'https://api.instagram.com/v1/tags/' . urlencode($feed_value) . '/media/recent?count=' . $results . '&access_token=' . $feed_token;
                    $max_str = 'max_tag_id';
                } elseif ($i == 3) {
                    // Deprecated - Just to be used as cache label
                    $feed_url = 'https://api.instagram.com/v1/locations/' . $feed_value . '/media/recent?access_token=' . $feed_token;
                } elseif ($i == 4) {
                    $coordinates = explode(',', $feed_value);
                    $feed_url = 'https://api.instagram.com/v1/media/search?lat=' . $coordinates[0] . '&lng=' . $coordinates[1] . '&distance=' . $coordinates[2] . '&access_token=' . $feed_token;
                    $max_str = 'max_timestamp';
                }
				
                if (@$feed_url) {
                    if ($next_max_id)
                        $feed_url .= '&'. $max_str .'='.$next_max_id;
                    
                    // USE SCRAPER If searching tag or access token is not set
                    if ($i == 2 || $i == 3 || $i == 1) {
                        $get_feed = TRUE;
                        if ( ! $forceCrawl ) {
                            if ( $cache->is_cached($feed_url) ) {
                                $content = $cache->get_cache($feed_url);
                                $get_feed = FALSE;
                            }
                        }
                        if ($get_feed) {
                            $next_max_id = empty($next_max_id) ? '' : $next_max_id;
                            try {
                                // use Instagram scraper
                                $instagram = $this->instagram;

                                $feed = new stdClass();
                                if ($i == 2) {
									$content = $medias = $instagram->getQueryPaginateMediasByTag($feed_value, $results, $next_max_id);
                                } elseif ($i == 3) {
                                    $content = $instagram->getPaginateMediasByLocationId($feed_value, $next_max_id);
                                } else {
                                    if ( ! is_numeric($feed_value) ) {
										$user_info_endpoint = 'https://api.instacloud.io/?path=%2Fv1%2Fusers%2F' . $feed_value;
										$user_info_response  = ( ! $forceCrawl) ? $cache->get_data($user_info_endpoint, $user_info_endpoint, true) : @$cache->do_curl($user_info_endpoint);
										$user_info = json_decode($user_info_response, true);
										if ( (isset($user_info['meta']) && isset($user_info['meta']['code']) && $user_info['meta']['code'] !== 200) || empty($user_info['data']) ) {
											ss_debug_log( 'Instagram error: Cant load user data for ' . $feed_value . '. Please, try again later.', SB_LOGFILE );
										} else {
											$feed_value = $user_info['data']['id'];
										}

										/*$igaccount = $instagram->searchAccount($feed_value);
                                        if ( ! empty($igaccount) ) {
                                            $feed_value = $igaccount->getId();
                                        } else {
                                            $igaccount = $instagram->searchAccountsByUsername($feed_value, 1);
                                            if ( ! empty($igaccount) ) {
                                                foreach ($igaccount as $igacc) {
                                                    if ( $igacc->getUsername() == $feed_value ) {
                                                        $feed_value = $igacc->getId();
                                                        break;
                                                    }
                                                }
                                            } else {
                                                $feed_value = null;
                                            }
                                        }*/
                                    }
                                    if (! empty($feed_value)) {
                                        $content = $instagram->getPaginateMediasByUserId($feed_value, $results, $next_max_id);
                                    }
                                }
                            } catch (Exception $e) {
                                ss_debug_log( 'Instagram error: ' . $e->getMessage() . ' - ' . ( ($i == 2) ? 'getMediasByTag' : ( ($i == 3) ? 'getPaginateMediasByLocationId' : 'getPaginateMediasByUserId' ) ), SB_LOGFILE );
                            }

							if ( ! empty($content) ) {
                                $medias = $content['medias'];
                                if ($content['hasNextPage'] === true) {
                                    $feed->pagination = new stdClass();
                                    $feed->pagination->next_max_id = $content['maxId'];
                                }
                            }

                            $user_info = array();
                            if ( ! empty($medias) ) {
                                // Loop through items
                                foreach ($medias as $key => $media) {
                                    $feed->data[$key] = new stdClass();
                                    $feed->data[$key]->id = $media->getId();
                                    $feed->data[$key]->link = $media->getLink();
                                    $feed->data[$key]->type = $media->getType();
                                    $feed->data[$key]->shortcode = $media->getShortCode();
                                    $feed->data[$key]->created_time = $media->getCreatedTime();
                                    $feed->data[$key]->caption = new stdClass();
                                    $feed->data[$key]->caption->text = $media->getCaption();
                                    
                                    $feed->data[$key]->user = new stdClass();
                                    $account = $media->getOwner();
                                    $user_id = $feed->data[$key]->user->id = $account->getId();
                                    if ($user_id) {
                                        if ( empty( $account->getUsername() ) || empty( $account->getProfilePicUrl() ) ) {
                                            if ( ! isset($user_info[$user_id]) ) {
                                                try {
                                                    // if ($i == 2) {
													$privateInfo = $instagram->getAccountPrivateInfo($user_id);
													$user_info[$user_id] = $privateInfo;
													$profile_pic = $user_info[$user_id]['profile_pic_url'];
                                                    // } else {
                                                    //     $umedia = $instagram->getMediaByUrl($feed->data[$key]->link);
                                                    //     $user_info[$user_id] = $umedia->getOwner();
                                                    //     $profile_pic = $user_info[$user_id]->getProfilePicUrl();
                                                    // }
                                                } catch (Exception $e) {
                                                    ss_debug_log( 'Instagram error: ' . $e->getMessage() . ' - ' . ( ($i == 1) ? 'getMediaByUrl' : 'getAccountPrivateInfo' ), SB_LOGFILE );
                                                }
                                                if ( empty($profile_pic) ) {
                                                    $cache->user_agent   = 'Mozilla/5.0 (Linux; Android 8.1.0; motorola one Build/OPKS28.63-18-3; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/70.0.3538.80 Mobile Safari/537.36 Instagram 72.0.0.21.98 Android (27/8.1.0; 320dpi; 720x1362; motorola; motorola one; deen_sprout; qcom; pt_BR; 132081645)';
                                                    $user_info_endpoint  = "https://i.instagram.com/api/v1/users/{$user_id}/info/";
                                                    $user_info_response  = ( ! $forceCrawl) ? $cache->get_data($user_info_endpoint, $user_info_endpoint, true) : @$cache->do_curl($user_info_endpoint);
                                                    $user_info[$user_id] = @json_decode($user_info_response);
                                                }
                                            }
                                            if ( ! empty($user_info[$user_id]) ) {
                                                if ( is_array($user_info[$user_id]) ) {
                                                    $feed->data[$key]->user->username = $user_info[$user_id]['username'];
                                                    $feed->data[$key]->user->profile_picture = $user_info[$user_id]['profile_pic_url'];
                                                } else {
                                                    if (@$user_info[$user_id]->status == 'ok') {
                                                        $feed->data[$key]->user->username = $user_info[$user_id]->user->username;
                                                        $feed->data[$key]->user->full_name = @$user_info[$user_id]->user->full_name;
                                                        $feed->data[$key]->user->profile_picture = $user_info[$user_id]->user->profile_pic_url;
                                                    } else {
                                                        $feed->data[$key]->user->username = $user_info[$user_id]->getUsername();
                                                        $feed->data[$key]->user->full_name = $user_info[$user_id]->getFullName();
                                                        $feed->data[$key]->user->profile_picture = $user_info[$user_id]->getProfilePicUrl();
                                                    }
                                                }
                                            }
                                        } else {
                                            $feed->data[$key]->user->username = $account->getUsername();
                                            $feed->data[$key]->user->full_name = $account->getFullName();
                                            $feed->data[$key]->user->profile_picture = $account->getProfilePicUrl();
                                        }
                                    }

                                    $feed->data[$key]->images = new stdClass();
                                    $feed->data[$key]->images->thumbnail = new stdClass();
                                    $feed->data[$key]->images->thumbnail->url = $media->getImageThumbnailUrl();
                                    $feed->data[$key]->images->low_resolution = new stdClass();
                                    $feed->data[$key]->images->low_resolution->url = $media->getImageLowResolutionUrl();
                                    $feed->data[$key]->images->standard_resolution = new stdClass();
                                    $feed->data[$key]->images->standard_resolution->url = $media->getImageStandardResolutionUrl();
                                    $feed->data[$key]->images->high_resolution = new stdClass();
                                    $feed->data[$key]->images->high_resolution->url = $media->getImageHighResolutionUrl();
                                    $feed->data[$key]->square_images = $media->getSquareImages();
									$feed->data[$key]->carousel_media = $media->getCarouselMedia();
                                    if(!empty($feedSidecarMedias = $media->getSidecarMedias())){
                                    	if(is_array($feedSidecarMedias)){
                                    		$ro = array();
                                    		foreach ($feedSidecarMedias as $k => $v){
                                    			if($k != 0) $ro[] = $v["imageStandardResolutionUrl"];
											}
										}
										$feed->data[$key]->sidecar_media = $ro;
									}

                                    $feed->data[$key]->videos = new stdClass();
                                    $feed->data[$key]->videos->low_resolution = new stdClass();
                                    $feed->data[$key]->videos->low_resolution->url = $media->getVideoLowResolutionUrl();
                                    $feed->data[$key]->videos->standard_resolution = new stdClass();
                                    $feed->data[$key]->videos->standard_resolution->url = $media->getVideoStandardResolutionUrl();
                                    $feed->data[$key]->videos->low_bandwidth = new stdClass();
                                    $feed->data[$key]->videos->low_bandwidth->url = $media->getVideoLowBandwidthUrl();
                                    
                                    // If user needs original media urls
                                    if ( empty($feed->data[$key]->videos->standard_resolution->url) && @$sboption['instagram_media_urls'] ) {
                                        if ($feed->data[$key]->type == 'video') {
                                            $imedia = $instagram->getMediaByUrl($feed->data[$key]->link);
                                            $feed->data[$key]->videos->low_resolution->url = $imedia->getVideoLowResolutionUrl();
                                            $feed->data[$key]->videos->standard_resolution->url = $imedia->getVideoStandardResolutionUrl();
                                            $feed->data[$key]->videos->low_bandwidth->url = $imedia->getVideoLowBandwidthUrl();
                                        }
                                    }

                                    $feed->data[$key]->comments = new stdClass();
                                    $feed->data[$key]->comments->count = $media->getCommentsCount();

                                    $feed->data[$key]->likes = new stdClass();
                                    $feed->data[$key]->likes->count = $media->getLikesCount();

                                    if ( ! $forceCrawl )
                                        $cache->set_cache($feed_url, json_encode($feed) );
                                }
                            }
                        } else {
                            $feed = @json_decode($content);
                        }
                    } else {
                        // use official API
                        $content = ( ! $forceCrawl) ? $cache->get_data($feed_url, $feed_url) : $cache->do_curl($feed_url);
                        $feed = @json_decode($content);
                    }
				}
				
                if ( ! isset($feed) || empty($feed) || empty(@$feed->data) ) {
					$content = $this->sbinstagram->GetContent($feed_value, (int)$results, @$next_max_id);
					if ( ! empty($content) ) $feed = $content;
				}
    		break;
    		case 'youtube':
                $google_api_key = @$GLOBALS['api']['google']['google_api_key'];
                if ($nextPageToken = @$_SESSION[$sb_label]['loadcrawl'][$feed_key.$i.$key2])
                    $pageToken = '&pageToken='.$nextPageToken;
                switch($i)
                {
                	case 1:
                    case 4:
                        $channel_filter = ($i == 1) ? 'forUsername' : 'id';
                        $user_url = 'https://www.googleapis.com/youtube/v3/channels?'.$channel_filter.'=' . $feed_value .'&key=' . $google_api_key . '&part=snippet,contentDetails';
                        $user_content = ( ! $forceCrawl) ? $cache->get_data($user_url, $user_url, true) : @$cache->do_curl($user_url);
                        if ($user_content) {
                            $user_feed = @json_decode($user_content);
                            if (@$user_feed->items[0])
                                $feed_url = 'https://www.googleapis.com/youtube/v3/playlistItems?playlistId=' . $user_feed->items[0]->contentDetails->relatedPlaylists->uploads . '&part=snippet,contentDetails';
                        }
                    break;
                    case 2:
                        $feed_url = 'https://www.googleapis.com/youtube/v3/playlistItems?playlistId=' . $feed_value . '&part=snippet,contentDetails';
                    break;
                    case 3:
                        $feed_url = 'https://www.googleapis.com/youtube/v3/search?q=' . rawurlencode($feed_value) . '&part=snippet';
                    break;
                }
                if ($results > 50) $results = 50;
                if (@$feed_url) {
                    $feed_url .= '&maxResults=' . $results . @$pageToken . '&key=' . $google_api_key;
                    $content = ( ! $forceCrawl) ? $cache->get_data($feed_url, $feed_url) : $cache->do_curl($feed_url);
                    $feed = @json_decode($content);
                    
					if (is_object($feed) && @$user_feed)
						$feed->userInfo = @$user_feed->items[0]->snippet;
                }
                if (@$feed->error) {
                    $feed = null;
                }
    		break;
    		case 'vimeo':
                $vimeo_access_token = @$GLOBALS['api']['vimeo']['vimeo_access_token'];
                $feedtype = 'videos';
                $feed_url = 'https://api.vimeo.com/users/' . $feed_value . '/' . $feedtype . "?per_page=$results&access_token=$vimeo_access_token";
                if ($nextPage = @$_SESSION[$sb_label]['loadcrawl'][$feed_key.$i.$key2])
                    $feed_url .= '&page='.$nextPage;
                    
                $content = ( ! $forceCrawl) ? $cache->get_data($feed_url, $feed_url) : $cache->do_curl($feed_url);
                $feed = @json_decode($content);
    		break;
    		case 'tumblr':
                $tumblr_api_key = @$GLOBALS['api']['tumblr']['tumblr_api_key'];
                $feed_url = "https://api.tumblr.com/v2/blog/" . $feed_value . ".tumblr.com/posts?api_key={$tumblr_api_key}&limit=$results";
                if ($posts_start = @$_SESSION[$sb_label]['loadcrawl'][$feed_key.$i.$key2])
                    $feed_url .= '&offset='.$posts_start;
                    
                $content = ( ! $forceCrawl) ? $cache->get_data($feed_url, $feed_url) : $cache->do_curl($feed_url);
                $feed = @json_decode($content);
            break;
    		case 'stumbleupon':
                if ( empty($_SESSION[$sb_label]['loadcrawl']) ) {
                    $stumbleupon_feeds = (@$sboption['stumbleupon_feeds']) ? ss_explode($sboption['stumbleupon_feeds']) : array( 'comments' => true, 'likes' => true );
                    $feedtypes = array('comments', 'likes');
                    foreach ($feedtypes as $ftype) {
                        if (@$stumbleupon_feeds[$ftype]) {
                            $feed_url = "https://www.stumbleupon.com/rss/stumbler/" . $feed_value . "/" . $ftype;
                            $content = ( ! $forceCrawl) ? $cache->get_data($feed_url, $feed_url) : $cache->do_curl($feed_url);
                            if ( $data = @simplexml_load_string(trim($content), 'SimpleXMLElement', LIBXML_NOCDATA) )
                                $feed[$ftype] = $data;
                        }
                    }
                }
    		break;
    		case 'deviantart':
                if ( empty($_SESSION[$sb_label]['loadcrawl']) ) {
                    $feed_url = "https://backend.deviantart.com/rss.xml?type=deviation&q=by%3A" . $feed_value . "+sort%3Atime+meta%3Aall";
                    $content = ( ! $forceCrawl) ? $cache->get_data($feed_url, $feed_url) : $cache->do_curl($feed_url);
                    $feed = @simplexml_load_string(trim($content));
                }
    		break;
            case 'rss':
                $content = ( ! $forceCrawl) ? $cache->get_data($feed_value, $feed_value) : $cache->do_curl($feed_value);
                $feed = @simplexml_load_string(trim($content));
            break;
            case 'soundcloud':
                if ( empty($_SESSION[$sb_label]['loadcrawl']) ) {
                    $soundcloud_client_id = @$GLOBALS['api']['soundcloud']['soundcloud_client_id'];
                    switch($i) {
                        case 1:
                            $endpoint = 'tracks';
                        break;
                        case 2:
                            $endpoint = 'sets';
                        break;
                    }
                    $feed_url = "https://api.soundcloud.com/resolve?url=https://soundcloud.com/{$feed_value}/{$endpoint}&client_id=" . $soundcloud_client_id . "&limit={$results}";
					// Reference: https://developers.soundcloud.com/docs/api/reference#resolve
					$is_cached = $cache->is_cached($feed_url);
                    $content = ( ! $forceCrawl) ? $cache->get_data($feed_url, $feed_url) : $cache->do_curl($feed_url);
					$feed = @json_decode($content);
                    if ( ! empty($feed->errors) ) {
                        if ( ! empty($feed->errors[0]->error_message) && ! $is_cached ) {
							$this->ss_debug_log( 'SoundCloud error: '.$feed->errors[0]->error_message.' - ' . $feed_url );
						}
                        $feed = null;
                    }
                }
            break;
            case 'vk':
                $pagefeed = (@$sboption['vk_pagefeed']) ? $sboption['vk_pagefeed'] : 'all';
                $wall_by = ($i == 1) ? 'domain' : 'owner_id';
                $vk_service_token = @$GLOBALS['api']['vk']['vk_service_token'];
				$feed_url = "https://api.vk.com/method/wall.get?v=5.34&{$wall_by}={$feed_value}&count={$results}&extended=1&lang=en&filter={$pagefeed}&access_token={$vk_service_token}";
                if ($i == 3) {
                	$feed_v = explode("/",$feed_value);
					$feed_value = @$feed_v[0];
					$feed_query = @$feed_v[1];
					$wall_by = ( is_int($feed_value) ) ? 'owner_id' : 'domain';
					$feed_url = "https://api.vk.com/method/wall.search?v=5.34&{$wall_by}={$feed_value}&count={$results}&extended=1&lang=en&query={$feed_query}&access_token={$vk_service_token}";
				}
                if (@$this->attr['https'])
                    $feed_url .= '&https=1';
                if ($offset = @$_SESSION[$sb_label]['loadcrawl'][$feed_key.$i.$key2])
                    $feed_url .= '&offset='.$offset;
                else
                    $offset = 0;
                $content = ( ! $forceCrawl) ? $cache->get_data($feed_url, $feed_url) : $cache->do_curl($feed_url);
                $content = @mb_convert_encoding($content, "UTF-8", "auto");
                $feed = @json_decode($content);
                if (is_object($feed))
                    $feed->offset = $offset;
            break;
            case 'linkedin':
                $linkedin_access_token = @$GLOBALS['api']['linkedin']['linkedin_access_token'];
                $feed_value_encoded = urlencode("urn:li:organization:$feed_value");
                $feed_url = "https://api.linkedin.com/v2/ugcPosts?q=authors&authors=List({$feed_value_encoded})&projection=(paging(total,count,start),elements*(author~(id,localizedName,vanityName,logoV2(cropped~:playableStreams)),created(time),id,lifecycleState,specificContent))&count={$results}&oauth2_access_token={$linkedin_access_token}";
                
                /*
                $sboption['linkedin_pagefeed'] = 'status-update';
                if ($pagefeed = @$sboption['linkedin_pagefeed']) {
                    if ($pagefeed != 'all')
                        $feed_url .= '&event-type='.$pagefeed;
                }
                */
                
                // add pagination
                if ($offset = @$_SESSION[$sb_label]['loadcrawl'][$feed_key.$i.$key2])
                    $feed_url .= '&start='.$offset;
                    
                    $headers = array();
                    $headers[] = 'Content-Type: application/json';
                    $headers[] = 'x-li-format: json';
                    $headers[] = 'X-Restli-Protocol-Version: 2.0.0'; // use protocol v2
                    $content = ( ! $forceCrawl) ? $cache->get_data($feed_url, $feed_url, false, $headers) : $cache->do_curl($feed_url, false, $headers);
                    $feed = @json_decode($content);
            break;
            case 'vine':
                $feed_url = "https://api.vineapp.com/timelines/users/{$feed_value}";
                if ($offset = @$_SESSION[$sb_label]['loadcrawl'][$feed_key.$i.$key2])
                    $feed_url .= '?page='.$offset;
                $content = ( ! $forceCrawl) ? $cache->get_data($feed_url, $feed_url) : $cache->do_curl($feed_url);
                $feed = @json_decode($content, false, 512, JSON_BIGINT_AS_STRING);
            break;
    	}
        
    	return @$feed;
    }
    
    // create time string for sorting and applying pinning options
    private function make_timestr($time, $link) {
        $timestr = ( is_numeric($time) ) ? $time : strtotime($time);
        if ( ! empty($this->attr['pins']) ) {
            $pinscount = @strlen(count($this->sboption['pins']) );
            $dkey = array_search($link, $this->attr['pins']);
            if ($dkey !== false) {
                $dkey = str_pad($dkey, $pinscount, 0, STR_PAD_LEFT);
                $timestr = "9{$dkey}";
            }
        }
		$linkstr = sprintf("%u", crc32($link) );
        return $timestr.'-'.$linkstr;
    }
    
    // applying stream items removal
    private function make_remove($link) {
        if ( ! empty($this->attr['remove']) ) {
            if ( in_array($link, $this->attr['remove']) )
                return false;
        }
        return true;
    }
    
    /**
     * Word Limiter
     *
     * Limits a string to X number of words.
     *
     * @param   $end_char   the end character. Usually an ellipsis
     */
    function word_limiter($text, $url = '', $comment = false) {
        $limit = $comment ? @$this->attr['commentwords'] : @$this->attr['words'];
        $end_char = '...';
        
        $str = trim( flame_strip_tags($text) );
        $str1 = trim( flame_strip_tags($text, array('<a>') ) );
        $str1 = trim(preg_replace('#<([^ >]+)[^>]*>([[:space:]]|&nbsp;)*</\1>#', '', $str1)); // remove all empty HTML tags
    	if ($str == '') {
    		return $str;
    	}
        
        if ($this->str_word_count_utf8($str) < $limit) {
            return ($str1 == $str) ? $this->append_links($str1) : trim(preg_replace('/\s*\n+/', "<br>", $str1) );
        }
        if ($limit) {
            preg_match('/^\s*+(?:\S++\s*+){1,'.(int) $limit.'}/', $str, $matches);
            if (strlen($str) == strlen($matches[0]) ) {
                $end_char = '';
            }
            $str = $this->append_links($matches[0]);
        }
    	if (@$this->attr['readmore'] && $url)
            $end_char = ' <a href="' . $url . '"'.$this->target.' style="font-size: large;">' . $end_char . '</a>';
            
        return $str.$end_char;
    }
    
    // Title Limiter (limits the title of each item to X number of words)
    function title_limiter($str, $url = '') {
        $end_char = '...';
        $limit = (@$this->attr['titles']) ? $this->attr['titles'] : 15;
        $str = strip_tags($str);

    	if (trim($str) == '')
    	{
    		return $str;
    	}
        
        if ($this->str_word_count_utf8($str) < $limit) {
            return $str;
        }

    	preg_match('/^\s*+(?:\S++\s*+){1,'.(int) $limit.'}/', $str, $matches);

        if (strlen($str) == strlen($matches[0]) )
    	{
    		$end_char = '';
    	}
            
        return rtrim($matches[0]).$end_char;
    }
    
    function append_links($str) {
        // make the urls hyper links
        $regex = '#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#';
        $str = preg_replace_callback($regex, array(&$this, 'links_callback'), $str);
        return trim(preg_replace('/\s*\n+/', "<br>", $str) );
    }
    
    function links_callback($matches) {
        return (@$matches[0]) ? '<a href="'.$matches[0].'"'.$this->target.'>'.$matches[0].'</a>' : '';
    }
    
    function format_text($str) {
        $str = trim( flame_strip_tags($str) );
    	if ($str == '')
    	{
    		return $str;
    	}
        
        $str = $this->append_links($str);
        return $str;
    }
    
    // Word counter function
    function str_word_count_utf8($str) {
        return count(preg_split('~[^\p{L}\p{N}\']+~u', $str));
    }
    
    // get all URLs from string
    function geturls($string) {
	    $regex = '#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#';
	    preg_match_all($regex, $string, $matches);
	    return @$matches[0];
    }
    
    function getsrc($html) {
        preg_match_all('/<img[^>]+>/i', $html, $rawimagearray, PREG_SET_ORDER);
        if ( isset($rawimagearray[0][0]) ) {
            preg_match('@src="([^"]+)"@', $rawimagearray[0][0], $match);
            $img['src'] = @array_pop($match);
            preg_match('@width="([^"]+)"@', $rawimagearray[0][0], $matchwidth);
            $img['width'] = @array_pop($matchwidth);
            
            return (@$img['width'] && $img['width'] < 10) ? false : $img;
        }
    }

    function add_links($endpoint, $text) {
        $endpoints = array(
            'twitter' => array(
                'https://twitter.com/$1',
                'https://twitter.com/search/%23$1'
            ),
            'instagram' => array(
                'https://www.instagram.com/$1/',
                'https://www.instagram.com/explore/tags/$1/'
            ),
            'facebook' => array(
                'https://www.facebook.com/$1',
                'https://www.facebook.com/hashtag/$1/'
            )
        );
        // Add links to all @ mentions
        $text = preg_replace('/(?<!&)@([\pL\d]+)/u', '<a href="'.$endpoints[$endpoint][0].'"'.$this->target.'>@$1</a>', $text);
        // Add links to all hash tags
        $text = preg_replace('/(?<!\S)#([\pL\d]+)/u', '<a href="'.$endpoints[$endpoint][1].'"'.$this->target.'>#$1</a>', $text);
        
        return $text;
    }

    // Embed url
    function get_embed($embedUrl, $enforce_custom_code = false) {
        $embedCache = new SS_HtmlEmbedCache;
        $embedCache->debug_log = @$this->attr['debuglog'];
        $embedCache->timeout = SB_API_TIMEOUT;
        $embedCache->cache_path = SB_DIR . '/cache/';
        if ($embedResult = $embedCache->getData($embedUrl) ) {
            // If no provider found, create a html block
            if ( ! empty($embedResult->code) && ! $enforce_custom_code ) {
                $embed = '<div class="sb-html-embed">';
                $embed .= $embedResult->code;
                $embed .= '</div>';
            } else {
                if ($embedResult->title || $embedResult->description) {
                    $embed = '<a class="sb-html-embed" href="' . $embedUrl . '" target="_blank">';
                    $embed .= '
                    <div class="sb-embed-user">';
                    if ( ! empty($embedResult->providerIcon) ) {
                        $embed .= '
                        <div class="sb-embed-uthumb">
                            <img class="sb-img" alt="' . @$embedResult->providerName . '" src="' . $embedResult->providerIcon . '">
                        </div>';
                    }
                    $author = @$embedResult->authorName ? $embedResult->authorName : @$embedResult->providerName;
                    $embed .= '<div class="sb-embed-uinfo">' . $author . '</div>
                    </div>';
                    if ( ! empty($embedResult->image) ) {
                        $embed .= '
                        <div class="sb-thumb">
                            <img class="sb-img sb-html-embed-image" src="' . $embedResult->image . '" />
                        </div>';
                    }
                    if ( ! empty($embedResult->title) )
                        $embed .= '<span class="sb-title">' . $embedResult->title . '</span>';
                    if ( ! empty($embedResult->description) )
                        $embed .= '<span class="sb-text">' . $this->word_limiter($embedResult->description, $embedUrl) . '</span>';
                    $embed .= '</a>';
                }
            }

            return $embed;
        }
    }
    
    function twitter_add_links($text, $embed_urls = null) {
        $text = $this->add_links('twitter', $text);
        
        // Convert shared links to Embed code
        if ( ! empty($embed_urls) ) {
            $embedList = array();
            foreach ($embed_urls as $url) {
                $urls[] = $url->url;
                $exurls[] = $url->expanded_url;
            }

            $regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>";
            if (preg_match_all("/$regexp/siU", $text, $matches, PREG_SET_ORDER) ) {
                foreach($matches as $match) {
                    $embed_key = array_search($match[3], $urls);
                    if ( is_numeric( $embed_key) )
                    if ( ! empty($exurls[$embed_key]) ) {
                        $embedList[$embed_key] = array('surl' => $match[0], 'url' => $exurls[$embed_key]);
                    }
                }
            }

            foreach ($embedList as $embedItem) {
                if ( $embed = $this->get_embed($embedItem['url'], true) ) {
                    $text = str_replace($embedItem['surl'], $embed, $text);
                }
            }
        }

        return $text;
    }

    function twitter_get_feed($rest, $params, $forceCrawl, $cache) {
        if ( empty($GLOBALS['api']['twitter']['twitter_api_key'])
            && empty($GLOBALS['api']['twitter']['twitter_api_secret']) ) {
            $consumer_key = 'defnTiNGlAHJWWcK6UfNKegtB';
            $consumer_secret = 'OP2HCxQi0bSTTXUsKmelBYncG6SOyDHfygzl4pHLjtk8IbjonG';
        } else {
            $consumer_key = @trim($GLOBALS['api']['twitter']['twitter_api_key']);
            $consumer_secret = @trim($GLOBALS['api']['twitter']['twitter_api_secret']);
        }
        $oauth_access_token = @trim($GLOBALS['api']['twitter']['twitter_access_token']);
        $oauth_access_token_secret = @trim($GLOBALS['api']['twitter']['twitter_access_token_secret']);
        $get_feed = TRUE;
        $label = 'https://api.twitter.com/1.1/'.$rest.'/'.serialize($params);
        if ( ! $forceCrawl ) {
            if ( $cache->is_cached($label) ) {
                $content = $cache->get_cache($label);
                $get_feed = FALSE;
            }
        }
        if ($get_feed) {
            $auth = new TwitterOAuth($consumer_key, $consumer_secret, $oauth_access_token, $oauth_access_token_secret);
            $auth->setTimeouts(SB_API_TIMEOUT, SB_API_TIMEOUT);
            $auth->setDecodeJsonAsArray(false);
            if ( ! empty($GLOBALS['SB_PROXY']['proxy']) ) {
                $auth->setProxy([
                    'CURLOPT_PROXY' => @trim($GLOBALS['SB_PROXY']['proxy']),
                    'CURLOPT_PROXYPORT' => @trim($GLOBALS['SB_PROXY']['proxy_port']),
                    'CURLOPT_PROXYUSERPWD' => @trim($GLOBALS['SB_PROXY']['proxy_userpass'])
                ]);
            }
            $content = $auth->get($rest, $params);
            if ( ! $content ) {
            	if (@$this->attr['debuglog'])
                    ss_debug_log( 'Twitter error: An error occurs while reading the feed, please check your connection or settings.', SB_LOGFILE );
            }
            else {
                $feed = $content;
                if ( isset( $feed->errors ) ) {
                    foreach( $feed->errors as $key => $val ) {
                        if (@$this->attr['debuglog'])
                            ss_debug_log( 'Twitter error: '.$val->message.' - ' . $rest, SB_LOGFILE );
                    }
                    $feed = null;
                }
            }
   			if ( ! $forceCrawl )
                $cache->set_cache($label, json_encode($content) );
        }
        else
            $feed = @json_decode($content);
        
        return @$feed;
    }

    function vk_get_photo($photo) {
        foreach ($photo as $ikey => $iphoto) {
            if (stristr($ikey, 'photo_') == TRUE) {
                $source = $iphoto;
            }
        }
        return @$source;
    }
    
    // Find a Youtube video link in a string and convert it into Embed Code
	function youtube_get_embedurl($url) {
		$url = urldecode($url);
		preg_match("#(http://www.youtube.com)?/(v/([-|~_0-9A-Za-z]+)|watch\?v\=([-|~_0-9A-Za-z]+)&?.*?)#i", $url, $matches);
		foreach ($matches as $matche) {
			if ( ! empty($matche) && strpos($matche, '/' ) === FALSE && strpos($matche, '=' ) === FALSE) {
				$vidID = $matche;
				break;
			}
		}
	    if ( ! empty($vidID) )
	    	return 'https://www.youtube.com/embed/' . $vidID ;
	    else
	    	return null;
    }

    // Check if the string is a real Instagram access token
    function instagram_is_access_token($token) {
        $tokenArr = explode('.', $token);
        if ( count($tokenArr) == 3 ) {
            if ( is_numeric($tokenArr[0]) && ! is_numeric($tokenArr[2]) && strlen($tokenArr[2]) == 32 )
                return true;
        }
        return false;
    }

    // Find Instagram access token
    function instagram_access_token($feed_value) {
        $feed_token = '';
        $instagram_access_token = ! empty($GLOBALS['api']['instagram']['instagram_accounts'])
            ? reset($GLOBALS['api']['instagram']['instagram_accounts'])['access_token'] : @$GLOBALS['api']['instagram']['instagram_access_token'];
        if ($this->instagram_is_access_token($feed_value) ) {
            $feed_token = $feed_value;
        } elseif ($feed_value == 'self') {
            $feed_token = $instagram_access_token;
        } elseif ( ! empty($GLOBALS['api']['instagram']['instagram_accounts']) ) {
            // Search account's access token by ID
            if ( isset( $GLOBALS['api']['instagram']['instagram_accounts'][$feed_value]['access_token'] ) ) {
                $feed_token = $GLOBALS['api']['instagram']['instagram_accounts'][$feed_value]['access_token'];
            } else {
                $feed_token = $instagram_access_token;
            }
        }
        return $feed_token;
    }

    function instagram_set_proxy() {
        if ( ! empty($GLOBALS['api']['instagram']['instagram_logins'][0]['username']) ) {
            $instagram = \InstagramScraper\Instagram::withCredentials(
                $GLOBALS['api']['instagram']['instagram_logins'][0]['username'],
                $GLOBALS['api']['instagram']['instagram_logins'][0]['password'],
                new Psr16Adapter( 'Files', ['path' => SB_DIR . '/cache'] )
            );
            try {
                $instagram->login();
                $instagram->saveSession();
            } catch (Exception $e) {
                ss_debug_log( 'Instagram error: Can not login to Instagram. ' . $e->getMessage() . ' - login', SB_LOGFILE );
            }
        } else {
            $instagram = new \InstagramScraper\Instagram();
        }
        // $instagram->setUserAgent('Mozilla/5.0 (Linux; Android 8.1.0; motorola one Build/OPKS28.63-18-3; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/70.0.3538.80 Mobile Safari/537.36 Instagram 72.0.0.21.98 Android (27/8.1.0; 320dpi; 720x1362; motorola; motorola one; deen_sprout; qcom; pt_BR; 132081645)');

        if ( ! empty($GLOBALS['SB_PROXY']['proxy']) ) {
            $proxy = [
                'address' => @trim($GLOBALS['SB_PROXY']['proxy']),
                'port'    => @trim($GLOBALS['SB_PROXY']['proxy_port']),
                'tunnel'  => false,
                'type'    => CURLPROXY_HTTP,
                'timeout' => 30
            ];
            if ( ! empty($GLOBALS['SB_PROXY']['proxy_userpass']) ) {
                $proxyauth = explode(':', @trim($GLOBALS['SB_PROXY']['proxy_userpass']) );
                $proxy['auth'] = [
                    'user'   => $proxyauth[0],
                    'pass'   => $proxyauth[1],
                    'method' => CURLAUTH_BASIC
                ];
            }
            \InstagramScraper\Instagram::setProxy($proxy);
        }
        return $instagram;
    }

    function instagram_parse_comments($comments_data, $link = '') {
        $comments_out = '';
        $count = 0;
        $comments_count = 3;
        if ( isset($this->sboption['instagram']['instagram_comments']) )
            $comments_count = ( @$this->sboption['instagram']['instagram_comments'] > 0 ) ? $this->sboption['instagram']['instagram_comments'] : 0;
        if (isset($comments_data->data) )
            $comments_data = $comments_data->data;
        if ($comments_count)
        foreach ($comments_data as $comment) {
            $count++;
            $comment_message = (@$this->attr['commentwords']) ? $this->word_limiter($comment->text, $link, true) : $comment->text;
            $nocommentimg = (@empty($comment->from->profile_picture) ) ? ' sb-nocommentimg' : '';
            $comment_date = (@$comment->created_time) ? 'title="'.ss_friendly_date( $comment->created_time, true ).'"' : '';
            $comments_out .= '<span class="sb-meta sb-mention'.$nocommentimg.'"'.$comment_date.'>';
            if ( ! @empty($comment->from) ) {
                if (@$comment->from->profile_picture)
                    $comments_out .= '<img class="sb-img sb-commentimg" src="' . $comment->from->profile_picture . '" alt="">';
                $name = (@$comment->from->full_name) ? $comment->from->full_name : $comment->from->username;
                $comments_out .= '<a href="https://instagram.com/' . $comment->from->username . '"'.$this->target.'>' . $name . '</a> ';
            }
            $comments_out .= $comment_message . '</span>';
            if ( $count >= $comments_count ) break;
        }
        return $comments_out;
    }

    function youtube_parse_comments($comments_data, $link) {
        $comments_out = '';
        $count = 0;
        $comments_count = ( @$this->sboption['youtube']['youtube_comments'] > 0 ) ? $this->sboption['youtube']['youtube_comments'] : 0;
        if ( ! isset($this->sboption['youtube']['youtube_comments']) )
            $comments_count = 3;
        if ($comments_count)
        foreach ( $comments_data->items as $comment ) {
            $count++;
            $snippet = $comment->snippet->topLevelComment->snippet;
            $comment_message = (@$this->attr['commentwords']) ? $this->word_limiter(nl2br($snippet->textOriginal), $link, true) : nl2br($snippet->textDisplay);
            $nocommentimg = (@empty($snippet->authorDisplayName) ) ? ' sb-nocommentimg' : '';
            $comment_date = (@$snippet->updatedAt) ? $snippet->updatedAt : @$snippet->publishedAt;
            $comments_out .= '<span class="sb-meta sb-mention'.$nocommentimg.'" title="'.ss_friendly_date( $comment_date, 'datetime' ).'">';
            if ( ! @empty($snippet->authorDisplayName) ) {
                $comments_out .= '<img class="sb-img sb-commentimg" src="'.$snippet->authorProfileImageUrl.'" alt="" /><a href="' . $snippet->authorChannelUrl . '"'.$this->target.'>' . $snippet->authorDisplayName . '</a> ';
            }
            $comments_out .= $comment_message . '</span>';
            if ( $count >= $comments_count ) break;
        }
        return $comments_out;
	}
	
	function ss_debug_log($message) {
		if (@$this->attr['debuglog']) {
			ss_debug_log( $message, SB_LOGFILE );
		}
	}
} // end class

// Check for Windows to find and replace the %e modifier correctly
function ss_format_locale( $format ) {
    if (strtoupper(substr(PHP_OS, 0, 3) ) == 'WIN') {
        $format = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%d', $format);
    }
    return $format;
}

// Friendly dates (i.e. "2 days ago")
function ss_friendly_date( $date, $format = 'friendly' ) {
	// Get the time difference in seconds
	$post_time = ( is_numeric($date) ) ? $date : strtotime( $date );
    if ( strlen($post_time) > 10 ) $post_time = substr( $post_time, 0, -3 );

    $full_time = strftime( SB_DT_FORMAT.' '.SB_TT_FORMAT, $post_time );

    if ($format == 'friendly') {
        $current_time = time();
        $time_difference = $current_time - $post_time;
        
        // Seconds per...
        $minute = 60;
        $hour = 3600;
        $day = 86400;
        $week = $day * 7;
        $month = $day * 31;
        $year = $day * 366;
        
        // if over 3 years
        if ( $time_difference > $year * 3 ) {
            $friendly_date = ss_lang( 'a_long_while_ago' );
        }
        
        // if over 2 years
        else if ( $time_difference > $year * 2 ) {
            $friendly_date = ss_lang( 'over_2_years_ago' );
        }
        
        // if over 1 year
        else if ( $time_difference > $year ) {
            $friendly_date = ss_lang( 'over_a_year_ago' );
        }
        
        // if over 11 months
        else if ( $time_difference >= $month * 11 ) {
            $friendly_date = ss_lang( 'about_a_year_ago' );
        }
        
        // if over 2 months
        else if ( $time_difference >= $month * 2 ) {
            $months = (int) $time_difference / $month;
            $friendly_date = sprintf( ss_lang( 'd_months_ago' ), $months );
        }
        
        // if over 4 weeks ago
        else if ( $time_difference > $week * 4 ) {
            $friendly_date = ss_lang( 'last_month' );
        }
        
        // if over 3 weeks ago
        else if ( $time_difference > $week * 3 ) {
            $friendly_date = ss_lang( '3_weeks_ago' );
        }
        
        // if over 2 weeks ago
        else if ( $time_difference > $week * 2 ) {
            $friendly_date = ss_lang( '2_weeks_ago' );
        }
        
        // if equal to or more than a week ago
        else if ( $time_difference >= $day * 7 ) {
            $friendly_date = ss_lang( 'last_week' );
        }
        
        // if equal to or more than 2 days ago
        else if ( $time_difference >= $day * 2 ) {
            $days = (int) $time_difference / $day;
            $friendly_date = sprintf( ss_lang( 'd_days_ago' ), $days );
        }
        
        // if equal to or more than 1 day ago
        else if ( $time_difference >= $day ) {
            $friendly_date = ss_lang( 'yesterday' );
        }
        
        // 2 or more hours ago
        else if ( $time_difference >= $hour * 2 ) {
            $hours = (int) $time_difference / $hour;
            $friendly_date = sprintf( ss_lang( 'd_hours_ago' ), $hours );
        }
        
        // 1 hour ago
        else if ( $time_difference >= $hour ) {
            $friendly_date = ss_lang( 'an_hour_ago' );
        }
        
        // 2–59 minutes ago
        else if ( $time_difference >= $minute * 2 ) {
            $minutes = (int) $time_difference / $minute;
            $friendly_date = sprintf( ss_lang( 'd_minutes_ago' ), $minutes );
        }
        
        else {
            $friendly_date = ss_lang( 'just_now' );
        }

        $dateout = '<time title="' . $full_time . '" datetime="' . date( 'c', $post_time ) . '">' . ucfirst( $friendly_date ) . '</time>';
    } elseif ($format == 'date') {
        $dateout = '<time title="' . $full_time . '" datetime="' . date( 'c', $post_time ) . '">' . strftime( SB_DT_FORMAT, $post_time ) . '</time>';
    } elseif ($format == 'datetime') {
        $dateout = $full_time;
    }
	
    return $dateout;
}

// i18n dates
function ss_i18n_date( $date, $format ) {
    $post_time = ( is_numeric($date) ) ? $date : strtotime( $date );
    if ( strlen($post_time) > 10 ) $post_time = substr( $post_time, 0, -3 );
    return strftime( $format, $post_time );
}

function countFormatter($digit) {
    if ($digit >= 1000000000) {
        return round($digit/ 1000000000, 1). 'G';
    }
    if ($digit >= 1000000) {
        return round($digit/ 1000000, 1).'M';
    }
    if ($digit >= 1000) {
        return round($digit/ 1000, 1). 'K';
    }
    return $digit;
}

function ss_explode( $output = array() ) {
    if ( ! empty($output) ) {
        $outputArr = explode(',', str_replace(' ', '', $output) );
        foreach ($outputArr as $val)
            $out[$val] = true;
        
        return $out;
    }
    return false;
}

// hex to rgb numerical converter for color styling
function ss_hex2rgb($hex, $str = true) {
   $hex = str_replace("#", "", $hex);

   if(strlen($hex) == 3) {
      $r = hexdec(substr($hex, 0, 1).substr($hex, 0, 1) );
      $g = hexdec(substr($hex, 1, 1).substr($hex, 1, 1) );
      $b = hexdec(substr($hex, 2, 1).substr($hex, 2, 1) );
   } else {
      $r = hexdec(substr($hex, 0, 2) );
      $g = hexdec(substr($hex, 2, 2) );
      $b = hexdec(substr($hex, 4, 2) );
   } 
   // returns the rgb values separated by commas OR returns an array with the rgb values
   $rgb = ($str) ? "$r, $g, $b" : array($r, $g, $b);
   return $rgb;
}

// Shuffle associative and non-associative array
function ss_shuffle_assoc($list) {
  if ( ! is_array($list) ) return $list;

  $keys = array_keys($list);
  shuffle($keys);
  $random = array();
  foreach ($keys as $key)
    $random[$key] = $list[$key];

  return $random;
}

function flame_strip_tags( $html, $allowed_tags = array() ) {
    $allowed_tags = array_change_key_case($allowed_tags, CASE_LOWER);
    $rhtml = preg_replace_callback( '/<\/?([^>\s]+)[^>]*>/i', function ($matches) use (&$allowed_tags) {
        return in_array( strtolower( $matches[1] ), $allowed_tags ) ? $matches[0] : '';
    }, $html);
    return $rhtml;
}

// This method creates an nonce. It should be called by one of the previous two functions.
function ss_nonce_create( $action = '', $user = '' ) {
	return substr( ss_nonce_generate_hash( $action . $user ), -12, 10);
}

// This method validates an nonce
function ss_nonce_verify( $nonce, $action = '', $user = '' ) {
	// Nonce generated 0-12 hours ago
	if ( substr(ss_nonce_generate_hash( $action . $user ), -12, 10) == $nonce ) {
		return true;
	}
	return false;
}

// This method generates the nonce timestamp
function ss_nonce_generate_hash( $action = '', $user = '' ) {
	return md5( SB_NONCE_KEY . $action . $user . $action );
}

// Retrieves the translated string for language
function ss_lang($label) {
    return (@$GLOBALS['_'][$label]) ? $GLOBALS['_'][$label] : $label;
}

function ss_win_locale($locale) {
    $winlocale = array(
    "en" => 'USA_ENU',
    "ar" => 'SAU_ARA',
    "az" => 'AZE_AZE',
    "bg_BG" => 'BGR_BGR',
    "bs_BA" => 'Bosanski',
    "ca" => 'ESP_CAT',
    "cy" => 'Cymraeg',
    "da_DK" => 'DNK_DAN',
    "de_CH" => 'CHE_DES',
    "de_DE" => 'DEU_DEU',
    "el" => 'GRC_ELL',
    "en_CA" => 'CAN_ENC',
    "en_AU" => 'AUS_ENA',
    "en_GB" => 'GBR_ENG',
    "eo" => 'Esperanto',
    "es_PE" => 'PER_ESR',
    "es_ES" => 'ESP_ESN',
    "es_MX" => 'MEX_ESM',
    "es_CL" => 'CHL_ESL',
    "eu" => 'ESP_EUQ',
    "fa_IR" => 'IRN_FAR',
    "fi" => 'FIN_FIN',
    "fr_FR" => 'FRA_FRA',
    "gd" => 'Gàidhlig',
    "gl_ES" => 'ESP_GLC',
    "haz" => 'هزاره گی',
    "he_IL" => 'ISR_HEB',
    "hr" => 'HRV_HRV',
    "hu_HU" => 'HUN_HUN',
    "id_ID" => 'IDN_IND',
    "is_IS" => 'ISL_ISL',
    "it_IT" => 'ITA_ITA',
    "ja" => 'JPN_JPN',
    "ko_KR" => 'KOR_KOR',
    "lt_LT" => 'LTU_LTH',
    "my_MM" => 'ဗမာစာ',
    "nb_NO" => 'NOR_NOR',
    "nl_NL" => 'nld_nld',
    "nn_NO" => 'Norsk nynorsk',
    "oci" => 'Occitan',
    "pl_PL" => 'POL_PLK',
    "ps" => 'پښتو',
    "pt_PT" => 'PRT_PTG',
    "pt_BR" => 'BRA_PTB',
    "ro_RO" => 'ROM_ROM',
    "ru_RU" => 'Русский',
    "sk_SK" => 'Slovenčina',
    "sl_SI" => 'Slovenščina',
    "sq" => 'Shqip',
    "sr_RS" => 'Српски језик',
    "sv_SE" => 'Svenska',
    "th" => 'ไทย',
    "tr_TR" => 'Türkçe',
    "ug_CN" => 'Uyƣurqə',
    "uk" => 'Українська',
    "zh_CN" => '简体中文',
    "zh_TW" => '繁體中文');
    return $winlocale[$locale];
}

function ss_debug_log($mValue, $sFilePath = null) {
    $msg = date( "Y/m/d H:i:s", time() ) . ' - ' . $mValue . PHP_EOL;
    error_log($msg, 3, $sFilePath);
}

// function for using in template files
function social_stream( $atts ) {
    $sb = new SocialStream();
    return $sb->init( $atts, false );
}

include(SB_DIR.'/ajax.php');
// End of file social-stream.php