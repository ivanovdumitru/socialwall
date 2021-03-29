<?php

/**
 * PHP Social Stream
 * Copyright 2018 Axent Media (support@axentmedia.com)
 */

class ss_modern_layout extends ss_default_layout {
    public $target;
    
    function create_item( $feed_class, $param, $attr = array(), $output = array(), $sbi = 0, $i = 0, $ifeed = 0 ) {
        $filtername = ' '.$feed_class.'-'.$i.'-'.$ifeed;
        $iconSocial = ( @$param['icon'][0] ) ? '<img src="'.$param['icon'][0].'" class="sb-img origin-flag" style="vertical-align:middle" alt="' . $feed_class . '">' : '<span class="origin-flag sb-' . $feed_class . '"><i class="sb-icon sb-' . $feed_class . '"></i></span>';
        $playstate = (@$param['play']) ? '<div class="sb-playstate"></div>' : '';
        $user_title = (@$param['user']['title']) ? $param['user']['title'] : $param['user']['name'];
        $imglayout = (@$attr['layout_image']) ? ' sb-'.$attr['layout_image'] : '';
		$datasize = (@$param['size']) ? ' data-size="' . $param['size'] . '"' : '';
		$imgAlt = $this->sb_create_alt($feed_class, $param);
        
        $noclass = array();
        if ( ! @$output['info'])
            $noclass[] = ' sb-nofooter';
        if ( ! @$param['thumb'] || ! @$output['thumb'])
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
            
        $inner = '<div class="sb-container'.$imglayout.( implode('', $noclass) ).'">'.$iconSocial;
        
        $thumb = $sbthumb = '';
        if (@$attr['carousel']) {
            $cropclass = 'sb-crop';
            if (@$param['iframe'])
                $cropclass .= ' '.$param['iframe'];
            if (@$param['thumb'] && @$output['thumb']) {
				$aurl = (@$param['thumburl'] ? $param['thumburl'] : @$param['url']);
				if (@$param['object'] && @$attr['iframe'] == 'media') {
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
                if (@$param['user']['image'] && @$attr['layout_user'] == 'usernopic') {
                    $cropclass .= ' sb-userimg';
                    $thumb = '<div class="'.$cropclass.'"><img class="sb-img" src="' . $param['user']['image'] . '" alt="'.$user_title.'"><br /><span>'.$user_title.'</span></div>';
                }
            }
            if (@$thumb)
            $sbthumb .= '
            <div class="sb-thumb">
                ' . $thumb . '
            </div>';
        } else {
            if (@$param['thumb'] && @$output['thumb']) {
                $aurl = htmlspecialchars(@$param['thumburl'] ? $param['thumburl'] : @$param['url']);
                $iframe = (@$param['iframe']) ? ' class="'.$param['iframe'].'"' : '';
				if (@$param['object'] && @$attr['iframe'] == 'media') {
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
                $sbthumb .= '
                <div class="sb-thumb">
                    <a href="' . $aurl . '"'.@$iframe.$datasize.$this->target.'><img class="sb-img" '.$sbimg.' alt="' . $imgAlt . '">'.$playstate.'</a>
                </div>';
            } else {
                if (@$attr['carousel'] && @$param['user']['image']) {
                    $cropclass = 'sb-crop sb-userimg';
                    $thumb = '<div class="'.$cropclass.'"><img class="sb-img" src="' . $param['user']['image'] . '" alt="' . $param['user']['name'] . '"><br /><span>'.$user_title.'</span></div>';
                    $sbthumb .= '
                    <div class="sb-thumb">
                        ' . $thumb . '
                    </div>';
                }
            }
        }

        if (@$sbthumb && @$attr['layout_image'] == 'imgexpand') {
            $inner .= $sbthumb;
            $userclass = ' sb-usermini';
        }
        
        $idstr = ' id="'.$sbi.'"';
        if (@$attr['iframe'] == 'slide') {
            $inline = ' data-href="#inline_'.$sbi.'"';
            $sbinline = ' sb-inline';
        } else {
            $inline = $sbinline = '';
        }

        if ( ! $no_inner)
            $inner .= '
            <div class="sb-inner">';
            
        if (@$param['user'] && @$attr['layout_user'] == 'userpic') {
            if (@$param['user']['title'] && @$param['user']['name']) {
                $user_title = @$param['user']['title'];
                $user_text = ( @$param['user']['url'] ) ? '<a href="' . @$param['user']['url'] . '"'.$this->target.'>' . @$param['user']['name'] . '</a>' : @$param['user']['name'];
            } else {
                $user_title = @$param['user']['name'];
                if (@$param['user']['status'])
                    $user_text = ( @$param['url'] ) ? '<a href="' . @$param['url'] . '"'.$this->target.'>' . @$param['user']['status'] . '</a>' : $param['user']['status'];
                else
                    $user_title_style = ' style="padding-top: 5px"';
            }
            
            $user_inner = '';
            if (@$param['user']['image']) {
                $user_image = ( @$param['user']['url'] ) ? '<a href="' . @$param['user']['url'] . '"'.$this->target.'><img class="sb-img" alt="' . @$param['user']['name'] . '" src="' . $param['user']['image'] . '"></a>' : '<img class="sb-img" alt="' . @$param['user']['name'] . '" src="' . $param['user']['image'] . '">';
                $user_inner .= '
                <div class="sb-uthumb">'.$user_image.'</div>';
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
                <div class="sb-user'.@$userclass.'">';
                $inner .= $user_inner;
                $inner .= '
                </div>';
            }
        }
        
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

            if (@$param['title'] && @$output['title'] && @$attr['carousel']) {
                $inner_title = '
                <span class="sb-title">
                    ' . $param['title'] . '
                </span>';
            } else {
                $expandclass .= ' sb-notitle';
            }
            
            $inner .= '<span class="sb-text'.$expandclass.'">'.@$inner_title;
            $inner .= @$param['text'];
            $inner .= '</span>';
            
            $is_carousel_text = true;
        }
        
        if (!@$attr['carousel']) {
	        if (@$param['tags'] && @$output['tags']) {
	            $inner .= '
	            <span class="sb-text">
	                <strong>'.ss_lang( 'tags' ).': </strong>' . $param['tags'] . '
	            </span>';
            }
        }

        // comments/likes block
        if ( ! $no_meta ) {
			$NoText = (!@$is_carousel_text) ? 'sb-no-ctext' : '';
            $inner .= '
            <span class="sb-metadata '. $NoText .'">';
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
                if ( ! empty($param['meta']['likes_data']) && ! @$attr['carousel'] )
                    $inner .= '
                    <span class="sb-meta item-likes">
                        ' . $param['meta']['likes_data'] . '
                    </span>';
            }
            $inner .= '
            </span>';
        } elseif ( ! empty($param['meta']['data']) && ! @$attr['carousel'] ) {
            $inner .= $param['meta']['data'];
        }
        // END: comments/likes block

        $us = '';
        if ( @$param['user'] && @$output['user'] && @$attr['layout_user'] == 'usernopic' ) {
            $user_text = ( @$param['user']['url'] ) ? '<a href="' . @$param['user']['url'] . '"'.$this->target.'>' . @$param['user']['name'] . '</a>' : @$param['user']['name'];
            $us .= '
            <span class="sb-user-foot">
                <i class="sb-bico sb-user-foot"></i> ' . $user_text . '
            </span>';
        }
        if ( @$param['url'] && @$output['share'] ) {
            if (@$param['share'])
                $us .= $param['share'];
            else {
            $sharetitle = @urlencode( strip_tags($param['title']) );
            $sharemedia = @urlencode( $param['thumb'] );
            $us .= '
                <span class="sb-share">
                    <a class="sb-facebook sb-hover" href="https://www.facebook.com/sharer.php?u=' . urlencode($param['url']) . '&amp;t=' . @$sharetitle . '"'.$this->target.'>
                        <i class="sb-sicon sb-facebook">Facebook</i>
                    </a>
                    <a class="sb-twitter sb-hover" href="https://twitter.com/share?url=' . urlencode($param['url']) . '&amp;text=' . @$sharetitle . '"'.$this->target.'>
                        <i class="sb-sicon sb-twitter">Twitter</i>
                    </a>
                    <a class="sb-pinterest sb-hover" href="//pinterest.com/pin/create/link/?url=' . urlencode($param['url']) . '&amp;description=' . @$sharetitle . '&amp;media=' . @$sharemedia . '"'.$this->target.'>
                        <i class="sb-sicon sb-pinterest">Pinterest</i>
                    </a>
                    <a class="sb-linkedin sb-hover" href="https://www.linkedin.com/shareArticle?mini=true&amp;url=' . urlencode($param['url']) . '&amp;title=' . @$sharetitle . '"'.$this->target.'>
                        <i class="sb-sicon sb-linkedin">LinkedIn</i>
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
              <small>'. ss_i18n_date($param['date'], SB_DT_FORMAT) .'</small>'. ss_i18n_date($param['date'], SB_TT_FORMAT) .'
            </div>
            <div class="timeline-icon">
              <div class="bg-' . $feed_class . '">
                ' . $icon . '
              </div>
            </div>
            <div class="timeline-content">
              <div class="panel-body sb-item sb-' . $feed_class . $filtername . $sbinline . '"'.$inline.'>
              ' . $inner . '
              </div>
            </div>
          </div>
        </div>' . "\n";
        } else {
            $iconType = ( @$param['icon'][0] ) ? '<img class="sb-img" src="'.$param['icon'][0].'" style="vertical-align:middle" alt="' . $feed_class . '">' : '<i class="sb-bico sb-' . $param['type'] . '" title="' . ucfirst($param['type']) . '"></i>';
            $tag = ( $attr['type'] != 'feed' || @$attr['carousel'] ) ? 'div' : 'li';
            $out1 = '
            <'.$tag.' class="sb-item sb-' . $feed_class . $filtername . $sbinline.'"'.$idstr.$inline.'>
                ' . $inner;
            if ($param['date'] && @$output['info'])
            $out1 .= '
                <div class="sb-foot">
                    <div class="sb-footer">
                        ' . $iconType . '
                        <a href="' . @$param['url'] . '"'.$this->target.'>'.ss_lang( 'posted' ).': ' . ss_friendly_date($param['date']) . '</a>
                    </div>
                </div>';
            $out1 .= '
            </div>
            </'.$tag.'>' . "\n";
            
            $out = (@$attr['carousel']) ? '<li>'.$out1.'</li>' : $out1;
        }
        return $out;
    }

    function create_colors($social_colors, $feed_keys, $type, $dotboard, $attr, $themetypeoption) {
        $style = array();
        foreach ($feed_keys as $netKey => $netVal) {
            $network = @key($netVal);
            $colorVal = $social_colors[$network];
            if (@$colorVal && @$colorVal != 'transparent') {
                // set colors for networks
                $rgbColorVal = ss_hex2rgb($colorVal);
                $style[$dotboard.' .origin-flag.sb-'.@$network][] = 'background-color: rgba('.$rgbColorVal.', 0.8) !important';
                $style[$dotboard.' .origin-flag.sb-'.@$network.':after'][] = 'border-left: 8px solid rgba('.$rgbColorVal.', 1) !important';
                
                if (@$attr['iframe'] == 'slide')
                    $style['.sb-slide-icon.sb-'.@$network][] = 'background-color: '.$colorVal.' !important';
                    
                if ( $type == 'timeline' )
                    $style[$dotboard.' .bg-'.$network][] = 'background-color: rgba('.$rgbColorVal.', 0.8) !important';

                $dotfilter = ( $type == 'wall' ) ? str_replace(array('timeline', '.sboard'), array('sb', ''), $dotboard) : $dotboard;
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
        if ( @$themetypeoption['item_background_color'] ) {
            if ( $themetypeoption['item_background_color'] != 'transparent') {
                $css_bg_color = ($type != 'timeline') ? "$dotboard .sb-item .sb-container .sb-inner, $dotboard .sb-item .sb-foot" : "$dotboard .sb-item .sb-container";
                $style[$css_bg_color][] = 'background-color: '.$themetypeoption['item_background_color'];
            }
        }
        
        // set item border
        if ( $border_size = @$themetypeoption['item_border_size'] ) {
            if ( $border_size > 1 ) {
                $border_radius = 5+$border_size-1;
                $style["$dotboard .sb-item .sb-container"][] = 'border-radius: '.$border_radius.'px;-moz-border-radius: '.$border_radius.'px;-webkit-border-radius: '.$border_radius.'px';
                $style["$dotboard.sb-modern .origin-flag"][] = 'margin-right: -8px';
            }
        }
        if ( @$themetypeoption['item_border_color'] ) {
            if ( $themetypeoption['item_border_color'] != 'transparent') {
                $dontbordersize = true;
                $style["$dotboard .sb-item .sb-container"][] = 'border: '.@$themetypeoption['item_border_size'].'px solid '.$themetypeoption['item_border_color'];
            }
        }
        if ( @$themetypeoption['item_border_size'] && ! @$dontbordersize ) {
            $style["$dotboard .sb-item .sb-container"][] = 'border-width: '.@$themetypeoption['item_border_size'].'px';
        }
        
        // set footer color
        if ( @$themetypeoption['font_color'] && @$themetypeoption['font_color'] != 'transparent') {
            $font_rgbColorVal = ss_hex2rgb($themetypeoption['font_color']);
            $style[$dotboard.'.sb-modern .sb-item .sb-footer a'][] = 'color: rgba('.$font_rgbColorVal.', 0.8) !important';
        }
        
        return $style;
    }
}
?>