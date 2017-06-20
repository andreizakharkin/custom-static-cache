<?php

namespace Zakharkin\CustomStaticCache;

class CustomStaticCache
{
    const NO_CACHE_HEADER = 'No-Custom-Cache';

    private static $_instance = null;

    private $_config;
    private $_cacheFolder;
    private $_pidFolder;
    private $_currentPath;
    private $_currentUri;
    private $_currentCacheFile;
    private $_currentPidFile;

    private function __construct()
    {
        $this->_setConfig();
        $this->_setCacheFolders();
        $this->_setCurrentPath();
        $this->_setCurrentUri();
        $this->_setCurrentCacheFile();
        $this->_setCurrentPidFile();
    }

    private function _setConfig()
    {
        if(file_exists(realpath(__DIR__ . '/../../../../../config/custom-static-cache.php'))) {
            $this->_config = include('../../../../../config/custom-static-cache.php');
        } else {
            $this->_config = include('../../config/config.php');
        }
    }

    private function _setCacheFolders()
    {
        $this->_cacheFolder = realpath(__DIR__ . '/files');
        $this->_pidFolder = realpath(__DIR__ . '/pids');
    }

    private function _setCurrentPath()
    {
        $urlData = parse_url($_SERVER['REQUEST_URI']);

        $this->_currentPath = $urlData['path'];
    }

    private function _setCurrentUri()
    {
        $this->_currentUri = $_SERVER['REQUEST_URI'];
    }

    private function _setCurrentCacheFile()
    {
        $this->_currentCacheFile = $this->_cacheFolder . '/' . md5($this->_currentUri) . '.cache';
    }

    private function _setCurrentPidFile()
    {
        $this->_currentPidFile = $this->_pidFolder . '/' . md5($this->_currentUri) . '.pid';
    }

    public static function getInstance()
    {
        if(is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    private function _curlGet($url) {
        if($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                self::NO_CACHE_HEADER . ': 1',
            ]);
            $response = curl_exec($curl);
            curl_close($curl);

            return $response;
        } else {
            return false;
        }
    }

    protected function hasNoCacheHeader()
    {
        $headers = apache_request_headers();
        if(array_key_exists(self::NO_CACHE_HEADER, $headers ?? [])) {
            return true;
        }

        return false;
    }

    protected function isAbleToCache()
    {
        if(!$this->hasNoCacheHeader()) {
            if($this->_config['type'] == 'all') {
                if(!in_array($this->_currentPath, $this->_config['blacklist'])) {
                    return true;
                }
            } elseif ($this->_config['type'] == 'list') {
                if(in_array($this->_currentPath, $this->_config['whitelist'])) {
                    return true;
                }
            }
        }

        if (file_exists($this->_currentCacheFile)) {
            unlink($this->_currentCacheFile);
        }
        return false;
    }

    protected function isActual($file)
    {
        $fileTime = filemtime($file);
        if($fileTime && ((time() - $fileTime) <= $this->_config['lifetime'])) {
            return true;
        }

        return false;
    }

    private function startCachingProcess()
    {
        $fp = fopen($this->_currentPidFile, 'w');
        fwrite($fp, getmypid());
        fclose($fp);
        chmod($this->_currentPidFile, 0777);
    }

    private function checkCachingProcess()
    {
        if(file_exists($this->_currentPidFile)) {
            return false;
        }

        return true;
    }

    private function stopCachingProcess()
    {
        if(file_exists($this->_currentPidFile)) {
            unlink($this->_currentPidFile);
        }
    }

    public function checkAndShow()
    {
        if(!$this->isAbleToCache()) {
            return;
        }

        if (file_exists($this->_currentCacheFile)) {
            if($this->isActual($this->_currentCacheFile)) {
                readfile($this->_currentCacheFile);
                exit();
            } else {
                unlink($this->_currentCacheFile);
            }
        }
    }

    public function save()
    {
        if(!$this->isAbleToCache() || !$this->checkCachingProcess()) {
            return;
        }

        $this->startCachingProcess();

        $buffer = $this->_curlGet('http://' . $_SERVER['SERVER_NAME'] . $this->_currentUri);
        if($buffer) {
            $fp = fopen($this->_currentCacheFile, 'w');
            fwrite($fp, $buffer);
            fclose($fp);
            chmod($this->_currentCacheFile, 0777);
        }

        $this->stopCachingProcess();
    }
}
