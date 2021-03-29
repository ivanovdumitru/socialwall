<?php

class ss_brick_layout extends ss_layout {
	public $grid_counter = 5;
	public $item_counter = 0;
	public $target;

	function create_item( $feed_class, $param, $attr = array(), $output = array(), $sbi = 0, $i = 0, $ifeed = 0 ) {
		$filtername = ' '.$feed_class.'-'.$i.'-'.$ifeed;
		$iconSocial = ( @$param['icon'][0] ) ? '<img src="'.$param['icon'][0].'" class="sb-img origin-flag" style="vertical-align:middle" alt="' . $feed_class . '">' : '<span class="sb-iconm sb-' . $feed_class . '"><i class="sb-iconm-inner sb-icon sb-' . $feed_class . '"></i></span>';
		$playstate = (@$param['play']) ? '<div class="sb-playstate"></div>' : '';
		$datasize = (@$param['size']) ? ' data-size="' . $param['size'] . '"' : '';
		
		$noclass = array();
		if ( ! @$output['info'])
			$noclass[] = ' sb-nofooter';
		if ( ! @$param['thumb'] || ! @$output['thumb'])
			$noclass[] = ' sb-nothumb';
		if ( count($noclass) > 1 ) {
			$noclass = array();
			$noclass[] = ' sb-noft';
		}
		$inner = '<div class="sb-container'.( implode('', $noclass) ).'">';

		$thumb = $sbthumb = '';
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
			$sbthumb = '
			<div class="sb-thumb">
				<a href="' . $aurl . '"'.$iframe.$datasize.$this->target.'><img class="sb-img" data-original="' . htmlspecialchars($param['thumb']) . '" src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iLTQzIC00MyAxMjQgMTI0IiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHN0cm9rZT0iI2ZmZiI+ICAgIDxnIGZpbGw9Im5vbmUiIGZpbGwtcnVsZT0iZXZlbm9kZCI+ICAgICAgICA8ZyB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxIDEpIiBzdHJva2Utd2lkdGg9IjIiPiAgICAgICAgICAgIDxjaXJjbGUgc3Ryb2tlLW9wYWNpdHk9Ii41IiBjeD0iMTgiIGN5PSIxOCIgcj0iMTgiLz4gICAgICAgICAgICA8cGF0aCBkPSJNMzYgMThjMC05Ljk0LTguMDYtMTgtMTgtMTgiPiAgICAgICAgICAgICAgICA8YW5pbWF0ZVRyYW5zZm9ybSAgICAgICAgICAgICAgICAgICAgYXR0cmlidXRlTmFtZT0idHJhbnNmb3JtIiAgICAgICAgICAgICAgICAgICAgdHlwZT0icm90YXRlIiAgICAgICAgICAgICAgICAgICAgZnJvbT0iMCAxOCAxOCIgICAgICAgICAgICAgICAgICAgIHRvPSIzNjAgMTggMTgiICAgICAgICAgICAgICAgICAgICBkdXI9IjFzIiAgICAgICAgICAgICAgICAgICAgcmVwZWF0Q291bnQ9ImluZGVmaW5pdGUiLz4gICAgICAgICAgICA8L3BhdGg+ICAgICAgICA8L2c+ICAgIDwvZz48L3N2Zz4=" alt="">'.$playstate.'</a>
			</div>';
		}
		$inner .= $sbthumb;
		
		$idstr = ' id="'.$sbi.'"';
		if (@$attr['lightboxtype'] == 'slideshow') { //chang:(@$attr['iframe'] == 'slide') 
			$inline = ' data-href="#inline_'.$sbi.'"';
			$sbinline = ' sb-inline';
		} else {
			$inline = $sbinline = '';
		}
		
		$inner .= $iconSocial;
		$inner .= '
			<div class="sb-inner">
				<div class="sb-inner2">';

		if (@$param['text'] && @$output['text']) {
			$expandclass = ( ! @$thumb) ? ' sb-expand' : '';
			$inner .= '<span class="sb-text'.$expandclass.'">';
			if (@$attr['scroll'])
				$inner .= '<p class="marquee">';

			if (@$param['title'] && @$output['title']) {
				$inner .= '
				<span class="sb-title">
					' . $param['title'] . '
				</span>';
			}
			$inner .= @$param['text'];

			if (@$attr['scroll'])
				$inner .= '</p>';

			$inner .= '</span>';
		}

		if (@$param['tags'] && @$output['tags']) {
			$inner .= '
			<span class="sb-text">
				' . $param['tags'] . '
			</span>';
		}
		$inner .= '</div>';

		if (@$output['user'] || @$output['comments'] || @$output['likes']) {
			$inner .= '
			<div class="sb-inner3">
				<div>';

			if (@$param['user']) {
				if (@$param['user']['title'] && @$param['user']['name']) {
					$user_title = @$param['user']['title'];
				} else {
					$user_title = @$param['user']['name'];
				}

				$inner .= '
					<div class="sb-user">';
				if (@$param['user']['image']) {
					$user_image = ( @$param['user']['url'] ) ? '<a href="' . @$param['user']['url'] . '"'.$this->target.'><img class="sb-img" alt="' . @$param['user']['name'] . '" src="' . $param['user']['image'] . '"></a>' : '<img class="sb-img" alt="' . @$param['user']['name'] . '" src="' . $param['user']['image'] . '">';
					$inner .= '
					<div class="sb-uthumbcon"><div class="sb-uthumb">'.$user_image.'</div></div>';
				} else {
					$no_thumb_class = ' sb-nouthumb';
				}
				$user_title_linked = ( @$param['user']['url'] ) ? '<a href="' . @$param['user']['url'] . '"'.$this->target.'>'.$user_title.'</a>' : $user_title;
				$inner .= '
					<div class="sb-uinfo'.@$no_thumb_class.'">
						<div class="sb-utitle">' . $user_title_linked . '</div>';
				$inner .= '
					</div>
				</div>';
			}
			
			// comments/likes block
			if ( ! empty($param['meta']) && (@$output['comments'] || @$output['likes']) ) {
				$ticons = ['likes' => 'like', 'comments' => 'comments', 'retweets' => 'retweet'];
				$mc = 0;
				$inner .= '
				<span class="sb-metadata">';
				if ( ! empty($param['meta']) && (@$output['comments'] || @$output['likes']) ) {
					foreach ($param['meta'] as $key => $meta) {
						if ( ! empty($meta['total']) ) {
							if ($mc == 0 ) {
								$inner .= '<span class="sb-meta">';
								$mc = 1;
							}
							if (@$meta['total']['count']) {
								$inner .= '
									<span class="' . $key . '" title="' . ucfirst( __( $key, 'social-board' ) ) . '">'
									. ( isset($meta['total']['url']) ? '<a href="' . $meta['total']['url'] . '"'.$this->target.'>' : '') . '
										<i class="sb-bico sb-'.(@$meta['total']['class'] ? $meta['total']['class'] : $ticons[$key]).'"></i> ' . countFormatter($meta['total']['count'])
									. ( isset($meta['total']['url']) ? '</a>' : '') . '
									</span>';
							}
						}
					}
					if ($mc == 1) {
						$inner .= '</span>';
						$mc = 0;
					}
				}
				$inner .= '
				</span>';
			}
			// END: comments/likes block
			$inner .= '
				</div>
			</div>';
		}

		$us = '';
		if ($param['date'] && @$output['info']) {
			$us .= '
				<div class="sb-date">';
			$us .= (@$param['url']) ? '<a href="' . @$param['url'] . '"'.$this->target.'>' . ss_friendly_date($param['date'], $attr['dateformat']) . '</a>' : sb_friendly_date($param['date'], $attr['dateformat']);
			$us .= '
				</div>';
		}
		if (@$param['url'] && @$output['share']) {
			if (@$param['share'])
				$us .= $param['share'];
			else {
				$sharetitle = @urlencode( strip_tags($param['title']) );
				$us .= '
					<span class="sb-share">
						<a class="sb-facebook sb-hover" href="https://www.facebook.com/sharer.php?u=' . urlencode($param['url']) . '&t=' . @$sharetitle . '"'.$this->target.'>
							<i class="sb-sicon sb-facebook"></i>
						</a>
						<a class="sb-twitter sb-hover" href="https://twitter.com/share?url=' . urlencode($param['url']) . '&text=' . @$sharetitle . '"'.$this->target.'>
							<i class="sb-sicon sb-twitter"></i>
						</a>
						<a class="sb-pinterest sb-hover" href="//pinterest.com/pin/create/link/?url=' . urlencode($param['url']) . '&description=' . @$sharetitle . '"'.$this->target.'>
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
		$inner .= '
		</div>';
		
		$classes = 'sb-' . $feed_class . $filtername . $sbinline;
		return $this->set_column($attr, $classes, $inner, $idstr . $inline);
	}
	
	function create_colors($social_colors, $feed_keys, $type, $dotboard, $attr, $themetypeoption) {
		$social_colors = $social_colors;
		$style = array();
		foreach ($social_colors as $colorKey => $colorVal) {
			$feedname = $colorKey;
			// set colors for networks
			if (@$colorVal && @$colorVal != 'transparent' ) {
				if (@$this->attr['lightboxtype'] == 'slideshow')
					$style['.sb-slide-icon.sb-'.$feedname][] = 'background-color: '.$colorVal.' !important';

				$dotfilter = $dotboard;
				$style[$dotfilter.' .sb-'.$feedname.' .sb-iconm::before'][] = 'border-color: transparent '.$colorVal.' transparent transparent';
				$style[$dotfilter.' .sb-'.$feedname.'.sb-hover:hover, '.$dotfilter.' .sb-'.$feedname.'.active'][] = 'background-color: '.$colorVal.' !important;border-color: '.$colorVal.' !important;color: #fff !important';
			}
		}

		// set item background color
		if ( @$themetypeoption['item_background_color'] ) {
			$style["$dotboard .sb-item .sb-inner"][] = 'background-color: '.$themetypeoption['item_background_color'];
		}

		// set item border
		if ( @$themetypeoption['item_border_color'] ) {
			$dontbordersize = true;
			$style["$dotboard .sb-item .sb-container"][] = 'border: '.@$themetypeoption['item_border_size'].'px solid '.$themetypeoption['item_border_color'];
		}
		if ( @$themetypeoption['item_border_size'] && ! @$dontbordersize ) {
			$style["$dotboard .sb-item .sb-container"][] = 'border-width: '.@$themetypeoption['item_border_size'].'px';
		}

		return $style;
	}
	
	function set_column($attr, $classes = '', $inner, $inline_attr = '', $column_classes = '') {
		$out = '';
		$gridclass = '';
		
		if ($attr['columns_style'] == "1-2") {
			if( ! $attr["columns_twofold_js"] )
				if ($this->item_counter % 3 == 0)
					$out .= '<div class="sb-column'.$column_classes.'">';

			$gridclass = ($this->grid_counter == 5 || $this->grid_counter == 4) ? ' sb-twofold' : ' sb-solo';
			
			if ($this->grid_counter == 5) {
				$this->grid_counter = 0;
			} else {
				$this->grid_counter++;
			}
		} else {
			$out .= '<div class="sb-column'.$column_classes.'">';
		}

		$out .= '
		<div class="sb-item'.$gridclass.' ' . $classes .'"' . $inline_attr . '>
			' . $inner;
		$out .= '
		</div>
		</div>' . "\n";
		
		if ($attr['columns_style'] == "1-2") {
			if( ! $attr["columns_twofold_js"] )
				if ($this->item_counter % 3 == 2)
					$out .= '</div>';
				

			$this->item_counter++;
		} else {
			$out .= '</div>';
		}
		
		return $out;
	}
}
