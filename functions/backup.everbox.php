<?php

/**
 * Save file to Everbox
 * @param string $path
 */
function hmbkp_save_to_everbox( $filepath ) {
  session_start();
  $sdid =  $_SESSION['sdid'];
  $token = $_SESSION['snda_token'];
  $token_sector = preg_split('~[|.-]~', $token['access_token']);
  $time = time();
  if ($time > intval($token_sector[4])){
    $token = null;
    echo 'time invalid';
    return;
  }
  $config = include dirname(__FILE__).'/../everbox/apipool.config.hp';
  $snda_oauth = new SNDAOAuthHTTPClient($config);
  $snda_oauth->setAccessToken($token);
  $client = new SNDAEverboxClient($snda_oauth, $config);
  try{
    $client->mkdir('/home/wpbackup');
  }catch (EverboxClientException $e){
    //not dir already exist
    if ( $e->getCode() != '-50000409'){
      echo $e->getInfo();
    }
  }
  try{
    $filename = basename($filepath);
    if(strpos($filename) === 0){
      $filename = substr($filename,1);
    }
    $client->put($filepath,'/home/wpbackup/'.$filename);
  }catch (EverboxClientException $e) {
    echo $e->getInfo();
  }
}

/**
 * Ensure that there is a folder named 'wpbackup' in the root folder of everbox.
 */
function hmbkp_conform_everbox_dir( $snda_oauth ) {
  $snda_oauth->setParam('method','sdo.everbox.fs.mkdir');
  $snda_oauth->setParam('path','/home/wpbackup');
  $result = $snda_oauth->request('GET');
  //if the error is not dir already exist
  if ($snda_oauth->getLastErrCode() &&
    $snda_oauth->getLastErrorCode() != '-50000409') {
    echo 'Error Code:', $snda_oauth->getLastErrCode(), '<br />';
    echo 'Error Msg:', $snda_oauth->getLastErrMsg(), '<br />';
    echo 'failed';
    return False;
  } 
  return True;
}

/**
 * Redirect to OAuth service
 */
function hmbkp_request_everbox_token( $encode_filepath ) {
        $config = include dirname(__FILE__).'/../everbox/apipool.config.hp';
        $snda_oauth = new SNDAOAuthHTTPClient($config);
        $oauth_url = $snda_oauth->getAuthorizeURL();
        $protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === false ? 'http://' : 'https://';
        $host = $_SERVER['HTTP_HOST'];
        $port = $_SERVER['SERVER_PORT'] == '80' ? "" : ":".$_SERVER['SERVER_PORT'];
        $uri = $_SERVER['REQUEST_URI'];
        $url = $protocol.$host.$uri."&hmbkp_save_to_everbox=".$encode_filepath;
        $url = str_replace("&","__and__",$url);
        $url = str_replace("=","__equal__",$url);
	wp_redirect( $oauth_url."&state=".$url );
	exit;
}
?>
