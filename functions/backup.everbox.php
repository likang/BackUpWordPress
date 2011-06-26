<?php

/**
 * Save file to Everbox
 * @param string $path
 */
function hmbkp_save_to_everbox( $filepath ) {
  session_start();
  $token = $_SESSION['snda_token'];
  $token_sector = preg_split('~[|.-]~', $token['access_token']);

  $time = time();
  if ($time > intval($token_sector[4])){
    echo 'time:'.$time.'<br>';
    echo 'token:'.intval($token_sector[4]).'<br>';
    $token = null;
    exit;
  }
  $config = include dirname(__FILE__).'/../everbox/apipool.config.php';
  $snda_oauth = new SNDAOAuthHTTPClient($config);
  $snda_oauth->setAccessToken($token);
  $client = new SNDAEverboxClient($snda_oauth, $config);
  
  hmbkp_confirm_everbox_dir($client);
	hmbkp_put_file_to_everbox($client, $filepath);
}

/**
 * Ensure that there is a folder named 'wpbackup' in the root folder of everbox.
 */
function hmbkp_confirm_everbox_dir( $everbox_client ) {
  try{
    $everbox_client->mkdir('/home/wpbackup');
  }catch (EverboxClientException $e){
    //ignore dir already exist error
    if ( $e->getCode() != '409'){
      echo 'already exist<br>';
      echo 'code:'.$e->getCode().'<br>';
      echo $e->getInfo();
      exit;
    }
  }
}

/**
 * Put file to the everbox server.
 */
function hmbkp_put_file_to_everbox( $everbox_client, $filepath ) {
  //echo 'before put';
  try{
    $filename = basename($filepath);
    if(strpos($filename,'.') === 0){
      $filename = substr($filename,1);
    }
    $everbox_client->put($filepath,'/home/wpbackup/'.$filename);
  }catch (EverboxClientException $e) {
    echo 'save file error<br>';
    echo 'filepath:'.$filepath.'<br>';
    echo $e->getInfo();
    exit;
  }
}

/**
 * Redirect to OAuth service
 */
function hmbkp_request_everbox_token() {
        $config = include dirname(__FILE__).'/../everbox/apipool.config.php';
        $snda_oauth = new SNDAOAuthHTTPClient($config);
        $oauth_url = $snda_oauth->getAuthorizeURL();

        $protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === false ? 'http://' : 'https://';
        $host = $_SERVER['HTTP_HOST'];
        $port = $_SERVER['SERVER_PORT'] == '80' ? "" : ":".$_SERVER['SERVER_PORT'];
        $uri = $_SERVER['REQUEST_URI'];
        $url = $protocol.$host.$uri;

        $url = str_replace("&","__and__",$url);
        $url = str_replace("=","__equal__",$url);
	wp_redirect( $oauth_url."&state=".$url );
	exit;
}
?>
