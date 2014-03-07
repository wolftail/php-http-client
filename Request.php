<?php
/**
 * Http class used to sending request and get response like a browser.
 * Use 2 functions: cURL, fsockopen.
 * Supports POST (fields, raw data), file uploading, GET, PUT, etc..
 *
 * @author     Phan Thanh Cong <ptcong90@gmail.com>
 * @copyright  2010-2014 Phan Thanh Cong.
 * @license    http://www.opensource.org/licenses/mit-license.php  MIT License
 * @version    2.6
 * @relase     Mar 7, 2014 (mostly clean, clear)
 */

namespace ChipVN\Http;

class Request
{
    /**
     * HTTP Version.
     *
     * @var string
     */
    public $httpVersion;

    /**
     * URL target.
     *
     * @var string
     */
    public $target;

    /**
     * URL schema.
     *
     * @var string
     */
    public $schema;

    /**
     * URL host.
     *
     * @var string
     */
    public $host;

    /**
     * URL port.
     *
     * @var integer
     */
    public $port;

    /**
     * URL path.
     *
     * @var string
     */
    public $path;

    /**
     * Request method.
     *
     * @var string
     */
    public $method;

    /**
     * Request cookies.
     *
     * @var string
     */
    public $cookies;

    /**
     * Request headers.
     *
     * @var array
     */
    public $headers;

    /**
     * Request parameters.
     *
     * @var array
     */
    public $parameters;

    /**
     * Raw post data.
     *
     * @var mixed
     */
    public $rawData;

    /**
     * Request user agent.
     *
     * @var string
     */
    public $userAgent;

    /**
     * Number of seconds to timeout.
     *
     * @var integer
     */
    public $timeout;

    /**
     * Determine the request will use cURL or not.
     *
     * @var boolean
     */
    public $useCurl;

    /**
     * Authentication username.
     *
     * @var string
     */
    public $authUsername;

    /**
     * Authentication password.
     *
     * @var string
     */
    public $authPassword;

    /**
     * Proxy IP (only cURL).
     *
     * @var string
     */
    public $proxyIp;

    /**
     * Proxy username.
     *
     * @var string
     */
    public $proxyUsername;

    /**
     * Proxy password.
     *
     * @var string
     */
    public $proxyPassword;

    /**
     * Determine the request is multipart or not.
     *
     * @var boolean
     */
    public $isMultipart;

    /**
     * Enctype (application/x-www-form-urlencoded).
     *
     * @var string
     */
    public $mimeContentType;

    /**
     * Boundary name (use when uploading).
     *
     * @var string
     */
    public $boundary;

    /**
     * Errors while execute.
     *
     * @var array
     */
    public $errors;

    /**
     * Response status code.
     *
     * @var integer
     */
    protected $responseStatus;

    /**
     * Response cookies.
     *
     * @var string
     */
    protected $responseCookies;

    /**
     * Response headers.
     *
     * @var array
     */
    protected $responseHeaders;

    /**
     * Response text.
     *
     * @var string
     */
    protected $responseText;

    /**
     * Create a Http instance.
     *
     * @return void
     */
    public function _construct()
    {
        $this->reset();
    }
    /**
     * Reset request
     * @return \ChipVN\Http\Request
     */
    public function reset()
    {
        $this->httpVersion      = '1.1';
        $this->target           = '';
        $this->schema           = 'http';
        $this->host             = '';
        $this->port             = 0;
        $this->path             = '';
        $this->method           = 'GET';
        $this->parameters       = array();
        $this->rawData          = '';
        $this->cookies          = '';
        $this->headers          = array();

        $this->useCurl          = false;
        $this->timeout          = 10;
        $this->userAgent        = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:9.0.1) Gecko/20100101 Firefox/9.0.1';
        $this->errors           = array();

        $this->mimeContentType  = 'application/x-www-form-urlencoded';
        $this->boundary         = 'chiplove.9xpro';

        $this->proxyIp          = '';
        $this->proxyUsername    = '';
        $this->proxyPassword    = '';

        $this->authUsername     = '';
        $this->authPassword     = '';

        $this->responseStatus   = 0;
        $this->responseHeaders  = array();
        $this->responseCookies  = '';
        $this->responseText     = '';

        return $this;
    }

    /**
     * Set http version.
     *
     * @param  string               $version
     * @return \ChipVN\Http\Request
     */
    public function setHttpVersion($version)
    {
        if (in_array($version, array('1.0', '1.1'))) {
           $this->httpVersion = $version;
        }

        return $this;
    }

    /**
     * Set URL target.
     *
     * @param  string               $target
     * @return \ChipVN\Http\Request
     */
    public function setTarget($target)
    {
        $this->target = trim($target);

        return $this;
    }

    /**
     * Set parameters with name, value or array of name-value pairs.
     *
     * @param  string|array         $name
     * @param  mixed                $value
     * @return \ChipVN\Http\Request
     */
    public function setParam($name, $value = null)
    {
        if (func_num_args() == 2) {
            $this->parameters[$name] = $value;
        } else {
            if (is_array($name)) {
                foreach ($name as $key => $value) {
                    $this->parameters[$key] = $value;
                }
            } elseif (is_string($name)) {
                $name = preg_replace_callback(
                    '#&[a-z]+;#',
                    create_function('$match', 'return rawurlencode($match[0]);'),
                    $name);
                parse_str(str_replace('+', '%2B', $name), $array);
                $this->setParam($array);
            }
        }

        return $this;
    }

    /**
     * Set request URL referer.
     *
     * @param  string               $referer
     * @return \ChipVN\Http\Request
     */
    public function setReferer($referer)
    {
        $this->headers['Referer'] = $referer;

        return $this;
    }

    /**
     * Set request user agent.
     *
     * @param  string               $userAgent
     * @return \ChipVN\Http\Request
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;

        return $this;
    }
    /**
     * Set number of seconds to time out.
     *
     * @param  integer              $seconds
     * @return \ChipVN\Http\Request
     */
    public function setTimeout($seconds)
    {
        if ($seconds > 0) {
            $this->timeout = $seconds;
        }

        return $this;
    }

    /**
     * Set request raw post data.
     *
     * @param  string               $rawData
     * @return \ChipVN\Http\Request
     */
    public function setRawPost($rawData)
    {
        $this->rawData = $rawData;

        return $this;
    }

    /**
     * Set request method.
     *
     * @param  string               $method
     * @return \ChipVN\Http\Request
     */
    public function setMethod($method)
    {
        $this->method = strtoupper(trim($method));

        return $this;
    }

    /**
     * Set request headers with name, value or array of name-value pairs.
     *
     * @param  string|array         $name
     * @param  mixed                $value
     * @return \ChipVN\Http\Request
     */
    public function setHeader($name, $value = null)
    {
        if (func_num_args() == 2) {
            $this->headers[trim($name) ] = trim($value);
        } else {
            if (is_array($name)) {
                foreach ($name as $key => $value) {
                    if (!is_int($key)) {
                        $this->setHeader($key, $value);
                    } else {
                        $this->setHeader($value);
                    }
                }
            } elseif (is_string($name)) {
                list($key, $value) = explode(':', $name, 2);
                $this->setHeader($key, $value);
            }
        }

        return $this;
    }

    /**
     * Determine if the request will use cURL or not.
     * Default is use fsockopen.
     *
     * @param  boolean              $useCurl
     * @return \ChipVN\Http\Request
     */
    public function useCurl($useCurl)
    {
        $this->useCurl = (boolean) $useCurl;

        return $this;
    }

    /**
     * Set submit multipart.
     *
     * @param  string               $type
     * @return \ChipVN\Http\Request
     */
    public function setSubmitMultipart($type = 'form-data')
    {
        $this->setMethod('POST');
        $this->isMultipart = true;
        $this->mimeContentType = "multipart/" . $type;

        return $this;
    }

    /**
     * Set submit normal.
     *
     * @param  string               $method
     * @return \ChipVN\Http\Request
     */
    public function setSubmitNormal($method = 'POST')
    {
        $this->setMethod($method);
        $this->isMultipart = false;
        $this->mimeContentType = "application/x-www-form-urlencoded";

        return $this;
    }

    /**
     * Set request content type.
     *
     * @param  string               $mimeType
     * @return \ChipVN\Http\Request
     */
    public function setMimeContentType($mimeType)
    {
        $this->mimeContentType = $mimeType;

        return $this;
    }

    /**
     * Set request cookies.
     *
     * @param  string|array         $value
     * @param  boolean              $addition
     * @return \ChipVN\Http\Request
     */
    public function setCookie($value, $addition = true)
    {
        if (is_array($value)) {
            $value = implode(';', $value);
        }
        if ($addition) {
            $this->cookies .= $value . ';';
        } else {
            $this->cookies = $value;
        }

        return $this;
    }

    /**
     * Set request with proxy.
     *
     * @param  string               $proxyIp  Format: ipaddress:port
     * @param  string               $username
     * @param  string               $password
     * @return \ChipVN\Http\Request
     */
    public function setProxy($proxyIp, $username = '', $password = '')
    {
        $this->proxyIp = trim($proxyIp);
        $this->proxyUsername = $username;
        $this->proxyPassword = $password;

        return $this;
    }

    /**
     * Set request authentication.
     *
     * @param  string               $username
     * @param  string               $password
     * @return \ChipVN\Http\Request
     */
    public function setAuth($username, $password = '')
    {
        $this->authUsername = $username;
        $this->authPassword = $password;

        return $this;
    }

    /**
     * Execute sending request and trigger errors messages if have.
     *
     * @param  string|null                  $target
     * @param  string|null                  $method
     * @param  string|array|null            $parameters
     * @param  string|null                  $referer
     * @return \ChipVN\Http\Request|boolean False if request is failed.
     */
    public function execute($target = null, $method = null, $parameters = null, $referer = null)
    {
        if ($target) {
            $this->setTarget($target);
        }
        if ($method) {
            $this->setMethod($method);
        }
        if ($referer) {
            $this->setReferer($referer);
        }
        if ($parameters) {
            $this->setParam($parameters);
        }

        if (empty($this->target)) {
            $this->errors[] = 'ERROR: Target url must be no empty.';

            return false;
        }

        if ($this->parameters && $this->method == 'GET') {
            $this->target .= ($this->method == 'GET' ? (strpos($this->target, '?') ? '&' : '?')
                . http_build_query($this->parameters) : '');
        }

        $urlParsed = parse_url($this->target);

        if ($urlParsed['scheme'] == 'https') {
            $this->host = 'ssl://' . $urlParsed['host'];
            $this->port = ($this->port != 0) ? $this->port : 443;
        } else {
            $this->host = $urlParsed['host'];
            $this->port = ($this->port != 0) ? $this->port : 80;
        }
        $this->path = (isset($urlParsed['path']) ? $urlParsed['path'] : '/')
                    . (isset($urlParsed['query']) ? '?' . $urlParsed['query'] : '');
        $this->schema = $urlParsed['scheme'];
        // use cURL to send request
        if ($this->useCurl) {
            if ($this->isMultipart) {
                foreach ((array) $this->parameters as $key => $value) {
                    if (substr($value, 0, 1) == '@') {
                        $this->parameters[$key] = $value . ';type=' . $this->getMimeType(substr($value, 1));
                    }
                }
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->target);

            $httpVersion = CURL_HTTP_VERSION_1_0;
            if ($this->httpVersion = '1.1') {
                $httpVersion = CURL_HTTP_VERSION_1_1;
            }
            curl_setopt($ch, CURLOPT_HTTP_VERSION, $httpVersion);

            if ($this->isMultipart) {
                $this->headers[] = 'Content-Type: ' . $this->mimeContentType;
            }
            if ($this->method == 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->parameters);
            }
            if ($this->cookies) {
                curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);
            }
            if ($this->headers) {
                $headers = array();
                foreach ($this->headers as $name => $value) {
                    $headers[] = $name . ': ' . $value;
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            if ($this->timeout) {
                curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            }
            if ($this->authUsername) {
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, $this->authUsername . ':' . $this->authPassword);
            }
            if ($this->proxyIp) {
                curl_setopt($ch, CURLOPT_PROXY, $this->proxyIp);
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);

                if ($this->proxyUsername) {
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyUsername . ':' . $this->proxyPassword);
                }
            }
            curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, false);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

            // send request
            $response = curl_exec($ch);

            if ($response === false) {
                $this->errors[] = sptinrf('ERROR: % - %s.', curl_errno($ch), curl_error($ch));

                return false;
            }
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $responseHeader = substr($response, 0, $headerSize);
            $responseBody = substr($response, $headerSize);

            $this->parseResponseHeaders($responseHeader);
            $this->responseText = $responseBody;
            curl_close($ch);
        }
        // use fsockopen to send request
        else {
            $postData = '';
            if ($this->rawData) {
                $postData .= $this->isMultipart ? "--" . $this->boundary . "\r\n" : "";
                $postData .= $this->rawData . "\r\n";
            }
            // upload file
            if ($this->isMultipart) {
                foreach ($this->parameters as $key => $value) {
                    if (substr($value, 0, 1) == '@') {
                        $upload_file_path = substr($value, 1);
                        $upload_field_name = $key;

                        if (file_exists($upload_file_path)) {
                            $postData .= "--" . $this->boundary . "\r\n";
                            $postData .= "Content-disposition: form-data; name=\"" . $upload_field_name . "\"; filename=\"" . basename($upload_file_path) . "\"\r\n";
                            $postData .= "Content-Type: " . $this->getMimeType($upload_file_path) . "\r\n";
                            $postData .= "Content-Transfer-Encoding: binary\r\n\r\n";
                            $postData .= $this->readBinary($upload_file_path) . "\r\n";
                        }
                    } else {
                        $postData .= "--" . $this->boundary . "\r\n";
                        $postData .= "Content-Disposition: form-data; name=\"" . $key . "\"\r\n";
                        $postData .= "\r\n";
                        $postData .= $value . "\r\n";
                    }
                }
                $postData .= "--" . $this->boundary . "--\r\n";
            }
            // submit normal
            else {
                foreach ($this->parameters as $key => $param) {
                    $postData .= urlencode($key) . '=' . rawurlencode($param) . '&';
                }
                $postData = substr($postData, 0, -1);
            }
            // open connection
            $filePointer = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

            if (!$filePointer) {
                $this->errors[] = sprintf('ERROR: %s - %s.', $errno, $errstr);

                return false;
            }
            $requestHeader = $this->method . " " . $this->path . " HTTP/" . $this->httpVersion . "\r\n";
            $requestHeader .= "Host: " . $urlParsed['host'] . "\r\n";
            $requestHeader .= "User-Agent: " . $this->userAgent . "\r\n";
            if ($this->headers) {
                foreach ($this->headers as $name => $value) {
                    $requestHeader .= $name . ': ' . $value . "\r\n";
                }
            }
            if ($this->mimeContentType) {
                $requestHeader .= "Content-Type: " . $this->mimeContentType
                    . ($this->isMultipart ? "; boundary=" . $this->boundary : "")
                    . "\r\n";
            }
            if ($this->authUsername) {
                $requestHeader .= "Authorization: Basic "
                    . base64_encode($this->authUsername . ":" . $this->authPassword)
                    . "\r\n";
            }
            if ($this->cookies) {
                $requestHeader .= "Cookie: " . $this->cookies . "\r\n";
            }
            if ($postData && $this->method == 'POST') {
                $requestHeader .= "Content-length: " . strlen($postData) . "\r\n";
            }
            $requestHeader .= "Connection: close\r\n";
            $requestHeader .= "\r\n";

            if ($postData && $this->method == "POST") {
                $requestHeader .= $postData;
            }
            $requestHeader .= "\r\n\r\n";

            // send request
            fwrite($filePointer, $requestHeader);

            $responseHeader = '';
            $responseBody = '';
            do {
                $responseHeader .= fgets($filePointer, 128);
            } while (strpos($responseHeader, "\r\n\r\n") === false);

            $this->parseResponseHeaders($responseHeader);

            while (!feof($filePointer)) {
                $responseBody .= fgets($filePointer, 128);
            }
            if (isset($this->responseHeaders['transfer-encoding']) && $this->responseHeaders['transfer-encoding'] == 'chunked') {
                $data = $responseBody;
                $pos = 0;
                $len = strlen($data);
                $outData = '';

                while ($pos < $len) {
                    $rawnum = substr($data, $pos, strpos(substr($data, $pos), "\r\n") + 2);
                    $num = hexdec(trim($rawnum));
                    $pos+= strlen($rawnum);
                    $chunk = substr($data, $pos, $num);
                    $outData .= $chunk;
                    $pos+= strlen($chunk);
                }
                $responseBody = $outData;
            }
            $this->responseText = $responseBody;
            fclose($filePointer);
        }

        return $this;
    }

    /**
     * Parse response headers.
     *
     * @param  string $headers
     * @return void
     */
    protected function parseResponseHeaders($headers)
    {
        $this->responseHeaders = array();
        $lines = explode("\n", $headers);
        foreach ($lines as $line) {
            if ($line = trim($line)) {
                // parse headers to array
                if (empty($this->responseHeaders)) {
                    preg_match('#HTTP/.*?\s+(\d+)#', $line, $match);
                    $this->responseStatus = intval($match[1]);
                    $this->responseHeaders['status'] = $line;
                } elseif (strpos($line, ':')) {
                    list($key, $value) = explode(':', $line, 2);
                    $value = ltrim($value);
                    $key = strtolower($key);
                    // parse cookie
                    if ($key == 'set-cookie') {
                        $this->responseCookies .= $value . ';';
                    }
                    if (array_key_exists($key, $this->responseHeaders)) {
                        if (!is_array($this->responseHeaders[$key])) {
                            $temp = $this->responseHeaders[$key];
                            unset($this->responseHeaders[$key]);
                            $this->responseHeaders[$key][] = $temp;
                            $this->responseHeaders[$key][] = $value;
                        } else {
                            $this->responseHeaders[$key][] = $value;
                        }
                    } else {
                        $this->responseHeaders[$key] = $value;
                    }
                }
            }
        }
    }

    /**
     * Get response status code.
     *
     * @return integer
     */
    public function getResponseStatus()
    {
        return $this->responseStatus;
    }

    /**
     * Get response cookies.
     *
     * @return string
     */
    public function getResponseCookies()
    {
        return $this->responseCookies;
    }

    /**
     * Get response headers.
     *
     * @param  string|null   $name Null to get all headers
     * @return mixed|boolean False if get header by name and it is not exist
     */
    public function getResponseHeaders($name = null)
    {
        if ($name !== null) {
            if (array_key_exists($name, $this->responseHeaders)) {
                return $this->responseHeaders[$name];
            }

            return false;
        }

        return $this->responseHeaders;
    }

    /**
     * Get response text.
     *
     * @return string
     */
    public function getResponseText()
    {
        return $this->responseText;
    }

    /**
     * Get response text.
     *
     * @return string
     */
    public function _toString()
    {
        return $this->getResponseText();
    }

    /**
     * Get response cookies.
     *
     * @return string
     * @deprecated 2.6
     */
    public function getResponseCookie()
    {
        return $this->getResponseCookies();
    }

    /**
     * Read binary data of file.
     *
     * @param  string $filePath
     * @return string
     */
    protected function readBinary($filePath)
    {
        $binarydata = '';
        if (file_exists($filePath)) {
            $handle = fopen($filePath, "rb");
            while ($buff = fread($handle, 128)) {
                $binarydata .= $buff;
            }
            fclose($handle);
        }

        return $binarydata;
    }

    /**
     * Get mime type of file.
     *
     * @param  string $filePath
     * @return string
     */
    protected function getMimeType($filePath)
    {
        $filename = realpath($filePath);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (preg_match('/^(?:jpe?g|png|[gt]if|bmp|swf)$/', $extension)) {
            $file = getimagesize($filename);

            if (isset($file['mime'])) return $file['mime'];
        }
        if (class_exists('finfo', false)) {
            if ($info = new finfo(defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME)) {
                return $info->file($filename);
            }
        }
        if (ini_get('mime_magic.magicfile') && function_exists('mime_content_type')) {
            return mime_content_type($filename);
        }

        return false;
    }
}