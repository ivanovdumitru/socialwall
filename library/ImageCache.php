<?php

/**
 * PHP Social Stream 2.6.3
 * Copyright 2018 Axent Media (axentmedia@gmail.com)
 */

class ImageCache {
    public $automationHandler = 'auto';
    public $imageCacheDir = 'cache/'; // set to a writable folder for image cache storage
    public $imageSquare = FALSE; // change a rectangular image into a square with white background
    public $imageCacheTime = 604800; // cache period in seconds, set to zero for proxy only mode
    public $imageCacheResize = FALSE; // set to resize dimension to enable  e.g. $this->imageCacheResize = 250;

    function cacheFetch_auto($src)
    {
        if (function_exists("curl_init") )
        {
            return $this->cacheFetch_curl($src);
        }
        else
        {
            return $this->cacheFetch_php($src);
        }
    }

    function cacheFetch_curl($src)
    {
        $ch = curl_init();

        // Setup headers - the same headers from Firefox version 2.0.0.6
        // using fake headers and a fake user agent.
        // below was split up because the line was too long.
        $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
        $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
        $header[] = "Cache-Control: max-age=0";
        $header[] = "Connection: keep-alive";
        $header[] = "Keep-Alive: 300";
        $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
        $header[] = "Accept-Language: en-us,en;q=0.5";
        $header[] = "Pragma: "; // browsers keep this blank.

        curl_setopt($ch, CURLOPT_URL, $src);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_REFERER, '');
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, SB_API_TIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, SB_API_TIMEOUT);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $img = curl_exec($ch);
        if ($img === false)
        {
            echo curl_error($ch);
        }
    	curl_close($ch);
        return $img;
    }
    
    function cacheFetch_php($src)
    {
        return file_get_contents($src);
    }

    function cacheFetch($src)
    {
        // make file name
        $filename = $this->imageCacheDir.md5($src).'.cache';
        
        // check if file is cached
        $fetch = false;
        if (!$this->imageCacheTime || !file_exists($filename) )
            $fetch = true;
        if ($this->imageCacheTime && file_exists($filename) ) {
            if (filemtime($filename) < (time() - $this->imageCacheTime) )
                $fetch = true;
        }
        // file is cached
        if ($fetch)
        {
            $cacheFetch_fn = "cacheFetch_".$this->automationHandler;
            $img = $this->{$cacheFetch_fn}($src);
        }
        // file is not cached
        else
        {
            $img = file_get_contents($filename);
        }
        if (!$res = @imagecreatefromstring($img) ) return FALSE;
        if ($this->imageCacheResize)
        {
            $oldX = imagesx($res);
            $oldY = imagesy($res);
            if ($this->imageSquare || $oldX > $this->imageCacheResize)
            {
                if ($this->imageSquare)
                {
                    $new = imagecreatetruecolor($this->imageCacheResize, $this->imageCacheResize);
                    $newBackground = imagecolorallocate($new, 255, 255, 255);
                    imagefill($new, 1, 1, $newBackground);
                    if ($oldX > $oldY)
                    {
                        $newX = $this->imageCacheResize;
                        $xMultiplier = ($newX / $oldX);
                        $newY = intval($oldY * $xMultiplier);
                        $dstX = 0;
                        $dstY = ($this->imageCacheResize / 2) - ($newY / 2);
                    }
                    else
                    {
                        $newY = $this->imageCacheResize;
                        $yMultiplier = ($newY / $oldY);
                        $newX = intval($oldX * $yMultiplier);
                        $dstX = ($this->imageCacheResize / 2)-($newX / 2);
                        $dstY = 0;
                    }
                    imagecopyresized($new, $res, $dstX, $dstY, 0, 0, $newX, $newY, $oldX, $oldY);
                }
                elseif ($oldX > $this->imageCacheResize)
                {
                	// calculate new width
                	$ratio = ($this->imageCacheResize / $oldX);
                	$new_w = $this->imageCacheResize;
                	$new_h = intval($oldY * $ratio);
                	$new = imagecreatetruecolor($new_w, $new_h);
                	imagecopyresampled($new, $res, 0, 0, 0, 0, $new_w, $new_h, $oldX, $oldY);
                }
                imagedestroy($res);
                ob_start();
                imagejpeg($new, NULL, 75);
                imagedestroy($new);
                $img = ob_get_contents();
                ob_end_clean();
            }
        }
        
        if ($fetch && $this->imageCacheTime)
        {
            $fp = fopen($filename, "w");
            fwrite($fp, $img);
            fclose($fp);
        }
        
        return $img;
    }

    // display image
    function cacheImage($imgArr)
    {
        if (@$imgArr["refresh"])
            $this->imageCacheTime = $imgArr["refresh"];
        
        if (@$imgArr["resize"])
            $this->imageCacheResize = $imgArr["resize"];
        
        $src = @$imgArr['src'];
        $token = @$imgArr["token"];
        if ($src && $token)
        {
            $tokenServer = md5(urlencode($src).@$_SERVER['SERVER_ADDR'].@$_SERVER['SERVER_ADMIN'].@$_SERVER['SERVER_NAME'].@$_SERVER['SERVER_PORT'].@$_SERVER['SERVER_PROTOCOL'].@$_SERVER['SERVER_SIGNATURE'].@$_SERVER['SERVER_SOFTWARE'].@$_SERVER['DOCUMENT_ROOT']);
            if ($tokenServer == $token)
            {
                if (!$img = $this->cacheFetch($src) )
                {
                    header("HTTP/1.0 404 Not Found");
                    exit();
                }
                else
                {
                    header("Content-Type: image");
                    print $img;
                    exit();
                }
            } else {
                header("HTTP/1.0 404 Not Found");
                exit();
            }
        }
    }
}
