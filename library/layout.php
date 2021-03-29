<?php

class ss_layout {
  
	function sb_create_alt($feed_class, $param) {
		if ( isset($param["title"]) && ! empty($param["title"]) ) {
			return strip_tags($param["title"]);
		}
		elseif ( isset($param["text"]) && ! empty($param["text"]) && ! empty( preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $param["text"]) ) ) {
			$alt = preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $param["text"]);
			return ( mb_strlen( strip_tags($alt), 'utf8') <= 100 ) ?  strip_tags($alt) : substr( strip_tags($alt), 0, 96 ) . "....";
		}
		elseif ( isset($param["user"]["title"]) && ! empty($param["user"]["title"]) ) {
			return strip_tags($param["user"]["title"]);
		}
		elseif ( isset($param["user"]["name"]) && ! empty($param["user"]["name"]) ) {
			return strip_tags($param["user"]["name"]);
		}
		else {
			return $feed_class;
		}
  }

}
