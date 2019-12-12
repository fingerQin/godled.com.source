<?php
/**
 * JSON 解密驱动。
 * @author fingerQin
 * @date 2018-09-25
 */

namespace Apis;

use finger\App;
use finger\Core;
use finger\DataInput;

class JsonDecodeDriver
{
    /**
     * 解析请求数据。
     * 
     * @param  array  $params  请求参数。
     *
     * @return array
     */
    public static function parse($params)
    {
        $sign   = DataInput::getString($params['post'], 'sign', '');
        $json   = DataInput::getString($params['post'], 'data', '');
        $params = json_decode($json, true);
        $params = is_array($params) ? $params : [];
        return array_merge($params, ['sign' => $sign, 'oriJson' => $json]);
    }

    /**
     * 验证码请求签名。
     * 
     * @param  array  $params     请求参数。
     * @param  string $apiSecret  API 密钥。
     *
     * @return bool
     */
    public static function checkSign($params, $apiSecret)
    {
        $str    = $params['oriJson'] . $apiSecret;
        $okSign = strtoupper(md5($str));
        if (App::getConfig('app.env') != ENV_DEV) {
            if (strlen($params['sign']) === 0 || $params['sign'] != $okSign) {
                Core::exception(STATUS_SERVER_ERROR, 'API signature error');
            }
        }
    }
}