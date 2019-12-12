<?php
/**
 * 短信发送接口。
 * @author fingerQin
 * @date 2018-06-27
 */

namespace Apis\app\v100\Sms;

use finger\Ip;
use Apis\AbstractApi;
use Services\Sms\Sms;

class SmsSendApi extends AbstractApi
{
    /**
     * 逻辑处理。
     * 
     * @return void
     */
    protected function runService()
    {
        $this->isAllowAccessApi(0);
        $mobile   = $this->getString('mobile', '');
        $key      = $this->getString('key', '');
        $platform = $this->getString('platform');
        $ip       = Ip::ip();
        $result   = Sms::send($mobile, $key, $ip, $platform);
        $this->render(STATUS_SUCCESS, '发送成功', $result);
    }
}
