<?php

class ThirdOpenPlatform
{
    /** @var string  微信授权页面扫码授权 baseUrl*/
    const wxAuthorizationBaseUrl = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?';
    /** @var string 获取微信预授权码 pre_auth_code url */
    const wxPreAuthCodeUrl = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token=';
    /** @var string 获取微信第三方平台 component_access_token url */
    const wxComponentAccessToken = 'https://api.weixin.qq.com/cgi-bin/component/api_component_token';
    /** @var string 使用授权码换取公众号接口调用凭据和授权信息 url */
    const wxAuthTokenAndPermission = 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token=';
    /** @var string 获取授权公众号账号基本信息 */
    const wxAuthorizationAccountsInfo = 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token=';
    /** @var string 获取账号的关注者列表请求uri */
    const wxFocusUsersList = 'https://api.weixin.qq.com/cgi-bin/user/get?';
    /** @var string 批量获取粉丝基本信息 */
    const wxBulkUserInfoList = 'https://api.weixin.qq.com/cgi-bin/user/info/batchget?access_token=';
    /** @var string 获取刷新授权公众号或小程序的接口调用凭据 */
    const wxRefreshToken = 'https:// api.weixin.qq.com /cgi-bin/component/api_authorizer_token?component_access_token=';
    /** @var string 存储微信推送的 component_verify_ticket 文件 */
    const wxComponentVerifyTicketFile = 'component_verify_ticket';
    /** @var string 微信推送请求记录（出错调试) */
    const wxPushLogFile = 'weChatPushUri.log';
    /** @var string 微信全网发布测试微信号 */
    const wxTestUsername = 'gh_3c884a361561';
    /** @var string 微信全网发布测试推送文本消息 content */
    const wxTestPushTextContent = 'TESTCOMPONENT_MSG_TYPE_TEXT';
    /** @var string 微信全网发布测试响应文本消息 content */
    const wxTestResponseTextContent = 'TESTCOMPONENT_MSG_TYPE_TEXT_callback';
    /** @var \Redis  */
    private $redis;
    /**
     * WeChat constructor.
     */
    public function __construct()
    {
        $this->redis = new \Redis;
        $this->redis->connect('127.0.0.1');
    }
    /**
     * 获取微信授权参数
     * @return array
     */
    public function getAuthURI()
    {
        $preAuthCode = $this->getPreAuthCode();
        $authRedirectUrl = http_build_query([
            'component_appid' => static::getWxConfig('appId'),
            'pre_auth_code' => $preAuthCode,
            'auth_type' => 1
        ]);
        return [
            'baseUrl' => self::wxAuthorizationBaseUrl,
            'params' => [
                'component_appid' => static::getWxConfig('appId'),
                'pre_auth_code' => $preAuthCode,
                'redirect_uri' => '前端自定义',
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
            'component_appid' => static::getWxConfig('appId'),
            'authorization_code' => $auth_code,
        ]);
        if (!isset($wxAuthTokenAndPermission['authorization_info']['authorizer_refresh_token'])) {
            ErrorReport::errorReporting([
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
            'component_appid' => static::getWxConfig('appId'),
            'authorizer_appid' => $authorizer_appid,
        ]);
        return [
            $accountsInfo['authorizer_info']
        ];
    }
    /**
     * 获取微信预授权码 pre_auth_code
     */
    private function getPreAuthCode()
    {
        $preAuthCode = static::postWxRequest(self::wxPreAuthCodeUrl . $this->getComponentAccessToken(), [
            'component_appid' => static::getWxConfig('appId'),
        ]);
        if (!isset($preAuthCode['pre_auth_code'])) {
            ErrorReport::errorReporting([
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
            'component_appid' => static::getWxConfig('appId'),
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
            'component_appid' => static::getWxConfig('appId'),
            'component_appsecret' => static::getWxConfig('appSecret'),
            'component_verify_ticket' => $this->getComponentVerifyTicket(),
        ]);
        if (!isset($component_access_token)) {
            ErrorReport::errorReporting([
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
        if (!file_exists(self::wxComponentVerifyTicketFile)) {
            ErrorReport::errorReporting('当前服务器未存在微信推送的 component_verify_ticket 文件');
        }
        return file_get_contents(self::wxComponentVerifyTicketFile);
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
     * 获取微信开放平台相关配置
     * @param $key
     * @return mixed|null
     */
    public static function getWxConfig($key = '')
    {
        $wxConfig = [
            'appId' => 'AppId',
            'appSecret' => 'AppSecret',
            'token' => '消息验证 token',
            'key' => '加解密 key 43 位',
        ];
        if (key_exists($key, $wxConfig)) {
            return $wxConfig[$key];
        }
        return $wxConfig;
    }
    /**
     * 微信消息验签
     * @param array $signatureParams
     * @param $signature
     * @return bool
     */
    public function verifyWeChatMsg(array $signatureParams, $signature)
    {
        $signatureParams[] = static::getWxConfig('token');
        sort($signatureParams, SORT_STRING);
        $signatureString = implode($signatureParams);
        $sign = sha1($signatureString);
        if ($signature === $sign) {
            return true;
        }
        Log::runtimeEventLog([
            'event' => '微信推送验签失败',
            'signatureParams' => $signatureParams,
            'signature' => $signature,
        ]);
        return false;
    }
    /**
     * 微信消息解密
     * @param $encrypt
     * @return array
     */
    public function decryptMsg($encrypt)
    {
        $aesKey = base64_decode(static::getWxConfig('key'));
        $aesIv = substr($aesKey, 0, 16);
        $decrypt = openssl_decrypt(base64_decode($encrypt), 'aes-256-cbc', $aesKey, OPENSSL_NO_PADDING, $aesIv);
        $originMsg = [];
        if ($decrypt) {
            $removePKCS7Padding = function ($decrypt) {
                $pad = ord(substr($decrypt, -1));
                if ($pad < 1 || $pad > 32) {
                    $pad = 0;
                }
                return substr($decrypt, 0, (strlen($decrypt) - $pad));
            };
            $decryptRemovePadding = $removePKCS7Padding($decrypt);
            $moveHeadPadding = function ($decrypt) {
                $content = substr($decrypt, 16);
                $contentLength = unpack('N', substr($content, 0, 4));
                $xmlLength = $contentLength[1];
                $xmlContent = substr($content, 4, $xmlLength);
                return $xmlContent;
            };
            $originXmlMsg = simplexml_load_string($moveHeadPadding($decryptRemovePadding));
            foreach ($originXmlMsg as $xmlElement) {
                $originMsg[$xmlElement->getName()] = rtrim((string) $xmlElement);
            }
        } else {
            Log::apiRequestLog([
                'postXml' => file_get_contents('php://input'),
            ], self::wxPushLogFile);
            Log::runtimeEventLog([
                'event' => '微信推送 ComponentVerifyTicket 解密失败',
            ]);
        }
        return $originMsg;
    }
    /**
     * 微信加密消息
     * @param $origin
     * @return string
     */
    public function encryptMsg($origin)
    {
        $aesKey = base64_decode(static::getWxConfig('key'));
        $aesIv = substr($aesKey, 0, 16);
        $headPadding = function ($text) {
            $randomArray = [
                'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
                '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
                'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'
            ];
            array_shift($randomArray);
            $random = implode(',', array_slice($randomArray, 0, 16));
            return $random . pack('N', strlen($text)) . $text . static::getWxConfig('appId');
        };
        $originHeadPadding = $headPadding($origin);
        $PKCS7Padding = function ($text) {
            $textLength = strlen($text);
            $amountToPad = 32 - ($textLength % 32);
            if ($amountToPad === 0) {
                $amountToPad = 32;
            }
            $padChr = chr($amountToPad);
            $tmp = '';
            for ($i = 0; $i < $amountToPad; $i++) {
                $tmp .= $padChr;
            }
            return $text . $tmp;
        };
        $originPKCS7Padding = $PKCS7Padding($originHeadPadding);
        return base64_encode(openssl_encrypt($originPKCS7Padding, 'aes-256-cbc', $aesKey, OPENSSL_NO_PADDING, $aesIv));
    }
    /**
     * 生成加密消息签名
     * @param $encryptMsg
     * @param $nonce
     * @param $timestamp
     * @return string
     */
    public function generateEncryptMsgSignature($encryptMsg, $nonce, $timestamp)
    {
        $signatureParams = [
            static::getWxConfig('token'),
            $timestamp,
            $nonce,
            $encryptMsg
        ];
        sort($signatureParams, SORT_STRING);
        $signature = implode($signatureParams);
        return sha1($signature);
    }

    public function __destruct()
    {
        $this->redis->close();
    }
}