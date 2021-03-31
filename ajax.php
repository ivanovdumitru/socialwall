<?php

/**
 * PHP Social Stream
 * Copyright 2014-2019 Axent Media (support@axentmedia.com)
 */

@header( 'Content-Type: text/html; charset=utf-8' );
@header( 'X-Robots-Tag: noindex' );

// create the ajax callback for tabable widget
if (@$_GET['action'] == 'sb_tabable') {
    
    if ( ! ss_nonce_verify( $_REQUEST['nonce'], "tabable", $_REQUEST['label'] )) {
        exit("No naughty business please!");
    }

    if ( ! empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        $sb = new SocialStream();
        $sb->init( $_REQUEST['attr'], true, null, array( array($_REQUEST['feed'] => 1) ) );
    }
    else {
        header("Location: ".$_SERVER["HTTP_REFERER"]);
    }
}
// create the ajax callback for load more
elseif (@$_GET['action'] == 'sb_loadmore') {

    if ( ! ss_nonce_verify( $_REQUEST['nonce'], "loadmore", $_REQUEST['label'] ) ) {
        exit("No naughty business please!");
    }

    if ( ! empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        $sb = new SocialStream();
        $sb->init( $_REQUEST['attr'], true, null, 'all', @$_SESSION[$_REQUEST['label']]['loadmore'] );
    }
    else {
        header("Location: ".$_SERVER["HTTP_REFERER"]);
    }
}
// creates the ajax callback for live update
elseif (@$_GET['action'] == 'sb_liveupdate') {

    if ( ! ss_nonce_verify( $_REQUEST['nonce'], "liveupdate")) {
        exit("No naughty business please!");
    }

    if ( ! empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        $args = array('liveresults' => @$_REQUEST['results']);
        $sb = new SocialStream();
        $sb->init( $_REQUEST['attr'], true, $args, 'all', array() );
    }
    else {
        header("Location: ".$_SERVER["HTTP_REFERER"]);
    }
}
// create the ajax callback for comments
elseif (@$_GET['action'] == 'sb_fetchcomments') {

    if ( ! ss_nonce_verify( $_REQUEST['nonce'], "fetchcomments")) {
        exit("No naughty business please!");
    }

    if ( ! empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

        $sb = new SocialStream();
        $sb->fetchcomments = true;
        $sb->init( $_REQUEST['attr'] );

        if ($_REQUEST['id'] && $_REQUEST['feed']) {
            $id = $_REQUEST['id'];
            $feed = explode('-', $_REQUEST['feed']);
            $feedclass = trim($feed[0]);
            
            // fetch comments from API
            switch ($feedclass) {
                case 'instagram':
                    // only if own Instagram user feed
                    if ($feed[1] == 1) {
                        $feed_value = $sb->sboption['instagram']['instagram_id_'.$feed[1]][$feed[2]];
                        $feed_token = $sb->instagram_access_token($feed_value);
                        $feed_url = 'https://api.instagram.com/v1/media/'.$id.'/comments';
                        if ( ! empty($feed_token) ) {
                            $feed_url .= '?access_token=' . $feed_token;
                        }
                    }
                break;
                case 'youtube':
                    $google_api_key = @$GLOBALS['api']['google']['google_api_key'];
                    $feed_url = 'https://www.googleapis.com/youtube/v3/commentThreads?videoId=' . $id . '&part=snippet,replies&maxResults=' . $sb->attr['results'] . '&key=' . $google_api_key;
                break;
            }
            if ( ! empty($feed_url) ) {
                // if other public instagram user account without a $feed_token
                if (empty($feed_token) && $feedclass == 'instagram') {
                    $get_feed = TRUE;
                    if ( ! $sb->forceCrawl ) {
                        if ( $sb->cache->is_cached($feed_url) ) {
                            $content = $sb->cache->get_cache($feed_url);
                            $get_feed = FALSE;
                        }
                    }
                    if ($get_feed) {
                        try {
                            // use Instagram scraper
                            $instagram = $sb->instagram_set_proxy();
                            $content = $instagram->getMediaCommentsById($id, $sb->attr['results']);
                            
                            $feed = new stdClass();
                            foreach ($content as $key => $comment) {
                                $feed->data[$key] = new stdClass();
                                $feed->data[$key]->id = $comment->getId();
                                $feed->data[$key]->created_time = $comment->getCreatedAt();
                                $feed->data[$key]->text = $comment->getText();
                                
                                $account = $comment->getOwner();
                                $feed->data[$key]->from = new stdClass();
                                $feed->data[$key]->from->id = $account->getId();
                                $feed->data[$key]->from->full_name = $account->getFullName();
                                $feed->data[$key]->from->username = $account->getUsername();
                                $feed->data[$key]->from->profile_picture = $account->getProfilePicUrl();
                            }
                        } catch (Exception $e) {
                            ss_debug_log( 'Instagram error: ' . $e->getMessage() . ' - getMediaCommentsById', SB_LOGFILE );
                        }
                        if ( ! $sb->forceCrawl )
                            $sb->cache->set_cache($feed_url, json_encode($feed) );
                        
                        $comments_feed = $feed;
                    }
                    else
                        $comments_feed = @json_decode($content);
                } else {
                    $content = ( ! $sb->forceCrawl) ? $sb->cache->get_data($feed_url, $feed_url) : $sb->cache->do_curl($feed_url);
                    $comments_feed = @json_decode($content);
                }
                if ( ! empty($comments_feed) ) {
                    $feedfunc = $feedclass.'_parse_comments';
                    echo $sb->$feedfunc($comments_feed, $_REQUEST['link']);
                }
            }
        }
    } else {
        header("Location: ".$_SERVER["HTTP_REFERER"]);
    }
}
// creates the ajax callback for live update
elseif ($sbimg = @$_GET['sbimg']) {
    if ( ! empty($sbimg) ) {
        // load image proxy
        include( SB_DIR . '/library/ImageCache.php' );
        $imgcache = new ImageCache;
        $imgcache->imageCacheDir = SB_DIR . '/cache/';
        
        // convert string
        parse_str(base64_decode($sbimg), $imgArr);
        // cache and parse image
        $imgcache->cacheImage($imgArr);
    }
}



// End of file ajax.php