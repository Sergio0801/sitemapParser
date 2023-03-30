<?php

namespace parsers;

/**
 * CurlRequest class
 *
 */
class CurlRequest
{
    /**
     * @var string
     */
    public string $url;

    /**
     * @var array
     */
    public array $headers = [];

    /**
     * @var string
     */
    public string $username;
    /**
     * @var string
     */
    public string $password;

    /**
     * @var string
     */
    public ?string $responseHeaders = null;

    /**
     * @var bool
     */
    public bool $outputHeadersInfo = false;


    /**
     * @param bool $decode
     * @param bool $auth
     * @param array $customConfig
     * @param int $timeout
     * @return bool|mixed|string
     */
    public function sendGet(bool $decode = true, bool $auth = false, array $customConfig = [], int $timeout = 120)
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
                CURLOPT_URL => $this->url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => $this->headers ?: []
            ] + $customConfig);

        if ($auth) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->username . ":" . $this->password);
        }

        $response = curl_exec($ch);

        if (curl_error($ch)) {
            return curl_error($ch);
        }

        if($this->outputHeadersInfo === true) {
            $this->responseHeaders = json_encode(curl_getinfo($ch));
        }

        curl_close($ch);

        if ($decode) {
            return json_decode($response, true);
        } else {
            return $response;
        }
    }
}