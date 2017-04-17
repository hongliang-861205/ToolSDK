<?php
namespace Home\Common;
class WeixinModel {
	private $token = 'hongliang861205';
	private $appid = 'wxbc4783cf51615381';
	private $appsecret = 'cb5b3d690af7d3f242e86b823a46215f';
	private $webchat_url = "";
	
	/**
	 *微信公众号配置验证
	 */
    public function checkSignature() {
		$timestamp = $_GET['timestamp'];
		$nonce = $_GET['nonce'];
		$token = $this->token;
		$signature = $_GET['signature'];
		
		$arr = array($timestamp, $nonce, $token);
		sort($arr);
		$str = implode($arr);
		$str = sha1($str);
		
		if($str == $signature) {
			return true;
		} else {
			return false;
		}
	}
	
	public function validWeixin() {
		$echoStr = $_GET['echostr'];
		
		if($this->checkSignature()) {
			echo $echoStr;
			exit;
		}
	}
    
	/**
	 *用户关注微信公众号时自动回复消息
	 *$postObj Xml文件转换成的对象
	 *$content 回复的内容
	 *$messageType 回复的文本类型
	 */
    public function responseOnSubscribe($postObj, $content, $messageType='text') {
        if(strtolower(trim($postObj->MsgType)) == 'event') {
            if(strtolower(trim($postObj->Event)) == 'subscribe') {
                $this->responseInfo($postObj, $content, $messageType);
            }
        }
    }
	
	/**
	 *自定义菜单用户点击时回复信息
	 *$postObj Xml文件转换成的对象
	 *$content 回复的内容
	 *$eventKey 点击类型的菜单的KEY
	 *$messageType 回复的文本类型
	 */
	public function responseOnMenuClick($postObj, $content, $eventKey='', $messageType='text') {
		if(strtolower(trim($postObj->MsgType)) == 'event') {
			if(strtolower(trim($postObj->Event)) == 'click') {
				if(strtolower(trim($postObj->EventKey)) === $eventKey) {
					$this->responseInfo($postObj, $content, 'news');
				}
			}
		}
	}
	
	/**
	 *回复信息
	 *$postObj Xml文件转换成的对象
	 *$content 回复的内容
	 *$messageType 回复的文本类型
	 */
	public function responseInfo($postObj, $content, $messageType='text') {
		switch($messageType) {
			case 'text':
				$this->responseSimpleText($postObj, $content);
				break;
			case 'news':
				$this->responsePicAndText($postObj, $content);
				break;
		}
	}
    
	/**
	 *回复单文本信息
	 *$postObj Xml文件转换成的对象
	 *$content 回复的文本内容
	 */
    private function responseSimpleText($postObj, $content) {
		$toUser = $postObj->FromUserName;
		$fromUser = $postObj->ToUserName;
		$createTime = time();
		$messageType = 'text';
		$template = '<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[%s]]></MsgType>
					<Content><![CDATA[%s]]></Content>
					</xml>';
		$message = sprintf($template, $toUser, $fromUser, $createTime, $messageType, $content);
		echo $message;
	}
	
	/**
	 *回复图文信息
	 *$postObj Xml文件转换成的对象
	 *$postArr 回复图文信息数值
	 */
	private function responsePicAndText($postObj, $postArr=array()) {
		$toUser = $postObj->FromUserName;
		$fromUser = $postObj->ToUserName;
		$createTime = time();
		$template = "
					<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[news]]></MsgType>
					<ArticleCount>".count($postArr)."</ArticleCount>
					<Articles>";
		foreach($postArr as $key=>$value){
			$template .= "
						<item>
						<Title><![CDATA[{$value['title']}]]></Title> 
						<Description><![CDATA[{$value['description']}]]></Description>
						<PicUrl><![CDATA[{$value['picUrl']}]]></PicUrl>
						<Url><![CDATA[{$value['url']}]]></Url>
						</item>
						";
		}
		$template .= "
					</Articles>
					</xml>";
		
		$message = sprintf($template, $toUser, $fromUser, $createTime);
		echo $message;
	}
	
	/**
	 *C_URL方式请求
	 *$url 请求的地址
	 *$method 请求的方式
	 *$parmType 请求的参数的格式
	 *$postArr 请求的参数数据
	 */
	public function http_curl($url, $method='get', $parmType='json', $postArr=null) {
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查  
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在	
		if($method == 'post') {
			curl_setopt($ch, CURLOPT_POST);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postArr);
		}
		
		$output = curl_exec($ch);
		
		curl_close($ch);
		if(curl_errno($ch)){
			var_dump(curl_error($ch));
			exit;
		}
		
		if($parmType == 'json') {
			$resultArr = json_decode($output, true);
		}
		return $resultArr;
	}
	
	/**
	 *获取微信access_token的值
	 */
	public function getAccessToken() {
		if($_SESSION['access-token'] && $_SESSION['expire_time'] > time()) {
			return $_SESSION['access-token']; 
		} else {
			$appid = $this->appid;
			$appsecret = $this->appsecret;
			$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appsecret}";
			$resultArr = $this->http_curl($url);
			$access_token = $resultArr['access_token']; 
			$_SESSION['access-token'] = $access_token;
			$_SESSION['expire_time'] = $time() + intval($resultArr['expire_time']);
			
			return $access_token;
		}
	}
	
	/**
	 *处理Post请求
	 *$postArr post请求数组
	 */
	public function accessPost($url, $postArr=array(), $isUrldecode=false) {	
		$json = urldecode(json_encode($postArr));
		$res = $this->http_curl($url, 'post', 'json', $json);
		return $res;
	}
	
	/**
	 *自定义菜单
	 *$menuArr 自定义菜单数组
	 */
	public function diyMenu($menuArr) {
		$access_token = $this->getAccessToken();
		$url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$access_token}";
		return $this->accessPost($url, $menuArr, true);
	}
	
	/**
	 *群发单文本消息
	 *$content 消息文本内容
	 *$toUsers 群发信息送达对象数组
	 */
	public function MassTextMessage($content, $toUsers=array()) {
		var messageArr = array(
			"touser:" => $toUsers,
			"msgtype" => "text",
			"text" => array(
				"content" => $content
			)
		);
		$access_token = $this->getAccessToken();
		$url = "https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token={$access_token}";
		return $this->accessPost($url, $messageArr);	
	}
	
	/**
	 *获取CODE
	 *$redirect_uri 授权后重定向的回调链接地址
	 *$scope 应用授权作用域
	 *$state 重定向后会带上state参数
	 */
	public function getCode($redirect_uri, $scope='snsapi_base', $state=1 ) {
		if($redirect_uri[0] == '/') {
			$redirect_uri = substr($redirect_uri,1);
		}
		$appid = $this->appid;
		$redirect_uri = $this->webchat_url.urlencode($redirect_uri);
		$response_type = 'code';
		$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirect_uri}&response_type={$response_type}&scope={$scope}&state={$state}#wechat_redirect";
		header('Location:'.$url, true, 301);
	}
	
	/**
	 *通过code换取网页授权access_token
	 *$code getCode()获取的code参数
	 *return  Array(access_token, expires_in, refresh_token, openid, scope)
	 */
	public function getAuthAccessTokenAndOpenId($code) {
		$grant_type = 'authorization_code';
		$appid = $this->appid;
		$appsecret = $this->appsecret;
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$appsecret}&code={$code}&grant_type={$grant_type}";
		return $this->http_curl($url);
	}
	
	/**
	 *刷新access_token（如果需要）
	 *$freshToken 填写通过access_token获取到的refresh_token参数 
	 *return array(
        "access_token"=>"网页授权接口调用凭证,注意：此access_token与基础支持的access_token不同",
        "expires_in"=>access_token接口调用凭证超时时间，单位（秒）,
        "refresh_token"=>"用户刷新access_token",
        "openid"=>"用户唯一标识",
        "scope"=>"用户授权的作用域，使用逗号（,）分隔")
	 */
	public function refreshToken($freshToken) {
		$appid = $this->appid;
		$grant_type = 'refresh_token';
		$url = "https://api.weixin.qq.com/sns/oauth2/refresh_token?appid={$appid}&grant_type={$grant_type}&refresh_token={$freshToken}";
		return $this->http_curl($url);
	}
	
	/**
	 *拉取用户信息(需scope为 snsapi_userinfo)
	 *$access_token 网页授权接口调用凭证
	 *$openid 用户唯一标识 
	 *$lang 语言
	 *return 用户的详细信息
	 */
	public function getUserInfo($access_token, $openid, $lang='zh_CN') {
		$url = "https://api.weixin.qq.com/sns/userinfo?access_token={$access_token}&openid={$openid}&lang={$lang}";
		return $this->http_curl($url);
	}
	
	/**
	 *检验授权凭证（access_token）是否有效
	 *$accessToken 网页授权接口调用凭证
	 *$openid 用户的唯一标识 
	 *return array("errcode"=>0,"errmsg"=>"ok")
	 */
	public function checkAccessToken($accessToken, $openid) {
		$url = "https://api.weixin.qq.com/sns/auth?access_token={$accessToken}&openid={$openid}";
		return $this->http_curl($url);
	}
}
?>