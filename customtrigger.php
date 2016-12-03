<html>
<head>
<meta charset="utf-8">
</head>
<body>
<?php
	require("vendor/autoload.php");
	use YConnect\Credential\ClientCredential;
	use YConnect\YConnectClient;

	// カスタムトリガー実行APIエンドポイント
	$url = "https://mythings-developers.yahooapis.jp/v2/services/2e768b5fff52d35ab274cb6e6721c00c/mythings/c74f3504cb222d851b8ed05e61a890fc/run";

	// アプリケーションID
	$appid = "dj0zaiZpPURVTVRhTThhaUpaSiZzPWNvbnN1bWVyc2VjcmV0Jng9NmI-";
	// シークレット
	$secret = "3888f419905d859111f2640028cdcd84e269d530";

	// カスタムトリガーに設定したキー名と値を指定
	$post = array();
	$post_args = array("msg" => "msg");
	$post["entry"] = json_encode($post_args);

	// アクセスしたユーザーのアクセストークン取得
	$user_id = $_POST["user_id"];
	$access_token = decrypt_token($user_id, 'access_token');

	// カスタムトリガーの実行
	$ret = request_with_token($url , $access_token, $post);
	$code = $ret["code"];

	// 401認証エラーが発生した場合はアクセストークンの有効期限が切れている
	if($code === 401){
		// アクセストークンの更新
		$access_token = refresh_access_token($appid, $secret, $user_id);

		// カスタムトリガーの実行
		$ret = request_with_token($url , $access_token, $post);
	}

	$result = $ret["result"];

	if(empty($result) || !$result["flag"]){
		echo "カスタムトリガーの実行に失敗しました。";
		echo "原因は以下の５つの可能性があります。<br>";
		echo "・ユーザー設定が完了していない可能性があります。ステップ２で実装したgeturl.phpにアクセスし、ユーザー設定画面に「利用停止」ボタンが表示されているかを確認してください。 <br>";
		echo "・テストユーザーに登録されていないユーザーでログインしている可能性があります。ログインしているユーザーをmyThings Developersからテストユーザーを設定してください。<br>";
		echo "・IP登録したサーバー以外からアクセスしている可能性があります。サーバーのIPとmyThings Developersに登録しているIPを確認してください。（未設定の場合は関係ありません。）<br>";
		echo "・URLが間違っている可能性があります。サンプルコードの".'$url'."とmyThings Developersに表示されているAPIエントリーポイントが一致している事を確認してください<br>";
		echo "・アクセストークンが正しく取得出来ていない可能性があります。アクセストークンの保存(callback.php)、および、取得を確認してください。<br>";
	} else {
		echo "カスタムトリガーの実行リクエストを受け付けました。";
	}

	/*
	 * APIエンドポイントへのリクエスト
	 *
	 * トークンをヘッダーに設定してAPIエンドポイントへリクエストする
	 *
	 * @param string $url APIエンドポイント
	 * @param string $access_token アクセストークン
	 * @param array $post POSTデータ
	 *
	 * @return array $ret APIの戻り値とHTTPコード
	 */
	function request_with_token($url , $access_token, $post=array())
	{
		// HTTPヘッダーにアクセストークンを設定
		$http_header = array(
			"Content-Type: application/x-www-form-urlencoded; charset=utf-8",
			"Authorization: Bearer " . $access_token,
		);

		// curlのオプション設定
		$curl_setopt_array = array(
			CURLOPT_URL             => $url,
			CURLOPT_HTTPHEADER      => $http_header,
			CURLOPT_RETURNTRANSFER  => true,
			CURLOPT_FAILONERROR     => true,
			CURLOPT_TIMEOUT         => 10,
		);

		// POSTデータの設定
		if(!empty($post)){
			$curl_setopt_array[CURLOPT_POST] = true;
			$curl_setopt_array[CURLOPT_POSTFIELDS] = http_build_query($post);
		}

		// curlでAPIエントリーポイントへリクエストを実行
		$ch = curl_init();
		curl_setopt_array($ch, $curl_setopt_array);
		$result = curl_exec($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$ret = array(
			"result" => $result,
			"code" => $code,
			);

		return $ret;
	}

	/*
	 * アクセストークンの更新
	 *
	 * リフレッシュトークンを元にアクセストークンを更新する
	 *
	 * @param string $appid アプリケーションID
	 * @param string $secret シークレット
	 * @param string $user_id ユーザー識別子
	 *
	 * @return string $access_token アクセストークン
	 */
	function refresh_access_token($appid, $secret, $user_id)
	{
		$cred = new ClientCredential( $appid, $secret );
		$client = new YConnectClient( $cred );
		$access_token = '';
		try {
			// リフレッシュトークンの復号化
			$refresh_token = decrypt_token($user_id, 'refresh_token');
			// Tokenエンドポイントにリクエストしてアクセストークンを更新
			$client->refreshAccessToken( $refresh_token );
			$access_token = $client->getAccessToken();
		} catch ( YConnect\Exception\TokenException $te ) {
			// リフレッシュトークンが有効期限切れであるかチェック
			if( $te->invalidGrant() ) {
				// index.phpへアクセスし、認証から実施してください
				$index_url = (empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"] . "/index.php";
				header("Location: ".$index_url);
			}
		}

		return $access_token;
	}

	/*
	 * トークンの復号化
	 *
	 * トークンを復号化する
	 *
	 * @param string $user_id ユーザー識別子
	 * @param string $kind トークンの種別('access_token' or 'refresh_token')
	 *
	 * @return string $token 復号化したトークン
	 */
	function decrypt_token($user_id, $kind)
	{
		// アクセストークンの復号化
    $key = substr($user_id, 0, 16);
		$iv = exec("cat /tmp/".$kind."_iv_".$user_id);
		$base64_token = exec("cat /tmp/".$kind."_".$user_id);
		$enc_token = base64_decode( $base64_token );
		$dec_token = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $enc_token, MCRYPT_MODE_CBC, $iv);
		$token = rtrim($dec_token);

		return $token;
	}
?>
</body>
</html>
