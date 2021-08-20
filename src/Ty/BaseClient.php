<?php


namespace IotSpace\Ty;

use Carbon\Carbon;
use IotSpace\Support\ApiRequest;
use IotSpace\Exception\IotException;
use IotSpace\Exception\ErrorCode;
use IotSpace\Support\HttpMethod;
use IotSpace\Support\Platform;

abstract class BaseClient
{
    const CACHE_TOKEN_KEY = 'TY_ACCESS_TOKEN';

    /**
     * @var array
     */
    protected $config;

    /***
     * 构造函数
     * @param array $config
     */
    public function __construct(array $config = null)
    {
        $this->config = $config;
    }

    protected function getCacheToken()
    {
        //todo token缓存
//        if(Cache::has(self::CACHE_TOKEN_KEY)){
//            return Cache::get(self::CACHE_TOKEN_KEY);
//        }else{
        $token = $this->getToken();
        return $token;
//        }
    }

    protected function getHeaders(bool $withToken = true)
    {
        $timestamp = getMicroTime();

        $clientId = $this->config['client_id'];
        $secret   = $this->config['secret'];
        if (empty($clientId)) {
            throw new IotException('缺少TY_CLIENT_ID配置', ErrorCode::OPTIONS);
        }
        if (empty($secret)) {
            throw new IotException('缺少TY_SECRET配置', ErrorCode::OPTIONS);
        }

        if ($withToken) {
            $data = $clientId . $this->getCacheToken() . $timestamp;
        } else {
            $data = $clientId . $timestamp;
        }

        $hash = hash_hmac("sha256", $data, $secret);
        $sign = strtoupper($hash);

        $headers = [
            'client_id'   => $clientId,
            'sign'        => $sign,
            't'           => $timestamp,
            'sign_method' => 'HMAC-SHA256'
        ];

        if ($withToken) {
            $headers['access_token'] = $this->getCacheToken();
            $headers['Content-Type'] = 'application/json';
        }

        return $headers;

    }

    /**
     * @param $url
     * @param string $method
     * @param bool $withToken
     * @param null $body
     * @return mixed
     * @throws IotException
     */
    protected function getHttpRequest($url, $method = HttpMethod::GET, bool $withToken = true, $body = null)
    {
        $url     = $this->config['api'] . $url;
        $headers = $this->getHeaders($withToken);
        $options = [
            'headers' => $headers
        ];
        if ($body) {
            $options['body'] = $body;
        }
        $res = ApiRequest::httpRequest($method, $url, $options);

        //todo 记录日志
        if (!$res['success']) {
            throw new IotException($res['msg'], ErrorCode::TY, $res);
        }
        $res = $res['result'];
        return $res;
    }

    /**
     * 获取令牌
     * @return string
     * @throws \IotSpace\Exception\IotException
     */
    public function getToken(): string
    {
        $url = '/v1.0/token?grant_type=1';

//        if (Cache::has(self::CACHE_TOKEN_KEY)) {
//            $token = Cache::get(self::CACHE_TOKEN_KEY);
//            return $token;
//        }

        $data = $this->getHttpRequest($url, HttpMethod::GET, false);

        $accessToken = $data['access_token'];
        $expireTime = $data['expire_time']; //Token过期时间  秒

//        Cache::put(self::CACHE_TOKEN_KEY, $accessToken, Carbon::now()->addSeconds($expireTime - 5));
        return $accessToken;
    }

    public function refreshToken($refreshToken)
    {
        $url = "/v1.0/token/{$refreshToken}";

        $data = $this->getHttpRequest($url, HttpMethod::GET, false);

        $accessToken = $data['access_token'];
        $expireTime = $data['expire_time']; //Token过期时间  秒
        $ttl = $expireTime-time();

        Cache::put(self::CACHE_TOKEN_KEY, $accessToken, Carbon::now()->addSeconds($expireTime - 5));
        return $accessToken;
    }

}
