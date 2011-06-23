<?php 
require 'snda_oauth.php';
$config = getSndaConfig();
$snda_oauth = new OauthSDK($config['appId'], $config['appSecret'], $config['redirectURI']);
$code = $_GET["code"];
$access_token = $snda_oauth->getAccessToken($code);
?>
<?php 
if (!$snda_oauth->getLastErrCode()) {
  $sdid = $access_token['sdid'];
  //echo 'sdid:'.$sdid."<br>";
  $access_token = $access_token['access_token'];
  //echo 'access_token:'.$access_token."<br>";
  $state =$_GET['state'];
  $state = str_replace("__equal__","=",$state);
  $state = str_replace("__and__","&",$state);
  $state = $state."&sdid=".$sdid."&snda_token=".$access_token;
  //echo 'state:'.$state."<br>";
  Header("HTTP/1.1 303 get token success");
  Header("Location: $state");
  exit;
}
echo 'Error Code:', $snda_oauth->getLastErrCode(), '<br />';
echo 'Error Msg:', $snda_oauth->getLastErrMsg(), '<br />';
echo 'failed';

?>
