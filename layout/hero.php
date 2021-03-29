<?php
/**
 * PHP Social Stream
 * Copyright 2018 Axent Media (axentmedia@gmail.com)
 */

class ss_hero_layout extends ss_layout{
	public $target;
	
	function create_item( $feed_class, $param, $attr = array(), $output = array(), $sbi = 0, $i = 0, $ifeed = 0 ) {
		$filtername = ' '.$feed_class.'-'.$i.'-'.$ifeed;
		$playstate = (@$param['play']) ? '<div class="sb-playstate"></div>' : '';
		$user_title = (@$param['user']['title']) ? $param['user']['title'] : $param['user']['name'];
		$datasize = (@$param['size']) ? ' data-size="' . $param['size'] . '"' : '';
		$imglayout = (@$attr['layout_image']) ? ' sb-'.$attr['layout_image'] : '';
		$imgAlt = "";

		$noclass = array();
		if ( ! @$output['info'])
			$noclass[] = ' sb-nofooter';
		if ( ! @$param['thumb'] || ! @$output['thumb'])
			$noclass[] = ' sb-nothumb';
		if ( count($noclass) > 1 ) {
			$noclass = array();
			$noclass[] = ' sb-noft';
		}
		
		//$no_meta = ( ! empty($param['meta']) && (@$output['comments'] || @$output['likes']) ) ? false : true;
		$no_meta = ( (@$param['meta']['comments_data'] || @$param['meta']['likes_data']
				|| @$param['meta']['comments_total_count'] || @$param['meta']['likes_total_count'])
			&& (@$output['comments'] || @$output['likes']) ) ? false : true;
		if ($no_meta)
			$noclass[] = ' sb-nometa';

		// thumb block
		$thumb = '';
		if ( ! empty($param['thumb']) && @$output['thumb']) {
			$iframe = (@$param['iframe']) ? ' ' . $param['iframe'] : '';
			$aurl = htmlspecialchars(@$param['thumburl'] ? $param['thumburl'] : @$param['url']);
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
			$thumb .= '
			<img class="ax-slider__main-img sb-img" ' . $sbimg . ' alt="' . $imgAlt . '" />'.$playstate;
		}
		
		$idstr = ' id="'.$sbi.'"';
		if (@$attr['iframe'] == 'slide') {
			$inline = ' data-href="#inline_'.$sbi.'"';
			$sbinline = ' sb-inline';
		} else {
			$inline = ' data-href="' . @htmlspecialchars($param['thumb']) . '"';
			$sbinline = @$iframe;
		}

		$date_text = ($param['date'] && @$output['info']) ? '<a href="' . @$param['url'] . '"'.$this->target.'>' . ss_friendly_date($param['date'], $attr['dateformat']) . '</a>' : '';
		
		$header = $headerinfo = $user_text = '';
		// user block
		if (@$param['user'] && @$output['user']) {
			if (@$param['user']['title'] && ! empty($param['user']['name']) ) {
				$user_title = @$param['user']['title'];
				$user_name = '@'.$param['user']['name'];
				$user_text = ( @$param['user']['url'] ) ? '<a href="' . @$param['user']['url'] . '"'.$this->target.'>' . $user_name . '</a>' : $user_name;
			} else {
				$user_title = @$param['user']['name'];
				if (@$param['user']['status'])
					$user_text = ( @$param['url'] ) ? '<a href="' . @$param['url'] . '"'.$this->target.'>' . @$param['user']['status'] . '</a>' : $param['user']['status'];
			}
			
			if (@$param['user']['image']) {
				$user_image = ( @$param['user']['url'] ) ? '<a href="' . @$param['user']['url'] . '"'.$this->target.'><img class="ax-slider__avatar-img sb-img" alt="' . @$param['user']['name'] . '" src="' . $param['user']['image'] . '"></a>' : '<img class="ax-slider__avatar-img sb-img" alt="' . @$param['user']['name'] . '" src="' . $param['user']['image'] . '">';
				$headerinfo .= '
					<div class="ax-slider__avatar">
						' . $user_image . '
					</div>';
			} else {
				$no_thumb_class = ' sb-nouthumb';
			}
			
			if ($user_title || $user_text) {
				$user_title_linked = ( @$param['user']['url'] ) ? '<a href="' . @$param['user']['url'] . '"'.$this->target.'>'.$user_title.'</a>' : $user_title;
				$headerinfo .= '
					<div class="ax-slider__info'.@$no_thumb_class.'">
						<div class="ax-slider__name">' . $user_title_linked . '</div>
						<div class="ax-slider__profile-link">
							' . $user_text . ( $user_text ? ' / ' : '' ) . $date_text . '
						</div>
					</div>';
			}

			$headerinfo .= '
				<i class="ax-slider__icon-type">
					<i class="fab fa-' . $feed_class . '"></i>
				</i>';

			if ( ! empty($headerinfo) ) {
				$header .= '
					<div class="ax-slider__header">
						' . $headerinfo . '
					</div>';
			}
		}
		
		// body block
		$body = '';
		if ( (@$param['text'] && @$output['text']) ) {
			$expandclass = ( ! @$thumb) ? ' sb-expand' : '';

			if (@$param['title'] && @$output['title'] && @$attr['carousel']) {
				// @todo: handle title
				$inner_title = '
				<span class="sb-title">
					' . $param['title'] . '
				</span>';
			} else {
				$expandclass .= ' sb-notitle';
			}
			
			$body .= '
			<div class="ax-slider__body'.$expandclass.'">
				<div class="ax-slider__description">
					' . @$inner_title . '
					' . $param['text'] . '
				</div>
			</div>';
		}

		$footer = $metashare = '';
		// comments/likes block
		if ( ! $no_meta ) {
			$ticons = ['likes' => 'far fa-heart', 'comments' => 'fas fa-comment', 'retweets' => 'fas fa-retweet'];
			if ( ! empty($param['meta']) && (@$output['comments'] || @$output['likes']) ) {
				
				$paramMetas = array(
					"comments" => [
						"total" => array(
							"count" => @$param['meta']["comments_total_count"]
						),
					],
					"likes" => [
						"total" => array(
							"count" => @$param['meta']["likes_total_count"]
						),
					]
				);
				foreach ($paramMetas as $key => $meta) {
					
					if ( ! empty($meta['total']) ) {
						if (@$meta['total']['count']) {
							$metashare .= '
							<div class="ax-slider__'.$key.'">'
							. ( isset($meta['total']['url']) ? '<a href="' . $meta['total']['url'] . '"'.$this->target.'>' : '') . '
								<i class="ax-slider__icon-footer '.$ticons[$key].'" title="' . ucfirst( ss_lang( $key ) ) . '"></i>
								<span class="ax-slider__label">' . countFormatter($meta['total']['count']) . '</span>'
								. ( isset($meta['total']['url']) ? '</a>' : '') . '
							</div>';
						}
					}
				}
			}
		}
		// END: comments/likes block
		
		// share block
		if ( @$param['url'] && @$output['share'] ) {
				$sharetitle = @urlencode( strip_tags($param['title']) );
				$sharemedia = @urlencode( $param['thumb'] );
				$metashare .= '
				<div class="ax-slider__share">
					<a class="ax-slider__share-icon ax-slider__share-icon--facebook" href="https://www.facebook.com/sharer.php?u=' . urlencode($param['url']) . '&amp;t=' . @$sharetitle . '"'.$this->target.'>
						<i class="fab fa-facebook-f"></i>
					</a>
					<a class="ax-slider__share-icon ax-slider__share-icon--twitter" href="https://twitter.com/share?url=' . urlencode($param['url']) . '&amp;text=' . @$sharetitle . '"'.$this->target.'>
						<i class="fab fa-twitter-square"></i>
					</a>
					<a class="ax-slider__share-icon ax-slider__share-icon--pinterest" href="//pinterest.com/pin/create/link/?url=' . urlencode($param['url']) . '&amp;description=' . @$sharetitle . '&amp;media=' . @$sharemedia . '"'.$this->target.'>
						<i class="fab fa-pinterest-square"></i>
					</a>
					<a class="ax-slider__share-icon ax-slider__share-icon--linkedin" href="https://www.linkedin.com/shareArticle?mini=true&amp;url=' . urlencode($param['url']) . '&amp;title=' . @$sharetitle . '"'.$this->target.'>
						<i class="fab fa-linkedin"></i>
					</a>
					<a href="javascript:void(0)" class="ax-slider__share-icon ax-slider__share-icon--main">
						<i class="fas fa-share-alt"></i>
					</a>
				</div>';
			if (@$param['share']){
				$metashare .= '<div class="ax-slider__param_share">'.$param['share'].'</div>';
			}
			
		}

		// footer block
		if ( ! empty($metashare) ) {
			$footer .= '
			<div class="ax-slider__footer">
				' . $metashare . '
			</div>';
		}
		
		$iframe = (@$param['iframe']) ? ' '.$param['iframe'] : '';
		$out = '
			<div class="ax-slider__item sb-' . $feed_class . $filtername . $sbinline . $iframe .'"'.$idstr.$inline.$datasize.'>
				' . $thumb . '
				<div class="ax-slider__overlay"></div>
				<div class="ax-slider__inner' . ( implode('', $noclass) ) . '">
					' . $header . '
					' . $body . '
					' . $footer . '
				</div>
			</div>';
		$out .= "\n";

		return $out;
	}
}
