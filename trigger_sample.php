<?php
	// Tokenエンドポイント
	$auth_url = "https://auth.login.yahoo.co.jp/yconnect/v1/token";

	// カスタムトリガー実行APIエンドポイント
	$url = "https://mythings-developers.yahooapis.jp/v2/services/2e768b5fff52d35ab274cb6e6721c00c/mythings/53317db53ec72618ddf8cc8b9ccb2652/run";

	// アプリケーションID
	$appid = "dj0zaiZpPURVTVRhTThhaUpaSiZzPWNvbnN1bWVyc2VjcmV0Jng9NmI-";
	// シークレット
	$secret = "3888f419905d859111f2640028cdcd84e269d530";

	// アクセストークン
	$access_token = "FJaLbDAB5aKSq1DdBG5wrQkttuBrqJqJWgdbUQ8eek9I7kLdczFuX3hy29iUfPdkofjca6zQvn2LKsSesUKE_XYHyD2ZwxCG9qo.7NcedNeVPYEA04_v6UBrnhMCS8fzWnk5zJOJYYML5ph_rvKmvxj2FCMVbHvNNXM_gIxUvpv3puP7Uh8A4BVtkPXVPTla0EDhBo1i6Ziwo53Vdx0FymAYXukuvj1EtHKgBjp9fNJguZwtnfZhSdQXOHHRsXbQsBVUGz8A_RygPBgYxEbb2sTwcYd3GO0C9W5Sk1KdB4x_eMt20fRU4TFX6JOp3vjWTKp_Ibmq1QOrqEogAv0fA69zJQwBdCx6wKay5bq_QZe4uERuaBh6l2riI5z4BJokmWXz9oGE6Z.HLNx9lfNyiGeH6HoFSZbcyO1irxMaDAlfxS3IrZhAU79zdvPabMZZr1rvK5JG8mLqgO5dGShtKTGm9X2d.3rptpKmXT24AXCOD7IYFhhe4vS829ek0h_4mNEJbH3qCUiw_ObFNf4_uNNW4Qz7FoluquukB1.okjvmJlxmZU0EdvGZZFt1bO9ij3yCR.qygFe1k.noxg6S6V797dAIy3Xb4PncSXyZjd86mhPouyvrN3iFEQI1.LkSLtGldXDwZc39";
	// リフレッシュトークン
	$refresh_token = "AJzlL1g5Cv7PvUN2VwDCdkUb.44xweqkJYCPy5aW_1NnE_IWFY5Njos-";

	// カスタムトリガーに設定したキー名と値を指定
	$post = array();
	$post_args = array("message" => "message");
	$post["entry"] = json_encode($post_args);

	// カスタムトリガーの実行
	$ret = request_with_token($url , $access_token, $post);
	$code = $ret["code"];

	// 401認証エラーが発生した場合はアクセストークンの有効期限が切れています
	if($code === 401){
		// アクセストークンの更新
		$ret = refresh_access_token($refresh_token, $appid, $secret, $auth_url);
		$code = $ret["code"];

		// 401認証エラーが発生した場合はリフレッシュトークンの有効期限が切れています
		if($code === 401){
			echo "リフレッシュトークンの有効期限が切れました。myThings Developersのサンプルコードからリフレッシュトークンを再取得して下さい。";
			return;
		}

		$dec = json_decode($ret["result"], true);
		$access_token = $dec["access_token"];

		// カスタムトリガーの実行
		$ret = request_with_token($url , $access_token, $post);
		$code = $ret["code"];
	}

	// レスポンス(JSON)をデコード
	$result = $ret["result"];
	$dec = json_decode($result, true);

	// レスポンスから実行結果を取得
	if(!empty($dec) && isset($dec["flag"]) && $dec["flag"]){
		echo "カスタムトリガーの実行リクエストを受け付けました。";
	} else {
		echo "カスタムトリガーの実行リクエストの受付に失敗しました。";
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
	 * @param string $refresh_token リフレッシュトークン
	 * @param string $appid アプリケーションID
	 * @param string $secret シークレット
	 * @param string $auth_url トークンAPIをエンドポイント
	 *
	 * @return array $ret APIの戻り値とHTTPコード
	 */
	function refresh_access_token($refresh_token, $appid, $secret, $auth_url)
	{
		$params = array(
			"grant_type"    => "refresh_token",
			"refresh_token" => $refresh_token,
		);
		// HTTPヘッダーにベーシック認証を設定
		$http_header = array(
			"Content-Type: application/x-www-form-urlencoded; charset=utf-8",
			"Authorization: Basic " . base64_encode($appid.":".$secret),
		);

		$curl_setopt_array = array(
			CURLOPT_URL             => $auth_url,
			CURLOPT_HTTPHEADER      => $http_header,
			CURLOPT_RETURNTRANSFER  => true,
			CURLOPT_FAILONERROR     => true,
			CURLOPT_TIMEOUT         => 10,
			CURLOPT_POST            => true,
			CURLOPT_POSTFIELDS      => http_build_query($params),
		);

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
?>
