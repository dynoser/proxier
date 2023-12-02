<?php
namespace dynoser\webtools;

class Proxier {
    public $url = '';
    public $repHeadArr = [];
    public function __construct($urlB64, $repHeadersB64 = '') {
        $this->url = self::base64Udecode($urlB64);
        if ($repHeadersB64) {
            $repHeadersStr = self::base64Udecode($repHeadersB64);
            if ($repHeadersStr) {
                $this->repHeadArr = \json_decode($repHeadersStr, true);
            }
        }
    }
    public static function base64Udecode($str) {
        return \base64_decode(\strtr($str, '-_', '+/'));
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
    }
}
