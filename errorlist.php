<html>
<head>
<meta charset="utf-8">
</head>
<body>
<?php
	require("vendor/autoload.php");
	use YConnect\Credential\ClientCredential;
	use YConnect\YConnectClient;

	// 認証切れユーザー一覧取得APIエンドポイント
	$url = "https://mythings-developers.yahooapis.jp/v2/services/2e768b5fff52d35ab274cb6e6721c00c/mythings/tokens/error";

	// アプリケーションID
	$appid = "dj0zaiZpPURVTVRhTThhaUpaSiZzPWNvbnN1bWVyc2VjcmV0Jng9NmI-";
	// シークレット
	$secret = "3888f419905d859111f2640028cdcd84e269d530";

	// パラメータの設定
	date_default_timezone_set("Asia/Tokyo");
	$param = array(
		"startIndex" => 1,                   // 開始位置(何件目から表示するかを設定)
		"startTime"  => strtotime("-1 day"), // 取得開始時間(サンプルでは１日前を指定)
		"endTime"    => time(),              // 取得終了時間(サンプルでは現在を指定)
	);
	$url .= "?" . http_build_query($param);

	// 開発者のアクセストークン取得
	$user_id = "JLLTD6DA5EWZGJAIZ6FLKVRUWE";
	$access_token = decrypt_token($user_id, 'access_token');

	// 認証切れユーザー一覧の取得
	$ret = request_with_token($url , $access_token);
	$code = $ret["code"];

	// 401認証エラーが発生した場合はアクセストークンの有効期限が切れている
	if($code === 401){
		// アクセストークンの更新
		$access_token = refresh_access_token($appid, $secret, $user_id);

		// 認証切れユーザー一覧の取得
		$ret = request_with_token($url , $access_token);
	}
	$result = $ret["result"];
	// レスポンス(JSON)をデコード
	$dec = json_decode($result, true);
	// JSON形式で画面表示
	$json = json_encode($dec, 128);

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
<textarea name="json" style="margin: 0px; height: 220px; width: 400px;"><?php echo $json; ?></textarea>
</body>
</html>
