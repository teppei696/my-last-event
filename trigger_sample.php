<?php
	// Tokenエンドポイント
	$auth_url = "https://auth.login.yahoo.co.jp/yconnect/v1/token";

	// カスタムトリガー実行APIエンドポイント
	$url = "https://mythings-developers.yahooapis.jp/v2/services/2e768b5fff52d35ab274cb6e6721c00c/mythings/c74f3504cb222d851b8ed05e61a890fc/run";

	// アプリケーションID
	$appid = "dj0zaiZpPURVTVRhTThhaUpaSiZzPWNvbnN1bWVyc2VjcmV0Jng9NmI-";
	// シークレット
	$secret = "3888f419905d859111f2640028cdcd84e269d530";

	// アクセストークン
	$access_token = "USSDRekh5plozAbclLalWWOGGa0H3.2ALqtKx6LDfA0.3NX1V_f4JpWkR6UwiJAcjGKB060Gdr6MovyjpfD5zjMReWafNGXzcyzWUgxhaVGj3LJJElEnbs5bSM8G_AuLbqH.tSqAlkBchmDi4E9iaKsc8lminyZGVw8qq1IRtk9EHm29reyIa6Sr4Nm_SgEjd.AVLx0CPMHGUCM705MBX4Ufc1R_c.SP_QAnakpnuQu6XTay3C8JoXf_ssphOat1OLIwNLHY07ua7v7xJq.qT1XttvJ1Irs4R0Ty_fIAJaV_5QYVCP_oVnGZPVBhTItxGQAaTSvJ_Mi2.lENXyslNnAkl5rRDcxl9p3miPNKPQoqxX58ox4EybjpQHX3RdF1LRPUmp7T2Wz9FqHPlddfXBglpy4Ku8I3EEJat6lHReL7LLTMOro1ddNNWboKQbFtOLETA_d8qy9oKpP9SwRTblZCdCRNaXsyWXhPI1VCrMKE4qcgkAhVS_a7WfZgg4l7PqY8XbfhNCVcaL2xvwFWEHemmApJr1V30VCKHJ5UaDHobgi5An4J2kOt4N8QAYhDhznM4yVYwh36I9iMYeosxwl2QL8G14ztLPyR4smvvPsssRCxswlTECJZvzGWcpSop2t..qF71Prc";
	// リフレッシュトークン
	$refresh_token = "AJzlL1g5Cv7PvUN2VwDCdkUb.44xweqkJYCPy5aW_1NnE_IWFY5Njos-";

	// カスタムトリガーに設定したキー名と値を指定
	$post = array();
	$post_args = array("msg" => "msg");
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
