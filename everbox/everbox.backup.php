<?php

/**
 * Save file to Everbox
 * @param string $path
 */
function hmbkp_save_to_everbox( $file_path ) {
  hmbkp_confirm_everbox_dir();
  $file = @fopen($file_path,'r');
  $keys = hmbkp_calc_file_keys($file);
  $stat = fstat($file);
  $file_size = $stat['size'];
  $config = include dirname(__FILE__).'/everbox.config.php';
  $path = '/home/'.$config['backup_folder'].'/'.hmbkp_get_upload_file_name($file_path);


  $result = hmbkp_everbox_upload_file_common('get_file_chunks_url', $keys, $file_size, $path, $config );
  $urls = explode("\n",trim($result));
  for ($i = 0; $i < count ($urls); $i++){
    hmbkp_put_file_chunk_to_everbox($file, $urls[$i], $file_size, $i, $config);
  }
  hmbkp_everbox_upload_file_common('commit_put', $keys, $file_size, $path, $config );
  fclose($file);
}

/**
 * Get file chunks upload url of everbox.
 */
function hmbkp_everbox_upload_file_common( $action, $keys, $file_size, $path, $config ) {
  $data = "";
  foreach ($keys as $key){
    $data = $data.'keys[]='.$key.'&';
  }

  $action_url = $config['action_url'].'?action='.$action.'&';
  $action_url = $action_url.hmbkp_build_token_url();
  $action_url = $action_url.'&file_size='.$file_size;
  $action_url = $action_url.'&chunk_size='.$config['chunk_size'];
  $action_url = $action_url.'&path='.hmbkp_urlsafe_base64_encode( $path );

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
function hmbkp_confirm_everbox_dir(  ) {
  $config = include dirname(__FILE__).'/everbox.config.php';
  $action_url = $config['action_url'].'?action=confirm_dir&folder='.$config['backup_folder'].'&';
  $action_url .= hmbkp_build_token_url();
  
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $action_url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $data = curl_exec($ch);
  curl_close($ch);
  return $data;
}

$everbox_bytes_sent = 0;
$everbox_file_size = 0;
function hmbkp_put_file_chunk_to_everbox($file, $url, $file_size, $chunkID , $config) {
  $chunk_size = $config['chunk_size'];
	$pos = $chunk_size * $chunkID;
	fseek($file, $pos);
	$size = ($file_size - $pos) > $chunk_size ? $chunk_size : ($file_size - $pos);
	$size = max($size, 0);

	$header = array('Content-Type: application/octet-stream');

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_PUT, true);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_INFILE, $file);
	curl_setopt($ch, CURLOPT_INFILESIZE, $size);
	curl_setopt($ch, CURLOPT_TIMEOUT, $config['io_timeout']);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $config['io_connect_timeout']);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	$bytes_sent = 0;
        global $everbox_bytes_sent,$everbox_file_size;
	$everbox_bytes_sent = $bytes_sent;
	$everbox_file_size = $size;

	curl_setopt($ch, CURLOPT_READFUNCTION, 'hmbkp_curl_read_function');
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	$response = curl_exec($ch);
	if (!$response) {
		$err = 'put file to io failed: '.curl_error($ch);
	}
	curl_close($ch);
	if (!$response) {
		throw new EverboxClientException(EverboxClient::STATUS_IO_ERROR, $err);
	}
	preg_match('~^HTTP/1\.[01] +(?!100)(\d{3})\s+(.+)~m', $response, $match);
	return array($match[1]=>$match[2]);
}

function hmbkp_curl_read_function($ch, $in, $to_read) {
    global $everbox_bytes_sent,$everbox_file_size;
    $ret_size = $everbox_bytes_sent + $to_read < $everbox_file_size ? $to_read : $everbox_file_size - $everbox_bytes_sent;
    $everbox_bytes_sent+= $ret_size;
    return $ret_size > 0 ? fread($in, $ret_size) : '';
}

function hmbkp_redirect_to_everbox_for_wp() {
  $config = include dirname(__FILE__).'/everbox.config.php';
  $index_url = $config['index_url'];
  $url = admin_url().'tools.php?page=' . HMBKP_PLUGIN_SLUG;
  
  wp_redirect( $index_url.'?back='.$url.'&backup_folder='.$config['backup_folder'].'&'.hmbkp_build_token_url() );
  exit;
}

/**
 * Redirect to OAuth service
 */
function hmbkp_request_everbox_token() {
        $config = include dirname(__FILE__).'/everbox.config.php';
        $action_url = $config['action_url'];
        $url = admin_url().'tools.php?page='.HMBKP_PLUGIN_SLUG;

	wp_redirect( $action_url.'?action=request_token&callback='.$url);
	exit;
}

function hmbkp_build_token_url(){
  session_start();
  return 'sdid='.$_SESSION['sdid'].'&access_token='.$_SESSION['snda_access_token'].'&expires_in='.$_SESSION['snda_expires_in'];
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
