<?php


namespace IotSpace\Ys;

use IotSpace\Support\ApiRequest;
use IotSpace\Exception\IotException;
use IotSpace\Exception\ErrorCode;
use IotSpace\Support\HttpMethod;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use IotSpace\Support\Platform;

abstract class BaseClient
{
    const CACHE_TOKEN_KEY = 'YS_ACCESS_TOKEN';

    /**
     * @var array
     */
    protected $config;

    /***
     * 构造函数
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    protected function getCacheToken()
    {
//        if(Cache::has(self::CACHE_TOKEN_KEY)){
//            return Cache::get(self::CACHE_TOKEN_KEY);
//        }else{
        $token = $this->getToken();
        return $token;
//        }
    }

    protected function getHeaders()
    {
        $headers = [
            'Host' => 'open.ys7.com',
            'Content-Type' => 'application/x-www-form-urlencoded',
//            'Content-Type' => 'application/json'
        ];

        return $headers;

    }

    /**
     * @param $url
     * @param array $postData
     * @param string $method
     * @param bool $withToken
     * @param bool $withHeaders
     * @return mixed
     * @throws IotException
     */
    protected function getHttpRequest($url, array $postData, $method = HttpMethod::POST, bool $withToken=true, bool $withHeaders=true)
    {
        $url = $this->config['api'].$url;
        $options = [];
        if($withHeaders){
            $options['headers'] = $this->getHeaders();
        }
        if($withToken){
            $postData['accessToken'] = $this->getCacheToken();
        }
        if($postData){
            $options['form_params'] = $postData;
        }
        $res = ApiRequest::httpRequest($method, $url, $options);
//        DB::table('iot_log')->insert([
//            'platform'=>Platform::YS,
//            'method'=>$method,
//            'url'=>$url,
//            'res_code'=>$res['code']??'',
//            'res_data'=>var_export($res, true),
//            'post_data'=>var_export($options, true),
//            'message'=>$res['msg']??'',
//            'createtime'=>date('Y-m-d H:i:s'),
//        ]);
        //todo 记录日志
        if((int)$res['code'] !== 200){
            throw new IotException($res['msg'], ErrorCode::YS, $res);
        }
        return $res['data']??true;
    }


    /**
     * 获取令牌
     * @return string
     * @throws \IotSpace\Exception\IotException
     */
    public function getToken(): string
    {
        $url = '/api/lapp/token/get';

//        if (Cache::has(self::CACHE_TOKEN_KEY)) {
//            $token = Cache::get(self::CACHE_TOKEN_KEY);
//            return $token;
//        }

        $key = $this->config['key'];
        $secret = $this->config['secret'];
        if(empty($key)){
            throw new IotException('缺少YS_KEY配置', ErrorCode::OPTIONS);
        }

        if(empty($secret)){
            throw new IotException('缺少YS_SECRET配置', ErrorCode::OPTIONS);
        }

        $postData = [
            'appKey' => $key,
            'appSecret' => $secret
        ];

        $data = $this->getHttpRequest($url, $postData, HttpMethod::POST, false, true);

        $accessToken = $data['accessToken'];
        $expireTime = $data['expireTime']; //Token过期时间  毫秒时间戳
        $this->accessToken = $accessToken;
        $expireDateTime = Carbon::createFromTimestampMs($expireTime);

//        Cache::put(self::CACHE_TOKEN_KEY, $accessToken, $expireDateTime);

        return $accessToken;
    }
}
