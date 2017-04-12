<?php
namespace Home\Common;
class WeixinModel {
	/**
	 *微信公众号配置验证
	 */
    public function checkSignature() {
		$timestamp = $_GET['timestamp'];
		$nonce = $_GET['nonce'];
		$token = "hongliang861205";
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
			$appid = "wxbc4783cf51615381";
			$appsecret = "cb5b3d690af7d3f242e86b823a46215f";
			$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appsecret}";
			$resultArr = $this->http_curl($url);
			$access_token = $resultArr['access_token']; 
			$_SESSION['access-token'] = $access_token;
			$_SESSION['expire_time'] = $resultArr['expire_time'];
			
			return $access_token;
		}
	}
	
	/**
	 *自定义菜单
	 *$menuArr 自定义菜单的数组
	 */
	public function diyMenu($menuArr=array()) {
		$access_token = $this->getAccessToken();
		$url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$access_token}";		
		$json = urldecode(json_encode($menuArr));
		$res = $this->http_curl($url, 'post', 'json', $json);
		return $res;
	}
}
?>