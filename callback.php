<?php
	require("vendor/autoload.php");
	use YConnect\Credential\ClientCredential;
	use YConnect\YConnectClient;

  $size = "1";
  $iv = "2";
  $enc_token = "3";
  $base64_token = "4";

	// アプリケーションID, シークレット
	$client_id = "dj0zaiZpPURVTVRhTThhaUpaSiZzPWNvbnN1bWVyc2VjcmV0Jng9NmI-";
	$client_secret = "3888f419905d859111f2640028cdcd84e269d530";
	// コールバックURL
	$redirect_uri  = "https://my-last-event.herokuapp.com/callback.php";

	$state="2e768b5fff52d35ab274cb6e6721c00c";
	$nonce="c74f3504cb222d851b8ed05e61a890fc";

	$cred = new ClientCredential( $client_id, $client_secret );
	$client = new YConnectClient( $cred );
	try {
		// Authorization Codeを取得
		$code_result = $client->getAuthorizationCode( $state );
		// Tokenエンドポイントにリクエスト
		$client->requestAccessToken( $redirect_uri, $code_result );
		// アクセストークン, リフレッシュトークンを取得
		$access_token  = $client->getAccessToken();
		$refresh_token = $client->getRefreshToken();
		// IDトークンを検証
		$verify_result = $client->verifyIdToken( $nonce );
		$id_token = $client->getIdToken();


		// TODO : ここから下はリリースまでにトークンの保存方法の修正が必要です。↓↓↓↓↓↓
		// アクセストークン、リフレッシュトークンの保存
		//save_token($access_token, $refresh_token, $id_token->user_id, $state);
    // アクセストークンの暗号化
		$size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
    $key = substr($id_token->user_id, 0, $size);
    $userid = $id_token->user_id;
		$iv = substr($state, 0, $size);
		exec("echo $iv > /tmp/access_token_iv_$userid");
		$enc_token = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $access_token, MCRYPT_MODE_CBC, $iv);
		$base64_token = base64_encode( $enc_token );
		// 暗号化したアクセストークンをファイルに保存（本来は外部からアクセスできない環境で保管してください）
		exec("echo $base64_token > /tmp/access_token_$userid");


		// リフレッシュトークンの暗号化
		$size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		$iv = substr($state, 0, $size);
		exec("echo $iv > /tmp/refresh_token_iv_$userid");
		$enc_token = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $refresh_token, MCRYPT_MODE_CBC, $iv)	;
		$base64_token = base64_encode( $enc_token );
		// 暗号化したリフレッシュトークンをファイルに保存（本来は外部からアクセスできない環境で保管してください）
		exec("echo $base64_token > /tmp/refresh_token_$userid");
		// TODO : ここから上はリリースまでにトークンの保存方法の修正が必要です。↑↑↑↑↑

	} catch ( TokenException $e ) {
		// 再度ログインして認可コードを発行してください
	}


	// TODO : ここから下はリリースまでにトークンの保存方法の修正が必要です。↓↓↓↓↓↓
	/*
	 * アクセストークン、リフレッシュの保存
	 *
	 * アクセストークン($access_token)、リフレッシュトークン($refresh_token)は安全な場所に保管してください。
	 * ここでは仮実装として、mcrypt関数で暗号化してファイルに保存しています。
	 * 動作確認が完了したらファイルに保存したアクセストークン、リフレッシュトークンは必ず削除してください。
	 * アクセストークン、リフレッシュトークンを保存したファイルは/tmpに配置されます。
	 *
	 * @param string $access_token アクセストークン
	 * @param string $refresh_token リフレッシュトークン
	 * @param string $key ユーザーを一意に判定できるキー
	 * @param string $state ランダムな文字列
	 *
	 */
	function save_token($access_token, $refresh_token, $key, $state)
	{
		// アクセストークンの暗号化
		$size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		$iv = substr($state, 0, $size);
		exec("echo $iv > /tmp/access_token_iv_$key");
		$enc_token = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $access_token, MCRYPT_MODE_CBC, $iv);
		$base64_token = base64_encode( $enc_token );
		// 暗号化したアクセストークンをファイルに保存（本来は外部からアクセスできない環境で保管してください）
		exec("echo $base64_token > /tmp/access_token_$key");


		// リフレッシュトークンの暗号化
		$size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		$iv = substr($state, 0, $size);
		exec("echo $iv > /tmp/refresh_token_iv_$key");
		$enc_token = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $refresh_token, MCRYPT_MODE_CBC, $iv)	;
		$base64_token = base64_encode( $enc_token );
		// 暗号化したリフレッシュトークンをファイルに保存（本来は外部からアクセスできない環境で保管してください）
		exec("echo $base64_token > /tmp/refresh_token_$key");
	}

	// TODO : ここから上はリリースまでにトークンの保存方法の修正が必要です。↑↑↑↑↑
?>

<html>
<head>
<meta charset="utf-8">
</head>
<body>
<div>$access_token : <?php echo $access_token ?></div>
<pre>
$key : <?php echo $key ?><br>
$size : <?php echo $size ?><br>
$iv : <?php echo $iv ?><br>
$enc_token : <?php echo $enc_token ?><br>
$base64_token : <?php echo $base64_token ?><br<
</pre>
<div class="wrap">
<!-- 「Step.2 ユーザー設定画面のURL取得処理」を呼び出す -->
<form action="geturl.php" method="POST">
<input type="hidden" name="user_id" value="<?php echo $id_token->user_id ?>">
<input type="submit" value="ユーザー設定">
</form>
</div>
<style type="text/css">body{margin:0;padding:0;position:absolute;top:0;right:0;bottom:0;left:0}.wrap,body,html{width:100%;height:100%}.wrap{width:40pc;height:60px;position:absolute;top:50%;left:50%;margin-top:-30px;margin-left:-20pc}form{width:300px;float:left;margin:10px}input[type=submit]{width:100%;color:#76b729;border:solid 1px #76b729;text-align:center;display:block;font-size:14px;box-sizing:border-box;background-color:#fff;cursor:pointer;padding:0 4px;min-height:36px;line-height:36px;border-radius:6px;font-weight:700}input[type=submit]:hover{background-color:#eee;text-decoration:none}</style>
</body>
</html>
