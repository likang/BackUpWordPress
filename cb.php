<?php 
require_once 'everbox/EverboxClient.php';
require_once 'everbox/SNDAOAuthHTTPClient.php';
$config = include 'everbox/apipool.config.php';
$http = new SNDAOAuthHTTPClient($config);
$code = isset($_GET["code"]) ? trim($_GET['code']) : "";
try {
  $token = $http->fetchAccessToken($code);
  $sdid = $token['sdid'];
  //echo 'sdid:'.$sdid."<br>";
  $access_token = $token['access_token'];
  //echo 'access_token:'.$access_token."<br>";
  $state =$_GET['state'];
  $state = str_replace("__equal__","=",$state);
  $state = str_replace("__and__","&",$state);
  $url = $state."&sdid=".$sdid."&snda_token=".$access_token;

  //echo 'state:'.$state."<br>";
  Header("HTTP/1.1 303 get token success");
  Header("Location: $url");
  exit;
}catch (EverboxClientException $e){
  echo $e->getMessage();
}
?>
