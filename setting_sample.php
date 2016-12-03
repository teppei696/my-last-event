<?php
	// Tokenエンドポイント
	$auth_url = "https://auth.login.yahoo.co.jp/yconnect/v1/token";

	// ユーザー設定画面URL取得APIエンドポイント
	$url = "https://mythings-developers.yahooapis.jp/v2/services/2e768b5fff52d35ab274cb6e6721c00c/mythings/53317db53ec72618ddf8cc8b9ccb2652/url";

	// アプリケーションID
	$appid = "dj0zaiZpPURVTVRhTThhaUpaSiZzPWNvbnN1bWVyc2VjcmV0Jng9NmI-";
	// シークレット
	$secret = "3888f419905d859111f2640028cdcd84e269d530";

	// アクセストークン
	$access_token = ".WAQ6hwFiqCTgnJMbjOuYI_nCjMuyHST44Vbmy56N1_GlbAr8hjJ8zYOFrilqcjBBXWtHCl17y1Cih1D7PcKuLMNUsrVAecTCeqMmEvzCwd9qmv65BypHf1RhLL2TLQfhzPmG7E7JIYS1uxJFts24qKU95Ih1HpICskyCZauGz4XUSHtZ0uudl0qYvihqGg8LmpJ1y1HAfsNX.p_mTU5gu4bMe.KxkPrApDOMrp9yofD.Exfk6X8FUuU7KbCtJ_iN8ktC7COFybY5SNut2UloOGNQHCXTZC3XSuVQuwXQxGa9e.BsOXs.Knp5cHbgTjIPKRqiMcbawdr0PdWwlZwpEZ12p2Vw1s4DmhWrCgXk5m1iKAFLw0GQOkvRGjaxHUqGZfPLloSIVcqcha4SMFYahpb_FjkTVTHjsidwM5EGRhJ9oTBCOHC9pa_CPcvpSGIhdUUizOckuxw7c1_K2TqLIvVBCZxDIXJmMc5U.Ub1elCd9fjskkZjbs4ptZU82Up9DLbyo4uAvu6BfB7Z23p0p9A1u9lzHA45.WFkMDT5Sbs5fSchhuo4TibDfRZsKBTxB6VCyYKYdzbFQTbA_IQAx92jvSl4NJmCvy27_uaP67Ai0VlHuf723zRcuERhpWfVI0Iytcto.qd";
	// リフレッシュトークン
	$refresh_token = "AJzlL1g5Cv7PvUN2VwDCdkUb.44xweqkJYCPy5aW_1NnE_IWFY5Njos-";


	// ユーザー設定画面URL取得
	$ret = request_with_token($url , $access_token);
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

		// ユーザー設定画面URL取得
		$ret = request_with_token($url , $access_token);
	}

	// レスポンス(JSON)をデコード
	$result = $ret["result"];
	$dec = json_decode($result, true);

	// レスポンスからURLを取得
	if(!empty($dec) && isset($dec["url"])){
		$url = $dec["url"];
		header("Location: ".$url);
	} else {
		echo "ユーザー設定画面URLの取得に失敗しました。";
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
