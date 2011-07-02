<?php

/**
 * Save file to Everbox
 * @param string $path
 */
function hmbkp_save_to_everbox( $filepath ) {
  $file = fopen($filepath, 'r');
  $keys = calcKeys($file);
  $data = "";
  foreach ($keys as $key){
    $data = $data.'keys[]='.urlencode($key).'&';
  }

  $config = include dirname(__FILE__).'/../everbox.config.php';
  $action_url = $config['action_url'];
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $action_url.'?action=get_file_chunks_url&'.hmbkp_build_token_url());
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  $data = curl_exec($ch);
  curl_close($ch);
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
    echo 'filename:'.basename($filepath).'<br>';
    echo 'filepath:'.$filepath.'<br>';
    echo $e->getInfo();
    exit;
  }
}

/**
 * Redirect to OAuth service
 */
function hmbkp_request_everbox_token() {
        $config = include dirname(__FILE__).'/../everbox.config.php';
        $action_url = $config['action_url'];

        $protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === false ? 'http://' : 'https://';
        $host = $_SERVER['HTTP_HOST'];
        $port = $_SERVER['SERVER_PORT'] == '80' ? "" : ":".$_SERVER['SERVER_PORT'];
        $tmp = split('&',$_SERVER['REQUEST_URI']);
        $url = $protocol.$host.$tmp[0];

	wp_redirect( $action_url."?action=request_token&callback=".$url);
	exit;
}

function hmbkp_build_token_url(){
  session_start();
  return "sdid=".$_SESSION['sdid']."&access_token=".$_SESSION['snda_access_token']."&expires_in=".$_SESSION['snda_expires_in'];
}


function _calcKeys($fp) {
  $CHUNK_SIZE = 4194304; // 4M
  $ret = array();
  do {
    $data = fread($fp, $CHUNK_SIZE);
    if ($data === '') {
      break;
    }
    $key = urlsafeBase64Encode(sha1($data, true));
    $ret[] = $key;
  } while (!feof($fp));
  return $ret;
}

function urlsafeBase64Encode($str) {
  return strtr(base64_encode($str), '+/', '-_');
}

function urlsafeBase64Decode($str) {
  return base64_decode(strtr($str, '-_', '+/'));
}


?>
