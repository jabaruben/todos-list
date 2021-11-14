<?php

/**
 * https://github.com/jabaruben/http-message-implementation
 */

namespace src\Infraestructure\HTTP;

// class Response extends Message implements \Psr\Http\Message\ResponseInterface
class Response extends Message
{

    /** @var array Map of standard HTTP status code/reason phrases */
    private static $phrases = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];

    /**
     * @var string
     */
    private $reasonPhrase = '';

    /**
     * @var int
     */
    private $statusCode = 200;

    /**
     * EOL characters used for HTTP response.
     *
     * @var string
     */
    public const EOL = "\r\n";

    /**
     *
     * @param int $statusCode
     * @param string $reasonPhrase
     * @param array $headers
     * @param Stream|string $body
     * @param string $protocolVersion
     */
    public function __construct(int $statusCode = 200, string $reasonPhrase = "", array $headers = array(), $body = null, string $protocolVersion = '1.1')
    {
        $this->statusCode = $statusCode;
        $this->reasonPhrase = $reasonPhrase;
        parent::__construct($headers, $body, $protocolVersion);
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus($code, $reasonPhrase = ''): self
    {
        $new = $this;
        if ($this->getImmutable()) {
            $new = clone $this;
        }

        $new->statusCode = (int) $code;
        if ($reasonPhrase == '' && isset(self::$phrases[$new->statusCode])) {
            $reasonPhrase = self::$phrases[$new->statusCode];
        }
        $new->reasonPhrase = $reasonPhrase;
        return $new;
    }

    public function send()
    {
        header("HTTP/" . $this->getProtocolVersion() . " " . $this->getStatusCode() . " " . $this->getReasonPhrase(), true, $this->getStatusCode());

        foreach ($this->getHeaders() as $name => $value) {
            header($name . ": " . implode(", ", $value), true);
        }

        echo (string) $this->getBody();
    }

    /**
     * Write JSON to Response Body.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * This method prepares the response object to return an HTTP Json
     * response to the client.
     *
     * @param  mixed     $data   The data
     * @param  int|null  $status The HTTP status code
     * @param  int       $options Json encoding options
     * @param  int       $depth Json encoding max depth
     * @return static
     */
    public function withJson($data, ?int $status = null, int $options = 0, int $depth = 512): Response
    {
        $json = (string) json_encode($data, $options, $depth);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(json_last_error_msg(), json_last_error());
        }

        $response = $this
            ->withHeader('Content-Type', 'application/json')
            ->withBody($json);

        if ($status !== null) {
            $response = $response->withStatus($status);
        }

        return $response;
    }

    /**
     * Redirect to specified location
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * This method prepares the response object to return an HTTP Redirect
     * response to the client.
     *
     * @param string    $url The redirect destination.
     * @param int|null  $status The redirect HTTP status code.
     * @return static
     */
    public function withRedirect(string $url, ?int $status = null): Response
    {
        $response = $this->response->withHeader('Location', $url);

        if ($status === null) {
            $status = 302;
        }
        $response = $response->withStatus($status);

        return new static($response, $this->streamFactory);
    }

    /**
     * Note: This method is not part of the PSR-7 standard.
     *
     * This method will trigger the client to download the specified file
     * It will append the `Content-Disposition` header to the response object
     *
     * @param string|resource|StreamInterface $file
     * @param string|null                     $name
     * @param bool|string                     $contentType
     *
     * @return static
     */
    public function withFileDownload($file, ?string $name = null, $contentType = true): Response
    {
        $disposition = 'attachment';
        $fileName = $name;

        if (is_string($file) && $name === null) {
            $fileName = basename($file);
        }

        if ($name === null && (is_resource($file) || $file instanceof Stream)) {
            $metaData = $file instanceof Stream
                ? $file->getMetadata()
                : stream_get_meta_data($file);

            if (is_array($metaData) && isset($metaData['uri'])) {
                $uri = $metaData['uri'];
                if ('php://' !== substr($uri, 0, 6)) {
                    $fileName = basename($uri);
                }
            }
        }

        if (is_string($fileName) && strlen($fileName)) {
            /*
             * The regex used below is to ensure that the $fileName contains only
             * characters ranging from ASCII 128-255 and ASCII 0-31 and 127 are replaced with an empty string
             */
            $disposition .= '; filename="' . preg_replace('/[\x00-\x1F\x7F\"]/', ' ', $fileName) . '"';
            $disposition .= "; filename*=UTF-8''" . rawurlencode($fileName);
        }

        return $this
            ->withFile($file, $contentType)
            ->withHeader('Content-Disposition', $disposition);
    }

    /**
     * Note: This method is not part of the PSR-7 standard.
     *
     * This method prepares the response object to return a file response to the
     * client without `Content-Disposition` header which defaults to `inline`
     *
     * You control the behavior of the `Content-Type` header declaration via `$contentType`
     * Use a string to override the header to a value of your choice. e.g.: `application/json`
     * When set to `true` we attempt to detect the content type using `mime_content_type`
     * When set to `false`
     *
     * @param string|resource|StreamInterface $file
     * @param bool|string                     $contentType
     *
     * @return static
     *
     * @throws RuntimeException If the file cannot be opened.
     * @throws InvalidArgumentException If the mode is invalid.
     */
    public function withFile($file, $contentType = true): Response
    {
        $response = $this->response;

        if (is_resource($file)) {
            $response = $response->withBody($this->streamFactory->createStreamFromResource($file));
        } elseif (is_string($file)) {
            $response = $response->withBody($this->streamFactory->createStreamFromFile($file));
        } elseif ($file instanceof Stream) {
            $response = $response->withBody($file);
        } else {
            throw new \InvalidArgumentException(
                'Parameter 1 of Response::withFile() must be a resource, a string ' .
                'or an instance of Psr\Http\Message\StreamInterface.'
            );
        }

        if ($contentType === true) {
            $contentType = is_string($file) ? mime_content_type($file) : 'application/octet-stream';
        }

        if (is_string($contentType)) {
            $response = $response->withHeader('Content-Type', $contentType);
        }

        return new static($response, $this->streamFactory);
    }

    /**
     * Write data to the response body.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * Proxies to the underlying stream and writes the provided data to it.
     *
     * @param string $data
     * @return static
     */
    public function write(string $data): Response
    {
        $this->response->getBody()->write($data);
        return $this;
    }

    /**
     * Is this response a client error?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->response->getStatusCode() >= 400 && $this->response->getStatusCode() < 500;
    }

    /**
     * Is this response empty?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return in_array($this->response->getStatusCode(), [204, 205, 304]);
    }

    /**
     * Is this response forbidden?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     * @api
     */
    public function isForbidden(): bool
    {
        return $this->response->getStatusCode() === 403;
    }

    /**
     * Is this response informational?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isInformational(): bool
    {
        return $this->response->getStatusCode() >= 100 && $this->response->getStatusCode() < 200;
    }

    /**
     * Is this response OK?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isOk(): bool
    {
        return $this->response->getStatusCode() === 200;
    }

    /**
     * Is this response not Found?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isNotFound(): bool
    {
        return $this->response->getStatusCode() === 404;
    }

    /**
     * Is this response a redirect?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isRedirect(): bool
    {
        return in_array($this->response->getStatusCode(), [301, 302, 303, 307, 308]);
    }

    /**
     * Is this response a redirection?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isRedirection(): bool
    {
        return $this->response->getStatusCode() >= 300 && $this->response->getStatusCode() < 400;
    }

    /**
     * Is this response a server error?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->response->getStatusCode() >= 500 && $this->response->getStatusCode() < 600;
    }

    /**
     * Is this response successful?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->response->getStatusCode() >= 200 && $this->response->getStatusCode() < 300;
    }

    /**
     * Convert response to string.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return string
     */
    public function __toString(): string
    {
        $output = sprintf(
            'HTTP/%s %s %s%s',
            $this->response->getProtocolVersion(),
            $this->response->getStatusCode(),
            $this->response->getReasonPhrase(),
            self::EOL
        );

        foreach ($this->response->getHeaders() as $name => $values) {
            $output .= sprintf('%s: %s', $name, $this->response->getHeaderLine($name)) . self::EOL;
        }

        $output .= self::EOL;
        $output .= (string) $this->response->getBody();

        return $output;
    }
}