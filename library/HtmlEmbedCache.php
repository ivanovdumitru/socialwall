<?php

require "Embed-master/src/autoloader.php";

/**
 * PHP Social Stream 2.7.0
 * Copyright 2018 Axent Media (axentmedia@gmail.com)
 */

class SS_HtmlEmbedCache {
	// Path to cache folder (with trailing /)
    public $cache_path = 'cache/';
    
	// Length of time to cache a file (in seconds)
    public $cache_time = 604800;
    
	// Cache file extension
	public $cache_extension = '.cache';
    
    public $debug_log = false;
    public $timeout = 15;

    public function __construct() {
        $this->dispatcher = new \Embed\Http\CurlDispatcher([
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT => $this->timeout
        ]);
    }

	public function getData($url, array $options = array() ) {
        if (empty($url) ) {
            return '';
        }

        $options = array_merge(
            array(
                'min_image_width' => 160,
                'min_image_height' => 160,
                'images_blacklist' => array('*ico', 'ico*', '*ico*', '*icon', 'icon*', '*icon*'),
                'html' => array(
                    'max_images' => 10, // Set to -1 for no limit
                    'external_images' => false
                )
            ),
            $options
        );

        // Check for a cached content
        $content = '';
        if ( $this->isCached($url) ) {
            $content = $this->getCache($url);
            $content = json_decode($content);
		} else {
            try {
                $data = \Embed\Embed::create($url, $options, $this->dispatcher);
                $content = new stdClass();
                $adapterData = [
                    'title',
                    'description',
                    'url',
                    'type',
                    'tags',
                    'images',
                    'image',
                    'imageWidth',
                    'imageHeight',
                    'code',
                    'width',
                    'height',
                    'aspectRatio',
                    'authorName',
                    'authorUrl',
                    'providerName',
                    'providerUrl',
                    'providerIcons',
                    'providerIcon',
                    'publishedDate'
                ];
                foreach ($adapterData as $name) {
                    $content->$name = $data->$name;
                }
                $this->setCache($url, json_encode($content) );
            } catch (Exception $exception) {
                ss_debug_log('Embed error - ' . $exception->getMessage() . ' - ' . $url);
            }
        }

        return $content;
    }
    
	private function setCache($label, $data) {
		file_put_contents($this->cache_path . $this->safeFilename($label) . $this->cache_extension, $data);
	}

	private function getCache($label) {
		$filename = $this->cache_path . $this->safeFilename($label) . $this->cache_extension;
		return file_get_contents($filename);
	}

	private function isCached($label) {
		$filename = $this->cache_path . $this->safeFilename($label) . $this->cache_extension;

        if ( file_exists($filename) && ( filemtime($filename) + $this->cache_time >= time() ) )
            return true;

		return false;
	}

	// Helper function to validate filenames
	private function safeFilename($filename) {
		$filename = md5($filename);
        return preg_replace('/[^0-9a-z\.\_\-]/i', '', strtolower($filename));
	}
}
