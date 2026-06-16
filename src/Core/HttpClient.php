<?php
namespace App\Core;

class HttpClient
{

    public function sendRequest(string $url, array $headers = [], ?string $referer = null, ?string $user_agent = null, ?string $cookie_file = null): array
    {
        $ch = curl_init();

        if ($ch === false) {
            // cURL init error
            return [
                'success' => false,
                'data' => [],
                'error' => [
                    'code' => 'CURL_INIT_FAILED',
                    'message' => 'Failed to initialize HTTP client.',
                    'details' => null,
                ],
                'meta' => [
                    'url' => $url,
                ],
            ];
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_USERAGENT => $user_agent ?? 'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_REFERER => $referer,
            CURLOPT_ENCODING => '',
        ]);

        if ($cookie_file !== null) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        }

        $body = curl_exec($ch);

        $error_code = curl_errno($ch);
        $error_message = curl_error($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        // for test
        $header_params = curl_getinfo($ch, CURLOPT_HTTPHEADER);
        $referer_params = curl_getinfo($ch, CURLOPT_REFERER);

        curl_close($ch);

        if ($error_code !== 0) {
            // cURL error
            return [
                'success' => false,
                'data' => [],
                'error' => [
                    'code' => 'HTTP_REQUEST_FAILED',
                    'message' => 'Failed to load external source.',
                    'details' => 'cURL error #' . $error_code . ': ' . $error_message,
                ],
                'meta' => [
                    'url' => $url,
                    'status_code' => $status_code,
                    'content_type' => $content_type,
                    'curl_error_code' => $error_code,
                ],
            ];
        }

        if ($status_code < 200 || $status_code >= 300) {
            // bad HTTP status
            return [
                'success' => false,
                'data' => [],
                'error' => [
                    'code' => 'HTTP_BAD_STATUS',
                    'message' => 'External source returned an invalid HTTP status.',
                    'details' => 'HTTP status code: ' . $status_code,
                ],
                'meta' => [
                    'url' => $url,
                    'status_code' => $status_code,
                    'content_type' => $content_type,

                    //test
                    'header_params' => $header_params,
                    'referer_params' => $referer_params,
                ],
            ];
        }

        if ($body === false || trim($body) === '') {
            // empty response
            return [
                'success' => false,
                'data' => [],
                'error' => [
                    'code' => 'EMPTY_RESPONSE',
                    'message' => 'External source returned an empty response.',
                    'details' => null,
                ],
                'meta' => [
                    'url' => $url,
                    'status_code' => $status_code,
                    'content_type' => $content_type,
                ],
            ];
        }

        // success
        return [
            'success' => true,
            'data' => [
                'body' => $body,
                'content_type' => $content_type,
            ],
            'error' => null,
            'meta' => [
                'url' => $url,
                'status_code' => $status_code,
                'content_type' => $content_type,
            ],
        ];
    }
}

