<?php

class WeChatAccess
{
    /** @var string  微信授权页面扫码授权 baseUrl*/
    const wxAuthorizationBaseUrl = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?';
    /** @var string 获取微信预授权码 pre_auth_code url */
    const wxPreAuthCodeUrl = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token=';
    /** @var string 获取微信第三方平台 component_access_token url */
    const wxComponentAccessToken = 'https://api.weixin.qq.com/cgi-bin/component/api_component_token';
    /** @var string 微信 AppId 缓存 key */
    const wxAppIdCacheKey = 'wxOpenPlatformAppId';
    /** @var string  微信 AppSecret 缓存 key */
    const wxAppSecretCacheKey = 'wxOpenPlatformAppSecret';
    /** @var string 微信配置文件位置 */
    const wxConfigPath = 'three_part/wxConfig.php';
    /** @var string 微信授权回调地址 */
    const wxAuthRedirect = 'www.51liuliuqiu.cn/weixin/authRedirect';
    /** @var string 使用授权码换取公众号接口调用凭据和授权信息 url */
    const wxAuthTokenAndPermission = 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token=';
    /** @var string 获取授权公众号账号基本信息 */
    const wxAuthorizationAccountsInfo = 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token=';
    /** @var string 获取账号的关注者列表请求uri */
    const wxFocusUsersList = 'https://api.weixin.qq.com/cgi-bin/user/get?';
    /** @var string 批量获取粉丝基本信息 */
    const wxBulkUserInfoList = 'https://api.weixin.qq.com/cgi-bin/user/info/batchget?access_token=ACCESS_TOKEN';
    /** @var string 获取刷新授权公众号或小程序的接口调用凭据 */
    const wxRefreshToken = 'https:// api.weixin.qq.com /cgi-bin/component/api_authorizer_token?component_access_token=';
    /** @var string 微信开放平台 appID */
    private $wxOpenPlatformAppId;
    /** @var string 微信开放平台 secret */
    private $wxOpenPlatformAppSecret;
    /** @var \Redis  */
    private $redis;


    /**
     * WeChat constructor.
     */
    public function __construct()
    {
        $this->redis = new \Redis;
        $this->redis->connect('127.0.0.1');
        $this->getWxConfig();
    }

    /**
     * 获取微信授权参数
     * @return array
     */
    public function getAuthURI()
    {
        $authRedirectUrl = http_build_query([
            'component_appid' => $this->wxOpenPlatformAppId,
            'pre_auth_code' => $this->getPreAuthCode(),
            'redirect_uri' => self::wxAuthRedirect,
            'auth_type' => 1
        ]);
        return [
            'baseUrl' => self::wxAuthorizationBaseUrl,
            'params' => [
                'component_appid' => $this->wxOpenPlatformAppId,
                'pre_auth_code' => $this->getPreAuthCode(),
                'redirect_uri' => self::wxAuthRedirect,
                'auth_type' => 1,
                'access_token' => $this->redis->get('component_access_token'),
            ],
            'redirect' => self::wxAuthorizationBaseUrl . $authRedirectUrl,
            'method' => 'get',
        ];
    }

    /**
     * 使用授权码换取微信公众号或小程序的接口调用凭据和授权信息
     * @param $auth_code
     * @return mixed
     */
    public function getWxAuthTokenAndPermission($auth_code)
    {
        $wxAuthTokenAndPermission = $this->postWxRequest(self::wxAuthTokenAndPermission . $this->getComponentAccessToken(), [
            'component_appid' => $this->wxOpenPlatformAppId,
            'authorization_code' => $auth_code,
        ]);
        if (!isset($wxAuthTokenAndPermission['authorization_info']['authorizer_refresh_token'])) {
            $this->errorReporting([
                'errorMsg' => "服务器内部错误,使用授权码换取微信公众号或小程序的接口调用凭据和授权信息失败",
                'errorInfo' => $wxAuthTokenAndPermission,
            ]);
        }
        $wxAuthTokenAndPermission['authorization_info']['expires_in'] += time();
        return $wxAuthTokenAndPermission;
    }

    /**
     * 获取授权方的账号基本信息
     * @param $authorizer_appid string 授权信息 authorizer_appid
     * @return array
     */
    public function getWxAuthorizationAccountsInfo($authorizer_appid)
    {
        $requestWxAuthorizationAccountsInfoUri = self::wxAuthorizationAccountsInfo . $this->getComponentAccessToken();
        $accountsInfo = $this->postWxRequest($requestWxAuthorizationAccountsInfoUri, [
            'component_appid' => $this->wxOpenPlatformAppId,
            'authorizer_appid' => $authorizer_appid,
        ]);
        return [
            $accountsInfo['authorizer_info']
        ];
    }

    /**
     * 获取微信 pre_auth_code
     */
    private function getPreAuthCode()
    {
        $preAuthCode = static::postWxRequest(self::wxPreAuthCodeUrl . $this->getComponentAccessToken(), [
            'component_appid' => $this->wxOpenPlatformAppId,
        ]);
        if (!isset($preAuthCode['pre_auth_code'])) {
            $this->errorReporting([
                'errorMsg' => '服务器内部错误,获取微信 pre_auth_code 失败',
                'errorInfo' => $preAuthCode,
            ]);
        }
        return $preAuthCode['pre_auth_code'];
    }

    /**
     * 获取微信公众号粉丝关注列表
     * @param $access_token
     * @param string $next_openid
     * @return mixed
     */
    public static function getFocusUserList($access_token, $next_openid = '')
    {
        $wxRequestUri = self::wxFocusUsersList . http_build_query(['access_token' => $access_token, 'next_openid' => $next_openid]);
        $userList = json_decode(file_get_contents($wxRequestUri), true);
        $redis = new \Redis;
        $redis->connect('127.0.0.1');
        $cacheOpenIdKey = $access_token . 'openids';
        if ($redis->exists($cacheOpenIdKey)) {
            $openid = json_decode($redis->get($cacheOpenIdKey));
            $redis->set($cacheOpenIdKey, json_encode(array_merge($openid, $userList['data']['openid'])));
        } else {
            $redis->set($cacheOpenIdKey, json_encode($userList['data']['openid']));
        }
        $userList['data']['openid'] = $cacheOpenIdKey;
        $redis->close();
        while ($userList['data']['next_openid']) {
            static::getFocusUserList($access_token, $userList['data']['next_openid']);
        }
        return [
            'total' => $userList['total'],
            'openIds' => $cacheOpenIdKey,
        ];
    }

    /**
     * 批量获取微信用户基本信息
     * @param $access_token
     * @param $openIdList
     * @return mixed
     */
    public static function getFansListInfo($access_token, $openIdList)
    {
        $requestBulkUserInfoListUri = self::wxBulkUserInfoList . $access_token;
        $requestBulkUserInfoListParams = [];
        foreach ($openIdList as $openId) {
            $requestBulkUserInfoListParams[] = [
                'openid' => $openId,
                'lang' => "zh_CN",
            ];
        }
        $userInfoList = static::postWxRequest($requestBulkUserInfoListUri, [
            'user_list' =>  $requestBulkUserInfoListParams,
        ]);
        return json_decode($userInfoList, true)['user_info_list'];
    }

    /**
     * 刷新授权公众号调用 token
     * @param $authorizer_appid
     * @param $authorizer_refresh_token
     * @return mixed
     */
    public function refreshToken($authorizer_appid, $authorizer_refresh_token)
    {
        $requestRefreshTokenUrl = self::wxRefreshToken . $this->getComponentAccessToken();
        $refreshToken = static::postWxRequest($requestRefreshTokenUrl, [
            'component_appid' => $this->wxOpenPlatformAppId,
            'authorizer_appid' => $authorizer_appid,
            'authorizer_refresh_token' => $authorizer_refresh_token
        ]);
        return $refreshToken;
    }

    /**
     * 获取微信 component_access_token
     * @return string
     */
    private function getComponentAccessToken()
    {
        if ($this->redis->exists('component_access_token') & $this->redis->ttl('component_access_token') > 600) {
            return $this->redis->get('component_access_token');
        }
        $component_access_token = static::postWxRequest(self::wxComponentAccessToken, [
            'component_appid' => $this->wxOpenPlatformAppId,
            'component_appsecret' => $this->wxOpenPlatformAppSecret,
            'component_verify_ticket' => $this->getComponentVerifyTicket(),
        ]);
        if (!isset($component_access_token)) {
            $this->errorReporting([
                'errorMsg' => '服务器内部错误,获取微信component_access_token失败',
                'errorInfo' => $component_access_token,
            ]);
        }
        $this->redis->set('component_access_token', $component_access_token['component_access_token'], $component_access_token['expires_in']);
        return $component_access_token['component_access_token'];
    }

    /**
     * 获取微信推送的 component_verify_ticket
     * @return string
     */
    private function getComponentVerifyTicket()
    {
        if (!file_exists('three_part/receiveTicket.txt')) {
            $this->errorReporting('当前服务器未存在微信推送的 component_verify_ticket 文件');
        }
        return json_decode(file_get_contents('three_part/receiveTicket.txt'), true)['component_verify_ticket'];
    }

    /**
     * 发起微信 post 请求
     * @param $url
     * @param $params
     * @return mixed
     */
    private static function postWxRequest($url, $params)
    {
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => json_encode($params),
                'timeout' => 15 * 60 // 超时时间（单位:s）
            ]
        ];
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return json_decode($result,true);
    }

    /**
     * 获取微信相关配置
     */
    private function getWxConfig()
    {
        if (!$this->redis->exists(self::wxAppIdCacheKey) || !$this->redis->exists(self::wxAppSecretCacheKey)) {
            if (!file_exists(self::wxConfigPath)) {
                $this->errorReporting('当前服务器不存在相关配置文件');
            }
            $wxConfig = include self::wxConfigPath;
            $this->wxOpenPlatformAppId = $wxConfig['AppId'];
            $this->wxOpenPlatformAppSecret = $wxConfig['AppSecret'];
            $this->redis->set(self::wxAppIdCacheKey, $this->wxOpenPlatformAppId, 0);
            $this->redis->set(self::wxAppSecretCacheKey, $this->wxOpenPlatformAppSecret, 0);
        } else {
            $this->wxOpenPlatformAppId = $this->redis->get(self::wxAppIdCacheKey);
            $this->wxOpenPlatformAppSecret = $this->redis->get(self::wxAppSecretCacheKey);
        }
    }

    /**
     * 全局报错
     * @param $message
     */
    protected function errorReporting($message)
    {
        if (is_string($message)) {
            $message = ['errorMsg' => $message];
        }
        $this->writeErrorLog($message);
        header("HTTP/1.1 500");
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode($message, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 记录错误日志
     * @param $error
     */
    private function writeErrorLog($error)
    {
        file_put_contents('requestWxOpenPlatError.log', json_encode([
                'errorTime' => date('Y-m-d H:i:s'),
                'errorInfo' => $error,
                'errorClass' => __CLASS__,
                'errorFile' => __FILE__
            ], JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        $this->redis->close();
    }
}