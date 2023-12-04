<?php
namespace dynoser\webtools;

class Proxier
{
    public $url = '';
    public $repHeadArr = []; // to replace request-headers
    
    public static $leftURL = ''; // for makeUrlPar only

    public $cacheShortName = '';  // hex-hash from (URL . repHead)
    public $cacheBaseDir = ''; // set by ->setCacheBaseDir(...)
    public $cacheTimeSec  = 3600; // default value is 1 hour

    public function __construct($urlB64, $repHeadersB64 = '', $cacheTimeSec = 0) {
        $this->setParams($urlB64, $repHeadersB64, $cacheTimeSec);
    }

    public function setParams($urlB64, $repHeadersB64 = '', $cacheTimeSec = 0) {
        $this->url = self::base64Udecode($urlB64);
        if ($repHeadersB64) {
            $repHeadersStr = self::base64Udecode($repHeadersB64);
            if ($repHeadersStr) {
                $this->repHeadArr = \json_decode($repHeadersStr, true);
            }
        }
        if ($cacheTimeSec && \is_numeric($cacheTimeSec)) {
            $this->cacheTimeSec = (int)$cacheTimeSec;
        }
        $this->cacheShortName = \substr(\hash('sha256', $urlB64 . $repHeadersB64), -15);
    }

    public static function base64Udecode($str) {
        return \base64_decode(\strtr($str, '-_', '+/'));
    }

    public static function base64Uencode($str) {
        $enc = \base64_encode($str);
        return \rtrim(\strtr($enc, '+/', '-_'), '=');
    }
    
    public static function makeUrlPar($url, $repHeadersArr = []) {
        if (!filter_var($url, \FILTER_VALIDATE_URL)) {
            die('Invalid URL');
        }
        $urlB64 = self::base64Uencode($url);
        $result = 'url=' . $urlB64;
        if ($repHeadersArr && \is_array($repHeadersArr)) {
            $result .= '&rep=' . self::base64Uencode(json_encode($repHeadersArr));
        }
        return self::$leftURL . $result;
    }
    
    public function setCacheBaseDir($cacheBaseDir, $checkDir = true) {
        $this->cacheBaseDir = \rtrim(\strtr($cacheBaseDir, '\\', '/'), '/');
        if ($checkDir && !\is_dir($this->cacheBaseDir)) {
            throw new \Exception("cacheBaseDir not exist: " . $this->cacheBaseDir);
        }
    }

    public function run() {
        if (empty($this->url)) {
            die('URL parameter is required');
        }
        if (!filter_var($this->url, \FILTER_VALIDATE_URL)) {
            die('Invalid URL');
        }
        if (!\is_array($this->repHeadArr)) {
            die('Invalid REP');
        }
        
        // --- begin cache ---
        $cacheFullFile = ($this->cacheBaseDir && $this->cacheTimeSec) ? ($this->cacheBaseDir . '/' . $this->cacheShortName) : null;
        if ($cacheFullFile && \is_file($cacheFullFile)) {
            $fileLastModified = \filemtime($cacheFullFile);
            $currentTime = \time();
            if (($currentTime - $fileLastModified) <= $this->cacheTimeSec) {
                $response = \file_get_contents($cacheFullFile);
                list($headersIn, $body) = \explode("\r\n\r\n", $response, 2);
                foreach (\explode("\r\n", $headersIn) as $n => $hdr) {
                    if ($n) {
                        \header($hdr);
                    } else {
                        \http_response_code($hdr);
                    }
                }
                echo $body;
                die;
            }
        }
        // --- end of cache ---

        $remoteDomain = \parse_url($this->url, \PHP_URL_HOST);

        if (empty($this->repHeadArr['Referer'])) {
            $this->repHeadArr['Referer'] = "https://$remoteDomain/";
        }
        if (empty($this->repHeadArr['Host'])) {
            $this->repHeadArr['Host'] = null;
        }
        if (empty($this->repHeadArr['Cookie'])) {
            $this->repHeadArr['Cookie'] = null;
        }

        $ch = curl_init();

        \curl_setopt($ch, \CURLOPT_URL, $this->url);
        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, \CURLOPT_HEADER, true);

        $headers = [];
        foreach (\getallheaders() as $headerName => $headerValue) {
            if (\array_key_exists($headerName, $this->repHeadArr)) {
                $headerValue = $this->repHeadArr[$headerName];
            }
            if (!\is_null($headerValue)) {
                $headers[] = "$headerName: $headerValue";
            }
        }

        \curl_setopt($ch, \CURLOPT_HTTPHEADER, $headers);

        $response = \curl_exec($ch);

        if (\curl_errno($ch)) {
            die('ERROR: ' . \curl_error($ch));
        }

        $httpCode = \curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        \curl_close($ch);

        list($headersIn, $body) = \explode("\r\n\r\n", $response, 2);

        \http_response_code($httpCode);

        foreach (\explode("\r\n", $headersIn) as $hdr) {
            \header($hdr);
        }

        echo $body;
        
        // --- begin cache ---
        if ($cacheFullFile && ($httpCode == '200')) {
            $fd = \fopen($cacheFullFile, 'wb');
            \fwrite($fd, $httpCode . "\r\n");
            \fwrite($fd, $response);
        }
        // --- end of cache ---
    }
}
