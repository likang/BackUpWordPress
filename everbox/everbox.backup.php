<?php

/**
 * Save file to Everbox
 * @param string $path
 */
function hmbkp_save_to_everbox( $file_path ) {
  $upload_urls = hmbkp_get_file_chunks_url( $file_path );
  hmbkp_put_file_to_everbox(json_decode($file_path, $upload_urls));
  echo $upload_urls;
  exit;
}

/**
 * Get file chunks upload url of everbox.
 */
function hmbkp_get_file_chunks_url( $filepath ) {
  $file = @fopen($filepath, 'r');
  $keys = hmbkp_calc_file_keys($file);
  $stat = fstat($file);
  $file_size = $stat['size'];
  fclose($file);
  $data = "";
  foreach ($keys as $key){
    $data = $data.'keys[]='.$key.'&';
  }

  $config = include dirname(__FILE__).'/everbox.config.php';
  $action_url = $config['action_url'].'?action=get_file_chunks_url&';
  $action_url = $action_url.hmbkp_build_token_url();
  $action_url = $action_url.'&file_size='.$file_size;
  $action_url = $action_url.'&chunk_size='.$config['chunk_size'];
  $file_name = hmbkp_get_upload_file_name($filepath);
  $action_url = $action_url.'&path='.hmbkp_urlsafe_base64_encode('/home/'.$file_name);

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $action_url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  $data = curl_exec($ch);
  curl_close($ch);
  return $data;
}

function hmbkp_get_upload_file_name($file_path){
  $file_name = basename($file_path);
  if(strpos($file_name,'.') === 0 ) {
    $file_name = substr($file_name,1);
  }
  return $file_name;
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
function hmbkp_put_file_to_everbox($file_path, $upload_urls ) {
  for ($i = 0; $i < count($upload_urls); $i ++) {
  }
}

/**
 * Redirect to OAuth service
 */
function hmbkp_request_everbox_token() {
        $config = include dirname(__FILE__).'/everbox.config.php';
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


function hmbkp_calc_file_keys($fp) {
  $config = include dirname(__FILE__).'/everbox.config.php';
  $ret = array();
  do {
    $data = fread($fp, $config['chunk_size']);
    if ($data === '') {
      break;
    }
    $key = hmbkp_urlsafe_base64_encode(sha1($data, true));
    $ret[] = $key;
  } while (!feof($fp));
  return $ret;
}

?>
