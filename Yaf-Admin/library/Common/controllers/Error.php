<?php
/**
 * 公用异常处理。
 * @author fingerQin
 * @date 2018-06-27
 */

namespace Common\controllers;

use finger\App;
use finger\Url;
use finger\Exception\FingerException;

class Error extends \Common\controllers\Common
{
    /**
     * 也可通过$request->getException()获取到发生的异常
     */
    public function errorAction($exception)
    {
        $trace   = $this->logWrapper($exception->__toString());
        $errCode = $exception->getCode();
        $errMsg  = $exception->getMessage();

        // [1] 参数验证错误
        // 如果抛出的是 ServiceException 业务异常,但是错误码不在注册的范围。也不能记录在业务错误日志。
        if ($exception instanceof FingerException) {
            if (App::isDebug()) { // 调试模式会输出具体的错误。
                $errMsg = ($errCode != STATUS_ERROR) ? $errMsg : $exception->__toString();
            }
            if ($errCode == STATUS_ERROR) {
                App::log($trace, 'errors', 'log');
            } else {
                App::log($trace, 'serviceErr', 'log');
            }
        } else {
            $errCode = STATUS_ERROR;
            $errMsg  = '服务器繁忙,请稍候重试';
            if (App::isDebug()) { // 调试模式会输出具体的错误。
                $errMsg = $trace;
            }
            App::log($trace, 'errors', 'log');
        }

        // [2] 根据是不同的请求类型响应不的数据。
        if ($this->_request->isXmlHttpRequest()) {
            $data = [
                'code' => (int)$errCode,
                'msg'  => $errMsg
            ];
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            $this->end();
        } else {
            if ($errCode == STATUS_LOGIN_TIMEOUT || $errCode == STATUS_NOT_LOGIN || $errCode == STATUS_OTHER_LOGIN) {
                $this->loginTips($errMsg, Url::createBackendUrl('Public', 'login'));
            } else {
                $this->error($errMsg, '', 0);
            }
        }
    }

    /**
     * 错误信息包装器。
     * 
     * @param string $log 错误信息。
     * @return string
     */
    protected function logWrapper($log)
    {
        $currentUrl = Url::getUrl();
        return "{$log}\nRequest Url:{$currentUrl}";
    }
}