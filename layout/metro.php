<?php

/**
 * PHP Social Stream
 * Copyright 2018 Axent Media (axentmedia@gmail.com)
 */

class ss_metro_layout extends ss_default_layout{
    public $target;
    
    function create_item( $feed_class, $param, $attr = array(), $output = array(), $sbi = 0, $i = 0, $ifeed = 0 ) {
        $filtername = ' '.$feed_class.'-'.$i.'-'.$ifeed;
        $iconSocial = ( @$param['icon'][0] ) ? '<img src="'.$param['icon'][0].'" class="sb-img origin-flag" style="vertical-align:middle" alt="' . $feed_class . '">' : '<span class="sb-iconm sb-' . $feed_class . '"><i class="sb-iconm-inner sb-icon sb-' . $feed_class . '"></i></span>';
        $playstate = (@$param['play']) ? '<div class="sb-playstate"></div>' : '';
        $user_title = (@$param['user']['title']) ? $param['user']['title'] : $param['user']['name'];
		$datasize = (@$param['size']) ? ' data-size="' . $param['size'] . '"' : '';
		$imglayout = (@$attr['layout_image']) ? ' sb-'.$attr['layout_image'] : '';
		$imgAlt = $this->sb_create_alt($feed_class, $param);
        
        $noclass = array();
        if ( ! @$this->output['info'])
            $noclass[] = ' sb-nofooter';
        if ( ! @$param['thumb'] || ! @$this->output['thumb'])
            $noclass[] = ' sb-nothumb';
        if ( count($noclass) > 1 ) {
            $noclass = array();
            $noclass[] = ' sb-noft';
        }

        $no_inner = (count($output) == 1 AND ! empty($output['thumb']) ) ? true : false;
        if ($no_inner)
            $noclass[] = ' sb-noinner';

        $no_meta = ( (@$param['meta']['comments_data'] || @$param['meta']['likes_data']
            || @$param['meta']['comments_total_count'] || @$param['meta']['likes_total_count'])
            && (@$output['comments'] || @$output['likes']) ) ? false : true;
        if ($no_meta)
            $noclass[] = ' sb-nometa';

        $inner = '<div class="sb-container'.$imglayout.( implode('', $noclass) ).'">';
        
        $thumb = $sbthumb = '';
        if (@$attr['carousel']) {
            $cropclass = 'sb-crop';
            if (@$param['iframe'])
                $cropclass .= ' '.$param['iframe'];
            if (@$param['thumb'] && @$output['thumb']) {
				$aurl = (@$param['thumburl'] ? $param['thumburl'] : @$param['url']);
				if (@$param['object'] && @$attr['lightboxtype'] == 'media') {
					$aurl32 = sprintf("%u", crc32($aurl) );
					$aurl = "#$aurl32";
					$thumb .= '
					<div style="display: none">
						<div class="sb-object" id="'.$aurl32.'">
							' . $param['object'] . '
						</div>
					</div>';
				}
				$sbimg = (@$attr['lazyload']) ? 'data-lazy="' . htmlspecialchars($param['thumb']) . '"' : 'src="' . htmlspecialchars($param['thumb']) . '"';
                $thumb .= '<a class="'.$cropclass.'" href="' . $aurl . '"'.$datasize.$this->target.'><img class="sb-img" '.$sbimg.' alt="'.$imgAlt.'">'.$playstate.'</a>';
            } else {
                $cropclass .= ' sb-userimg';
                if (@$param['user']['image'] && ! @$output['thumb']) {
                    $thumb = '<div class="'.$cropclass.'"><img class="sb-img" src="' . $param['user']['image'] . '" alt="' . @$param['user']['name'] . '"><br /><span>'.$user_title.'</span></div>';
                }
            }
            if (@$thumb)
                $sbthumb .= '
                <div class="sb-thumb">
                    ' . $thumb . '
                </div>';
        } else {
            if (@$param['thumb'] && @$output['thumb']) {
                $iframe = (@$param['iframe']) ? ' class="'.$param['iframe'].'"' : '';
				$aurl = htmlspecialchars(@$param['thumburl'] ? $param['thumburl'] : @$param['url']);
				if (@$param['object'] && @$attr['lightboxtype'] == 'media') {
					$aurl32 = sprintf("%u", crc32($aurl) );
					$aurl = "#$aurl32";
					$sbthumb .= '
					<div style="display: none">
						<span class="sb-object" id="'.$aurl32.'">
							' . $param['object'] . '
						</span>
					</div>';
				}
                $sbimg = (@$attr['lazyload']) ? 'data-original="' . htmlspecialchars($param['thumb']) . '" src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iLTQzIC00MyAxMjQgMTI0IiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHN0cm9rZT0iI2ZmZiI+ICAgIDxnIGZpbGw9Im5vbmUiIGZpbGwtcnVsZT0iZXZlbm9kZCI+ICAgICAgICA8ZyB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxIDEpIiBzdHJva2Utd2lkdGg9IjIiPiAgICAgICAgICAgIDxjaXJjbGUgc3Ryb2tlLW9wYWNpdHk9Ii41IiBjeD0iMTgiIGN5PSIxOCIgcj0iMTgiLz4gICAgICAgICAgICA8cGF0aCBkPSJNMzYgMThjMC05Ljk0LTguMDYtMTgtMTgtMTgiPiAgICAgICAgICAgICAgICA8YW5pbWF0ZVRyYW5zZm9ybSAgICAgICAgICAgICAgICAgICAgYXR0cmlidXRlTmFtZT0idHJhbnNmb3JtIiAgICAgICAgICAgICAgICAgICAgdHlwZT0icm90YXRlIiAgICAgICAgICAgICAgICAgICAgZnJvbT0iMCAxOCAxOCIgICAgICAgICAgICAgICAgICAgIHRvPSIzNjAgMTggMTgiICAgICAgICAgICAgICAgICAgICBkdXI9IjFzIiAgICAgICAgICAgICAgICAgICAgcmVwZWF0Q291bnQ9ImluZGVmaW5pdGUiLz4gICAgICAgICAgICA8L3BhdGg+ICAgICAgICA8L2c+ICAgIDwvZz48L3N2Zz4="' : 'src="' . htmlspecialchars($param['thumb']) . '"';
                $sbthumb = '
                <div class="sb-thumb">
                    <a href="' . $aurl . '"'.$iframe.$datasize.$this->target.'><img class="sb-img" '.$sbimg.' alt="' . $feed_class . '">'.$playstate.'</a>
                </div>';
            }
        }
        
        if (@$sbthumb && @$attr['layout_image'] == 'imgexpand') {
            $inner .= $sbthumb;
        }
        
        $idstr = ' id="'.$sbi.'"';
        if (@$attr['iframe'] == 'slide') {
            $inline = ' data-href="#inline_'.$sbi.'"';
            $sbinline = ' sb-inline';
        } else {
            $inline = $sbinline = '';
        }
        
        $inner .= $iconSocial;

        if ( ! $no_inner)
            $inner .= '
            <div class="sb-inner">';
        
        $inner .= '
            <div class="sb-inner2">';
        
        if (@$param['title'] && @$output['title'] && ! @$attr['carousel']) {
            $inner .= '
            <span class="sb-title">
                ' . $param['title'] . '
            </span>';
        }
        
        if (@$sbthumb && @$attr['layout_image'] == 'imgnormal') {
            $inner .= $sbthumb;
        }
        
        if ( (@$param['text'] && @$output['text']) || (@$attr['carousel'] && ! @$thumb) ) {
            $expandclass = ( ! @$thumb) ? ' sb-expand' : '';
            $inner .= '<span class="sb-text'.$expandclass.'">';
            
            if (@$attr['carousel']) {
                if (@$param['title'])
                    $inner .= '
                    <span class="sb-title">
                        ' . $param['title'] . '
                    </span>';
            }
            
            $inner .= @$param['text'];
            $inner .= '</span>';
			$is_carousel_text = true;
        }
        
        if ( ! @$attr['carousel']) {
            if (@$param['tags'] && @$output['tags'])
                $inner .= '
                <span class="sb-text">
                    <strong>'.__( 'Tags', 'social-board' ).': </strong>' . $param['tags'] . '
                </span>';
        }

        // comments/likes block
        if ( ! $no_meta ) {
			$NoText = (!@$is_carousel_text) ? 'sb-no-ctext' : '';
            $inner .= '
            <span class="sb-metadata '.$NoText.'">';
            if (@$output['comments']) {
                if ( ! empty($param['meta']['comments_total_count']) ) {
                    $inner .= '
                    <span class="sb-meta">
                        <span class="comments"><i class="sb-bico sb-comments"></i> '.countFormatter($param['meta']['comments_total_count']).' '.ucfirst( ss_lang( 'comments' ) ).'</span>
                    </span>';
                }
                if ( ! empty($param['meta']['comments_data']) && ! @$attr['carousel'] ) {
                    if ($param['meta']['comments_data'] == 'fetch')
                        $inner .= '
                        <div class="sb-fetchcomments" data-nonce="'.ss_nonce_create( 'fetchcomments' ).'" data-id="'.$param['id'].'" data-link="'.$param['url'].'" data-feed="'.$feed_class.'-'.$i.'-'.$ifeed.'">
                            <a href="javascript:void(0)" class="sb-triggercomments">'.ss_lang( 'show_comments' ).'</a>
                        </div>';
                    else
                        $inner .= '
                        <div class="sb-fetchcomments">
                            '.$param['meta']['comments_data'].'
                        </div>';
                }
            }
            if (@$output['likes']) {
                if ( ! empty($param['meta']['likes_total_count']) ) {
                    $inner .= '
                    <span class="sb-meta">
                        <span class="likes"><i class="sb-bico sb-star"></i> '.countFormatter($param['meta']['likes_total_count']).' '.ucfirst( ss_lang( 'likes' ) ).'</span>
                    </span>';
                }
                if ( ! empty($param['meta']['likes_data']) && ! @$attr['carousel'] ) {
                    $inner .= '
                    <span class="sb-meta item-likes">
                        ' . $param['meta']['likes_data'] . '
                    </span>';
                }
            }
            $inner .= '
            </span>';
        } elseif ( ! empty($param['meta']['data']) && ! @$attr['carousel'] ) {
            $inner .= $param['meta']['data'];
        }
        // END: comments/likes block
        
        $inner .= '</div>';
        
        if (@$param['user']) {
            if (@$param['user']['title'] && @$param['user']['name']) {
                $user_text = ( @$param['user']['url'] ) ? '<a href="' . @$param['user']['url'] . '"'.$this->target.'>' . @$param['user']['name'] . '</a>' : @$param['user']['name'];
            } else {
                if (@$param['user']['status'])
                    $user_text = ( @$param['url'] ) ? '<a href="' . @$param['url'] . '"'.$this->target.'>' . @$param['user']['status'] . '</a>' : $param['user']['status'];
                else
                    $user_title_style = ' style="padding-top: 5px"';
            }
            
            $user_inner = '';
            if (@$param['user']['image']) {
                $user_image = ( @$param['user']['url'] ) ? '<a href="' . @$param['user']['url'] . '"'.$this->target.'><img class="sb-img" alt="' . @$param['user']['name'] . '" src="' . $param['user']['image'] . '"></a>' : '<img class="sb-img" alt="' . @$param['user']['name'] . '" src="' . $param['user']['image'] . '">';
                $user_inner .= '
                <div class="sb-uthumbcon"><div class="sb-uthumb">'.$user_image.'</div></div>';
            } else {
                $no_thumb_class = ' sb-nouthumb';
            }
            if ($user_title || @$user_text) {
                $user_title_linked = ( @$param['user']['url'] ) ? '<a href="' . @$param['user']['url'] . '"'.$this->target.'>'.$user_title.'</a>' : $user_title;
                $user_inner .= '
                    <div class="sb-uinfo'.@$no_thumb_class.'">';
                if ($user_title)
                    $user_inner .= '<div class="sb-utitle"'.@$user_title_style.'>' . $user_title_linked . '</div>';
                if (@$user_text && $user_title)
                    $user_inner .= '<div class="sb-uname">' . $user_text . '</div>';
                $user_inner .= '
                    </div>';
            }

            if (@$output['user'] && ($user_title || @$user_image) ) {
                $inner .= '
                <div class="sb-inner3">
                    <div class="sb-user">';
                $inner .= $user_inner;
                $inner .= '
                    </div>
                </div>';
            }
        }
        
        $us = '';
        if ($param['date'] && @$output['info'])
        $us .= '
            <div class="sb-date">
                <a href="' . @$param['url'] . '"'.$this->target.'>'.ss_lang( 'posted' ).': ' . ss_friendly_date($param['date']) . '</a>
            </div>';
        if (@$param['url'] && @$output['share']) {
            if (@$param['share'])
                $us .= $param['share'];
            else {
            $sharetitle = @urlencode( strip_tags($param['title']) );
            $sharemedia = @urlencode( $param['thumb'] );
            $us .= '
                <span class="sb-share">
                    <a class="sb-facebook sb-hover" href="https://www.facebook.com/sharer.php?u=' . urlencode($param['url']) . '&t=' . @$sharetitle . '"'.$this->target.'>
                        <i class="sb-sicon sb-facebook"></i>
                    </a>
                    <a class="sb-twitter sb-hover" href="https://twitter.com/share?url=' . urlencode($param['url']) . '&text=' . @$sharetitle . '"'.$this->target.'>
                        <i class="sb-sicon sb-twitter"></i>
                    </a>
                    <a class="sb-pinterest sb-hover" href="//pinterest.com/pin/create/link/?url=' . urlencode($param['url']) . '&amp;description=' . @$sharetitle . '&amp;media=' . @$sharemedia . '"'.$this->target.'>
                        <i class="sb-sicon sb-pinterest"></i>
                    </a>
                    <a class="sb-linkedin sb-hover" href="https://www.linkedin.com/shareArticle?mini=true&url=' . urlencode($param['url']) . '&title=' . @$sharetitle . '"'.$this->target.'>
                        <i class="sb-sicon sb-linkedin"></i>
                    </a>
                </span>';
            }
        }
        if (@$us)
            $inner .= '
            <div class="sb-info">
                ' . $us . '
            </div>';
        
        if ( ! $no_inner)
            $inner .= '
            </div>';
        
        if ( $attr['type'] == 'timeline' ) {
            $icon = ( @$param['icon'][1] ) ? '<img class="sb-img" src="'.$param['icon'][1].'" style="vertical-align:middle" alt="' . $feed_class . '">' : '<i class="sb-bico sb-wico sb-' . $param['type'] . '"></i>';
            $out = '
          <div class="timeline-row"'.$idstr.'>
            <div class="timeline-time">
              <small>'. sb_i18n_date( $param['date'], get_option( 'date_format' ) ) .'</small>'. sb_i18n_date( $param['date'], get_option( 'time_format' ) ) .'
            </div>
            <div class="timeline-icon">
              <div class="bg-' . $feed_class . '">
                ' . $icon . '
              </div>
            </div>
            <div class="timeline-content">
              <div class="panel-body sb-item sb-' . $feed_class . $filtername . $sbinline.'"'.$inline.'>
              ' . $inner . '
              </div>
            </div>
          </div>
        </div>' . "\n";
        } else {
            $tag = ( $attr['type'] != 'feed' || @$attr['carousel'] ) ? 'div' : 'li';
            $out1 = '
            <'.$tag.' class="sb-item sb-' . $feed_class . $filtername . $sbinline.'"'.$idstr.$inline.'>
                ' . $inner;
            $out1 .= '
            </div>
            </'.$tag.'>' . "\n";
            
            $out = (@$attr['carousel']) ? '<li>'.$out1.'</li>' : $out1;
        }
        return $out;
    }

    function create_colors( $social_colors, $feed_keys, $type, $dotboard, $attr, $themetypeoption ) {
        $style = array();
        foreach ($feed_keys as $netKey => $netVal) {
            $network = @key($netVal);
            $colorVal = $social_colors[$network];
            if (@$colorVal && @$colorVal != 'transparent' ) {
                // set colors for networks
                $rgbColorVal = ss_hex2rgb($colorVal);
                
                if (@$attr['lightboxtype'] == 'slideshow')
                    $style['.sb-slide-icon.sb-'.@$feed_keys[$colorKey]][] = 'background-color: '.$colorVal.' !important';
                    
                if ( $type == 'timeline' )
                    $style[$dotboard.' .bg-'.$network][] = 'background-color: rgba('.$rgbColorVal.', 0.8) !important';

                $dotfilter = ( $type == 'wall' ) ? str_replace(array('timeline', '.sboard'), array('sb', ''), $dotboard) : $dotboard;
                $style[$dotfilter.' .sb-'.$network.' .sb-iconm::before'][] = 'border-color: transparent '.$colorVal.' transparent transparent';
                if (!@$attr['carousel'])
                    $style[$dotfilter.' .sb-'.$network.'.sb-hover:hover, '.$dotfilter.' .sb-'.$network.'.active'][] = 'background-color: '.$colorVal.' !important;border-color: '.$colorVal.' !important;color: #fff !important';
                
                // set colors for tabs
                if (@$attr['tabable']) {
                    if (@$attr['position'] == 'normal')
                        $style["$dotboard.tabable .sb-tabs .sticky .".$network.":hover, $dotboard.tabable .sb-tabs .sticky .".$network.".active"][] = 'border-bottom-color: '.$colorVal;
                    else
                        $style["$dotboard.tabable .sb-tabs .sticky .".$network.":hover, $dotboard.tabable .sb-tabs .sticky .".$network.".active"][] = 'background-color: '.$colorVal;
                }
            }
        }
        
        // set item background color
        if ( @$attr['item_background_color'] ) {
            $style["$dotboard .sb-item .sb-inner"][] = 'background-color: '.$attr['item_background_color'];
        }
        if ( ! @$this->output['info'] && ! @$this->output['share']) {
            $style["$dotboard .sb-item .sb-user"][] = 'border-bottom: 0';
        }
        
        // set item border
        if ( @$attr['item_border_color'] ) {
            $dontbordersize = true;
            $style["$dotboard .sb-item .sb-container"][] = 'border: '.@$attr['item_border_size'].'px solid '.$attr['item_border_color'];
        }
        if ( @$attr['item_border_size'] && ! @$dontbordersize ) {
            $style["$dotboard .sb-item .sb-container"][] = 'border-width: '.@$attr['item_border_size'].'px';
        }
        // set footer color
        if ( @$attr['font_color'] && @$attr['font_color']) {
            $font_rgbColorVal = csw_hex2rgb($attr['font_color']);
            $style[$dotboard.'.sb-modern2 .sb-item .sb-info a'][] = 'color: rgba('.$font_rgbColorVal.', 0.8) !important';
        }
        $style["$dotboard .sb-icon"][] = 'background-image: url('.SB_PATH.'public/img/social-icons-flat.png);';
        
        return $style;
    }
}
