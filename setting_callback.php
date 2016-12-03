<html>
<head>
<meta charset="utf-8">
</head>
<body>
<?php
	// callback時に渡されるuser_idパラメータでユーザー判定
	if(empty($_GET["user_id"])){
		echo "user_idが指定されてません";
		exit;
	}
?>
<div class="wrap">
<form action="customtrigger.php" method="POST">
<input type="hidden" name="user_id" value="<?php echo $_GET["user_id"] ?>">
<input type="submit" value="カスタムトリガー実行">
</form>
</div>
<style type="text/css">body{margin:0;padding:0;position:absolute;top:0;right:0;bottom:0;left:0}.wrap,body,html{width:100%;height:100%}.wrap{width:40pc;height:60px;position:absolute;top:50%;left:50%;margin-top:-30px;margin-left:-20pc}form{width:300px;float:left;margin:10px}input[type=submit]{width:100%;color:#76b729;border:solid 1px #76b729;text-align:center;display:block;font-size:14px;box-sizing:border-box;background-color:#fff;cursor:pointer;padding:0 4px;min-height:36px;line-height:36px;border-radius:6px;font-weight:700}input[type=submit]:hover{background-color:#eee;text-decoration:none}</style>
</body>
</html>
