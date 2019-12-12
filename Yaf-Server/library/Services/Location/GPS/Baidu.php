<?php
/**
 * 百度 GPS(经纬度) 定位。
 * --1) 文档地址：http://lbsyun.baidu.com/index.php?title=webapi/guide/webservice-geocoding-abroad
 * @author fingerQin
 * @date 2018-09-12
 */

namespace Services\Location\GPS;

use finger\App;
use finger\Cache;
use finger\Core;
use Models\District;

class Baidu
{
    /**
     * 获取定位数据。
     *
     * -- 定位结果，保留 30 分钟。
     * 
     * @param  float  $long  经度。
     * @param  float  $lat   纬度。
     *
     * @return array
     */
    public function get($long, $lat)
    {
        $cacheKey  = "loc-gps:{$long},{$lat}";
        $locResult = Cache::get($cacheKey);
        if ($locResult !== FALSE) {
            return $locResult;
        } else {
            $result = $this->request($long, $lat);
            if (empty($result)) {
                Core::exception(STATUS_SERVER_ERROR, '定位失败');
            }
            if ($result['status'] != 0) {
                App::log(['position' => 'baidu-gps', 'long' => $long, 'lat' => $lat], 'location', 'log');
                Core::exception(STATUS_SERVER_ERROR, '定位失败');
            }
            // @todo 后续将所有的地区数据放入缓存当中。加速定位的速度。
            $cityName      = $result['result']['addressComponent']['city'];
            $DistrictModel = new District();
            $district      = $DistrictModel->fetchOne([], [
                'city_name'   => $cityName, 
                'region_type' => District::REGION_TYPE_CITY
            ]);
            if (empty($district)) {
                Core::exception(STATUS_SERVER_ERROR, '定位失败');
            }
            $locResult = [
                'province_name' => $district['province_name'],
                'province_code' => $district['province_code'],
                'city_name'     => $district['city_name'],
                'city_code'     => $district['city_code']
            ];
            Cache::set($cacheKey, $locResult, 1800);
            return $locResult;
        }
    }

    /**
     * 向接口发送 POST 请求。
     * 
     * @param  float  $long  经度。
     * @param  float  $lat   纬度。
     * 
     * @return array
     */
    private function request($long, $lat)
    {
        $key = App::getConfig('location.gps.key');
        $url = "http://api.map.baidu.com/geocoder/v2/?location={$lat},{$long}&output=json&pois=0&ak={$key}";
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $response = curl_exec($ch);
        if (FALSE == $response) {
            $result = [];
        } else {
            $result = json_decode($response, true);
        }
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        if ($curlErrno != 0) {
            $log = [
                'curl_error' => $curlErrno,
                'curl_errno' => $curlError,
                'long'       => $long, 
                'lat'        => $lat
            ];
            App::log($log, 'curl', 'baidu-gps');
        }
        curl_close($ch);
        return $result;
    }
}