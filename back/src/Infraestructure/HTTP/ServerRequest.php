<?php

/**
 * https://github.com/jabaruben/http-message-implementation
 */

namespace src\Infraestructure\HTTP;

// class ServerRequest extends Request implements \Psr\Http\Message\ServerRequestInterface
class ServerRequest extends Request
{

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @var array
     */
    private $cookieParams = [];

    /**
     * @var array
     */
    private $serverParams = [];

    /**
     * @var array
     */
    private $queryParams = [];

    /**
     * @var array
     */
    private $uploadedFiles = [];

    /**
     * @var array|object
     */
    private $parsedBody = [];

    public function getAttribute($name, $default = null)
    {
        if (array_key_exists($name, $this->attributes) === false) {
            return $default;
        }

        return $this->attributes[$name];
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    public function getCookieParam($name, $default = null)
    {
        if (array_key_exists($name, $this->cookieParams) === false) {
            return $default;
        }

        return $this->cookieParams[$name];
    }

    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    // public function getParsedBodyParam($name, $default = null)
    // {
    //     if (array_key_exists($name, $this->parsedBody) === false) {
    //         return $default;
    //     }

    //     return $this->parsedBody[$name];
    // }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    // public function getQueryParam($name, $default = null)
    // {
    //     if (array_key_exists($name, $this->queryParams) === false) {
    //         return $default;
    //     }

    //     return $this->queryParams[$name];
    // }

    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    // public function getServerParam($name, $default = null)
    // {
    //     if (array_key_exists($name, $this->serverParams) === false) {
    //         return $default;
    //     }

    //     return $this->serverParams[$name];
    // }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     *
     * @param string $name
     * @param mixed $default
     * @return UploadedFile|mixed
     */
    public function getUploadedFile($name, $default = null)
    {
        if (array_key_exists($name, $this->uploadedFiles) === false) {
            return $default;
        }

        return $this->uploadedFiles[$name];
    }

    public function hasAttribute($name): bool
    {
        if (isset($this->attributes[$name])) {
            return true;
        }

        foreach ($this->attributes as $key => $value) {
            if (strtolower($key) == strtolower($name)) {
                return true;
            }
        }

        return false;
    }

    public function withAttribute($name, $value): self
    {
        $new = $this;
        if ($this->getImmutable()) {
            $new = clone $this;
        }

        $new->attributes[$name] = $value;

        return $new;
    }

    public function withCookieParams(array $cookies): self
    {
        $new = $this;
        if ($this->getImmutable()) {
            $new = clone $this;
        }

        $new->cookieParams = $cookies;
        return $new;
    }

    public function withParsedBody($data): self
    {
        $new = $this;
        if ($this->getImmutable()) {
            $new = clone $this;
        }

        $new->parsedBody = $data;
        return $new;
    }

    public function withQueryParams(array $query): self
    {
        $new = $this;
        if ($this->getImmutable()) {
            $new = clone $this;
        }

        $new->queryParams = $query;
        return $new;
    }

    public function withUploadedFiles(array $uploadedFiles): self
    {
        $new = $this;
        if ($this->getImmutable()) {
            $new = clone $this;
        }

        $new->uploadedFiles = $uploadedFiles;

        return $new;
    }

    public function withoutAttribute($name): self
    {
        if (array_key_exists($name, $this->attributes) === false) {
            return $this;
        }

        $new = $this;
        if ($this->getImmutable()) {
            $new = clone $this;
        }

        unset($new->attributes[$name]);
        return $new;
    }

    public function getClientIP()
    {
        foreach (array('CLIENT-IP', 'X-FORWARDED-FOR', 'X-FORWARDED', 'X-CLUSTER-CLIENT-IP', 'FORWARDED-FOR', 'FORWARDED') as $key) {
            if ($this->hasHeader($key) === true) {
                foreach ($this->getHeader($key) as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
                foreach ($this->getHeader($key) as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return $this->getServerParam("REMOTE_ADDR");
    }

    public function fromGlobals(): self
    {
        /** @var ServerRequest $new */
        $new = $this;
        if ($this->getImmutable()) {
            $new = clone $this;
        }

        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
            if (isset($_SERVER['CONTENT_TYPE'])) {
                $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
            }
        }

        foreach ($headers as $name => $value) {
            $new = $new->withHeader($name, [$value]);
        }

        $uri = new Uri();
        $uri = $uri->fromGlobals();

        $body = new Stream(fopen("php://input", "r"));
        $protocolVersion = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';

        $new->serverParams = $_SERVER;

        $new = $new->withMethod($method);
        $new = $new->withUri($uri);
        $new = $new->withBody($body);
        $new = $new->withProtocolVersion($protocolVersion);
        $new = $new->withCookieParams($_COOKIE);
        $new = $new->withQueryParams($_GET);

        if ($new->hasHeader("Content-Type") && strstr($new->getHeaderLine("Content-Type"), "application/json")) {
            $input = file_get_contents('php://input');
            $json = json_decode($input, true);
            if (is_array($json)) {
                $new = $new->withParsedBody($json);
            }
        } else if ($new->hasHeader("Content-Type") && strstr($new->getHeaderLine("Content-Type"), "multipart/form-data") && $new->getMethod() !== "POST") {
            $multipartFormData = $new->parseMultipartFormData($body->getContents(), $new->getHeaderLine("Content-Type"));
            $new = $new->withParsedBody($multipartFormData['params']);
            $new = $new->withUploadedFiles($multipartFormData['files']);
        } else {
            $new = $new->withParsedBody($_POST);

            $uploadedFiles = [];
            foreach ($_FILES as $name => $file) {
                if (is_string($file['tmp_name'])) {
                    $uploadedFiles[$name] = new UploadedFile($file['tmp_name'], $file['name'], $file['type'], $file['error']);
                } else {
                    $uploadedFiles[$name] = [];
                    foreach (array_keys($file['tmp_name']) as $key) {
                        $uploadedFiles[$name][] = new UploadedFile($file['tmp_name'][$key], $file['name'][$key], $file['type'][$key], $file['error'][$key]);
                    }
                }
            }
            $new = $new->withUploadedFiles($uploadedFiles);
        }

        return $new;
    }

    private function parseMultipartFormData(string $string, string $contentType)
    {
        $boundaryArray = [];
        preg_match('/boundary=(?<boundary>.*)$/', $contentType, $boundaryArray);
        $boundary = isset($boundaryArray['boundary']) ? $boundaryArray['boundary'] : null;

        if (empty($boundary)) {
            throw new \InvalidArgumentException("Can't find boundary in string");
        }
        $blocks = explode("--$boundary", $string);

        $files = [];
        $params = [];

        foreach ($blocks as $block) {
            $block = ltrim($block, "\n\r");

            $contentDisposition = [];
            preg_match("/Content-Disposition\:(.*?)(name=\"(?<name>.*?)\")(;\s)?(filename=\"(?<filename>.*?)\")?/", $block, $contentDisposition);

            if (!isset($contentDisposition['name'])) {
                continue;
            }
            $isArray = false;

            $keyMatch = [];
            if (preg_match("/(.*)\[(?<key>.*)\]$/", $contentDisposition['name'], $keyMatch) === 1) {
                $isArray = true;
                $contentDisposition['name'] = rtrim($contentDisposition['name'], "[" . $keyMatch['key'] . "]");
            }

            $value = preg_replace('/Content-(.*)[\n|\n\r]+/', '', $block);
            $value = rtrim($value, "\n\r");

            if (isset($contentDisposition['filename'])) {
                $contentType = [];
                preg_match("/Content-Type\:\s(?<value>[a-zA-Z\/\-]+)/", $block, $contentType);

                if (!isset($contentType['value'])) {
                    $contentType['value'] = null;
                }
                $file = tmpfile();
                fwrite($file, $value);

                $uploadedFile = new UploadedFile($file, $contentDisposition['filename'], $contentType['value']);

                if ($isArray) {
                    if (!isset($files[$contentDisposition['name']])) {
                        $files[$contentDisposition['name']] = [];
                    }
                    if (empty($keyMatch['key'])) {
                        $files[$contentDisposition['name']][] = $uploadedFile;
                    } else {
                        $files[$contentDisposition['name']][$keyMatch['key']] = $uploadedFile;
                    }
                } else {
                    $files[$contentDisposition['name']] = $uploadedFile;
                }
            } else {
                if ($isArray) {
                    if (!isset($params[$contentDisposition['name']])) {
                        $params[$contentDisposition['name']] = [];
                    }
                    if (empty($keyMatch['key'])) {
                        $params[$contentDisposition['name']][] = $value;
                    } else {
                        $params[$contentDisposition['name']][$keyMatch['key']] = $value;
                    }
                } else {
                    $params[$contentDisposition['name']] = $value;
                }
            }
        }

        return [
            "files" => $files,
            "params" => $params,
        ];
    }

    /**
     * Fetch serverRequest parameter value from body or query string (in that order).
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param  string $key The parameter key.
     * @param  mixed  $default The default value.
     *
     * @return mixed The parameter value.
     */
    public function getParam(string $key, $default = null)
    {
        $postParams = $this->getParsedBody();
        $getParams = $this->getQueryParams();
        $attributes = $this->getAttributes();
        $result = $default;

        if (is_array($postParams) && isset($postParams[$key])) {
            $result = $postParams[$key];
        } elseif (is_object($postParams) && property_exists($postParams, $key)) {
            $result = $postParams->$key;
        } elseif (isset($getParams[$key])) {
            $result = $getParams[$key];
        } elseif (isset($attributes[$key])) {
            $result = $attributes[$key];
        }

        return $result;
    }

    /**
     * Fetch associative array of body and query string parameters.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return mixed[]
     */
    public function getParams(): array
    {
        $params = $this->getQueryParams();
        $postParams = $this->getParsedBody();
        $attributes = $this->getAttributes();

        if ($postParams) {
            $params = array_merge($params, (array)$postParams);
        }

        if ($attributes) {
            $params = array_merge($params, (array)$attributes);
        }

        return $params;
    }

    /**
     * Fetch parameter value from serverRequest body.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getParsedBodyParam(string $key, $default = null)
    {
        $postParams = $this->getParsedBody();
        $result = $default;

        if (is_array($postParams) && isset($postParams[$key])) {
            $result = $postParams[$key];
        } elseif (is_object($postParams) && property_exists($postParams, $key)) {
            $result = $postParams->$key;
        }

        return $result;
    }

    /**
     * Fetch parameter value from query string.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getQueryParam(string $key, $default = null)
    {
        $getParams = $this->getQueryParams();
        $result = $default;

        if (isset($getParams[$key])) {
            $result = $getParams[$key];
        }

        return $result;
    }

    /**
     * Retrieve a server parameter.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function getServerParam(string $key, $default = null)
    {
        $serverParams = $this->serverRequest->getServerParams();
        return isset($serverParams[$key]) ? $serverParams[$key] : $default;
    }

    /**
     * Is this an XHR serverRequest?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isXhr(): bool
    {
        return $this->serverRequest->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Is this a DELETE serverRequest?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    /**
     * Is this a GET serverRequest?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    /**
     * Is this a HEAD serverRequest?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isHead(): bool
    {
        return $this->isMethod('HEAD');
    }

    /**
     * Does this serverRequest use a given method?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param  string $method HTTP method
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return $this->serverRequest->getMethod() === $method;
    }

    /**
     * Is this a OPTIONS serverRequest?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isOptions(): bool
    {
        return $this->isMethod('OPTIONS');
    }

    /**
     * Is this a PATCH serverRequest?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isPatch(): bool
    {
        return $this->isMethod('PATCH');
    }

    /**
     * Is this a POST serverRequest?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    /**
     * Is this a PUT serverRequest?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }
}