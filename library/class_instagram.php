<?php

namespace SB_AX_SOCIAL\INSTAGRAM;

class SB_Instagram {
	public $cache, $forceCrawl, $debug_log;

	public function GetInstagramOption() {
		return ! empty($GLOBALS['api']['instagram']) ? $GLOBALS['api']['instagram'] : null;
	}

	public function GetInstagramAccounts() {
		$op = $this->GetInstagramOption();
		return ! empty($op['instagram_accounts']) ? $op['instagram_accounts'] : null;
	}

	public function GetId($feed_value) {
		$instagram_accounts = $this->GetInstagramAccounts();
		if ( is_array($instagram_accounts) or is_object($instagram_accounts) ) {
			if ( array_key_exists($feed_value, $instagram_accounts) ) {
				return $feed_value;
			} else {
				foreach ($instagram_accounts as $r) {
					if ($r["username"] == $feed_value or $r["access_token"] == $feed_value) {
						return $r["id"];
					}
				}
			}
		}
		if(is_numeric($feed_value)){
			return $feed_value;
		}
	}

	public function GetAccessToken($id)
	{
		$Accounts = $this->GetInstagramAccounts();
		if($Accounts){
			if(array_key_exists($id,$Accounts)){
				if(!empty($Accounts[$id]["access_token"])){
					return $Accounts[$id]["access_token"];
				}
			}
		}
		
	}

	public function GetUser($id) {
		$access_token = $this->GetAccessToken($id);
		
		$feed_url = "https://graph.instagram.com/me?fields=account_type,id,media_count,username,media&access_token=".$access_token;
		
		$is_cached = $this->cache->is_cached($feed_url);
		$content = ( ! $this->forceCrawl ) ? $this->cache->get_data($feed_url, $feed_url) : $this->cache->do_curl($feed_url);
		if ( empty($content) ) return;

		$feed = @json_decode($content);
		if ( isset($feed->account_type) ) {
			return $feed;
		} else {
			if ( ! empty($feed->error) ) {
				if ( ! empty($feed->error->message) && ! $is_cached ) {
					if ($this->debug_log)
						ss_debug_log( 'Instagram error: '.$feed->error->message.' - ' . $feed_url, SB_LOGFILE );
				}
				$feed = null;
			}
		}
		
		return;
	}

	public function GetMedias($id, $limit, $after = null) {
		$user = $this->GetUser($id);
		$access_token = $this->GetAccessToken($id);
		
		if ( (int) $limit < 1) $limit = 1;
		
		if ( ! empty($user) ) {
			$fields = "caption,id,media_type,media_url,permalink,thumbnail_url,timestamp,username,children{id,media_type,media_url,permalink,thumbnail_url,timestamp,username}";
			$feed_url = "https://graph.instagram.com/v1.0/{$user->id}/media?fields={$fields}&limit={$limit}&after={$after}&access_token=".$access_token;

			$is_cached = $this->cache->is_cached($feed_url);
			$content = ( ! $this->forceCrawl ) ? $this->cache->get_data($feed_url, $feed_url) : $this->cache->do_curl($feed_url);
			if (empty($content)) return;

			$feed = @json_decode($content);
			if ( isset($feed->data) && ! empty($feed->data) ) {
				return $feed;
			} else {
				if ( ! empty($feed->error) ) {
					if ( ! empty($feed->error->message) && ! $is_cached ) {
						if ($this->debug_log)
							ss_debug_log( 'Instagram error: '.$feed->error->message.' - ' . $feed_url, SB_LOGFILE );
					}
					$feed = null;
				}
			}

			return;
		}
		
	}

	public function GetContent($id, $limit = 25, $after = null) {
		$user = $this->GetUser($id);
		if ($user) {
			$media = $this->GetMedias($id, (int)$limit, $after);
			if ($media) {
				$mediaDatas = array();
				foreach($media->data as $r) {
					$mediaDatas[] = (object) array(
						"id" => $r->id,
						"link" => $r->permalink,
						"type" => $r->media_type,
						"shortcode" => array_values(array_filter(explode("/", parse_url($r->permalink, PHP_URL_PATH))))[1],
						"created_time" => strtotime($r->timestamp),
						"caption" => (object) array(
							"text" => @$r->caption
						),
						"user" => (object) array(
							"id" => $user->id,
							"username" => $user->username,
							"full_name" => "",
							"profile_picture" => "",
						),
						"images" => $this->GetImages(($r->media_type == "VIDEO") ? $r->thumbnail_url : $r->media_url),
						"square_images" => $this->GetSquareImages(($r->media_type == "VIDEO") ? $r->thumbnail_url : $r->media_url),
						"videos" => $this->GetVideos(($r->media_type == "VIDEO") ? $r->media_url : null),
						"comments" => $this->GetComments(null),
						"likes" => $this->GetLikes(null),
					);
				}
				return (object) array(
					"pagination" => (object) array(
						"next_max_id" => @$media->paging->cursors->after
					),
					"data" => $mediaDatas
				);
			}
		}
	}

	public function GetImages($url="") {
		$return = (object) array(
			"thumbnail" => (object) array(
				"url" => (is_array($url)) ? @$url["thumbnail"] : $url
			),
			"low_resolution" => (object) array(
				"url" => (is_array($url)) ? @$url["low_resolution"] : $url
			),
			"standard_resolution" => (object) array(
				"url" => (is_array($url)) ? @$url["standard_resolution"] : $url
			),
			"high_resolution" => (object) array(
				"url" => (is_array($url)) ? @$url["high_resolution"] : $url
			),
		);
		return $return;
	}

	public function GetSquareImages($url="") {
		return (array) $url;
	}

	public function GetVideos($url="") {
		return (object) array(
			"low_resolution" => (object) array(
				"url" => (is_array($url)) ? @$url["low_resolution"] : ""
			),
			"standard_resolution" => (object) array(
				"url" => (is_array($url)) ? @$url["standard_resolution"] : $url
			),
			"low_bandwidth" => (object) array(
				"url" => (is_array($url)) ? @$url["low_bandwidth"] : ""
			)
		);
	}
	public function GetComments($comment="")
	{
		return (object) array(
			"count" => $comment
		);
	}
	public function GetLikes($links="")
	{
		return (object) array(
			"count" => $links
		);
	}
	
}