<?php
const APPID          = "00008"; //机构号
const APPPUBLICKEY   = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDBcsLvYw3tYEDZoWAvOvvBztZmHNZFWCUuF4BQSqESmaFx3P+5MKLqUyg1S7DStZGIem2Aqw85fM7qWaHdN8D2gMaDCPhDjSgCYbMY45FPQn+791uI6kqHZLOlm1uNGjfCJM5urEyvW8FB4VUBgSCB65F3TNDcCxRpAsQTibFO9wIDAQAB";
const APPPPRIVATEKEY = "MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBAMFywu9jDe1gQNmhYC86+8HO1mYc1kVYJS4XgFBKoRKZoXHc/7kwoupTKDVLsNK1kYh6bYCrDzl8zupZod03wPaAxoMI+EONKAJhsxjjkU9Cf7v3W4jqSodks6WbW40aN8Ikzm6sTK9bwUHhVQGBIIHrkXdM0NwLFGkCxBOJsU73AgMBAAECgYEAuUEWrztz9fQshn3FZ9WbjTFwHp/VDtln2akF515gvDhF5I3Yk1ehXLfAFLHkpxTcRw+/V+35CXudh83IddpTvdImg5WcjefF6Xq4UOiMipUOVnxPBNE7e/T751HrJiPFPbs0lODrSkDJfTpraNZWtXa2DSJVYkICHlYOHNdERukCQQDqq8EpsckK5KIjYOQYNaSLnsyKabcbJZF3OU5lpjvlb6LiaXtpBC2W05TbknX7Z+OMQW36eHu5tDYbH5SVQjsLAkEA0wfaNfos4GolPyypLhiEtEGWlk2i9xzpfjh328swOmJ57tXfO6m8jQkP3lNzATSfxQo6yBlJ3ZritztHhz1PRQJAX8U8jcGKpLFuIjYWoHKz9m8WVp6SJbgNR7TRc9aFtzr6JKCbxt33pQgwRfPdxn49dS32rjOhesY7FpKq1scyxQJBALe3GFLxbuwXvEFZQhdtJOpARzamc/8pXmSSCFaCd4P8gyZXbrXkOM+XWgc1IuOjyouhMjdNPK79ze2yX7zutPkCQQDLvpWlbTxrzCkDlNSmNkr4Y+paf0J+x1DXQfGrAMPoPgze+vLsPRKtYCmeC7mOm07ArxlXwCnEYgiiYmTdMKie";

$data = "orgNo=".APPID;

$data = "orgNo=00008&sign_type=RSA";

//echo $data."<hr/>";

$res = "-----BEGIN PUBLIC KEY-----\n" .
				wordwrap(APPPPRIVATEKEY, 64, "\n", true) .
				"\n-----END PUBLIC KEY-----";
//echo '<pre>'.$res.'</pre>'."<hr/>";
$res = "-----BEGIN RSA PRIVATE KEY-----\n" .
				wordwrap(APPPPRIVATEKEY, 64, "\n", true) .
				"\n-----END RSA PRIVATE KEY-----";

//echo '<pre>'.$res.'</pre>'."<hr/>";



openssl_private_encrypt($data, $encrypted, $res); //公钥加密
$signValue = base64_encode($encrypted);// base64传输
//echo $signValue, "<br/>";


//=====================
$private_key = file_get_contents("./test/rsa_private_key.pem");
$public_key = file_get_contents("./test/rsa_public_key.pem");

$pi_key =  openssl_pkey_get_private($private_key);// 可用返回资源id
$pu_key = openssl_pkey_get_public($public_key);

openssl_private_encrypt($data, $encrypted, $private_key); //公钥加密
$signValue = base64_encode($encrypted);// base64传输
$signValue ='dc531648afdd2c4a7fcb9e6bf87cc8c6';
echo $signValue;
?>
<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body>
<form action="https://apisandbox.xiaozhusk.com/uploadimg" method="post" enctype="multipart/form-data">
	<input type="text" value="<?php echo APPID;?>" name="orgNo">
	<input type="text" value="<?php echo $signValue;?>" name="signvalue">
    <input type="file" name="file" />
    <input type="submit" value="上传文件" />
    </form>
</body>
</html>
