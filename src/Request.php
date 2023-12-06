<?php

/**
 * 
 */
namespace TheFoundation;

/**
 * 
 */
class Request {

    /**
     * 
     */
    public static function json(string $url, array $payload = [], array $curl_options = [], \Closure $callback = null) {
        $curl_options = array_replace([
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_USERAGENT       => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/536.5 (KHTML, like Gecko) Chrome/19.0.1084.56 Safari/536.5',
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_SSL_VERIFYHOST  => false,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HEADER          => false,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_CONNECTTIMEOUT  => 30,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_HTTPHEADER      => [
                'charset=utf-8',
                'content-type: application/json',
            ],
        ], $curl_options, !$payload ? [] : [
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => json_encode($payload),
        ]);

        $curl = curl_init($url);
        curl_setopt_array($curl, $curl_options);

        $response = @json_decode(curl_exec($curl), \JSON_OBJECT_AS_ARRAY) ?: [];
        $errors = [
            'code' => $err_code = curl_errno($curl) ?: null,
            'description' => curl_strerror($err_code ?: 0) ?: null,
        ];
        $info = curl_getinfo($curl);
        curl_close($curl);

        if (!is_null($callback))
            return $callback($response, $errors, $info);

        return $response;
    }

    /**
     * 
     */
    public static function html(string $url, array $payload = [], array $curl_options = [], \Closure $callback = null) {
        $curl_options = array_replace([
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_USERAGENT       => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/536.5 (KHTML, like Gecko) Chrome/19.0.1084.56 Safari/536.5',
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_SSL_VERIFYHOST  => false,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HEADER          => false,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_CONNECTTIMEOUT  => 30,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_MAXREDIRS       => 10,
        ], $curl_options, !$payload ? [] : [
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => $payload,
        ]);

        $curl = curl_init($url);
        curl_setopt_array($curl, $curl_options);

        $response = curl_exec($curl) ?: null;
        $errors = [
            'code' => $err_code = curl_errno($curl) ?: null,
            'description' => curl_strerror($err_code) ?: null,
        ];
        $info = curl_getinfo($curl);

        if (!is_null($callback))
            return $callback($response, $errors, $info);

        return $response;
    }
}
?>