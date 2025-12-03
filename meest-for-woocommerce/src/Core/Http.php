<?php
namespace MeestShipping\Core;

use WP_Http;
use MeestShipping\Exceptions\MissingTokenException;
use MeestShipping\Exceptions\BadRequestException;
use MeestShipping\Exceptions\UnauthorizedRequestException;

class Http
{
    private $options;
    private $http;

    public function __construct()
    {
        $this->options = meest_init('Option')->all();
        $this->http = new WP_Http();
    }

    public function makeUri($urn, $query = [], $params = []): string
    {
        $urn = $this->options['urns'][$urn];

        return $this->options['url']
            .(!empty($query) ? strtr($urn, $query) : $urn)
            .(!empty($params) ? '?'.http_build_query($params) : null);
    }

    public function request($method, $urn, $params = [], $query = [], $file = false)
    {
        $data = [
            'method' => $method,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'timeout' => 30, // Увеличиваем таймаут до 30 секунд
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'sslverify' => true,
        ];

        if (!in_array($urn, ['auth_get', 'auth_refresh'])) {
            $this->options['tokens'] = meest_init('Option')->checkTokens($this->options['tokens']);

            if (empty($this->options['tokens']['token'])) {
                throw new MissingTokenException();
            }

            $data['headers']['token'] = $this->options['tokens']['token'];
        }

        if ($method === 'GET') {
            $uri = $this->makeUri($urn, $query, $params);
        } else {
            $uri = $this->makeUri($urn, $query);
            $data['body'] = json_encode($params, JSON_UNESCAPED_UNICODE);
        }

        $response = $this->http->request($uri, $data);

        if (is_array($response)) {
            if (!empty($response['response']) && $response['response']['code'] === 200) {
                if ($file === false) {
                    $body = json_decode($response['body'], true);

                    if ($body['status'] === 'OK') {
                        return $body['result'];
                    }
                } else {
                    return $response['body'];
                }
            } elseif (!empty($response['response']) && $response['response']['code'] === 401) {
                throw new UnauthorizedRequestException($response);
            } else {
                throw new BadRequestException($response, $response['response']['code'] ?? 400);
            }
        } else {
            // Обработка ошибок cURL (включая таймауты)
            $error_message = $response->get_error_message();
            $error_code = $response->get_error_code();
            
            // Логируем ошибку для отладки
            error_log('Meest API Error: ' . $error_code . ' - ' . $error_message . ' | URI: ' . $uri);
            
            // Если это таймаут, выводим более понятное сообщение
            if (strpos($error_message, 'timed out') !== false || strpos($error_message, 'timeout') !== false) {
                wp_die('API Meest не отвечает. Попробуйте позже или обратитесь в поддержку. Ошибка: ' . $error_message);
            }
            
            wp_die($error_message);
        }
    }

    public static function addSettingsError($setting, $code, $message, $type = 'error')
    {
        global $wp_settings_errors;

        $wp_settings_errors[] = array(
            'setting' => $setting,
            'code'    => $code,
            'message' => $message,
            'type'    => $type,
        );
    }
}
