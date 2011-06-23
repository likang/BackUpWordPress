<?php

/**
 * Save file to Everbox
 * @param string $path
 */
function hmbkp_save_to_everbox( $path ) {
  session_start();
  $sdid =  $_SESSION['sdid'];
  $token = $_SESSION['snda_token'];
  //print $path;
}

/**
 * Redirect to OAuth service
 */
function hmbkp_request_token( $encode_filepath ) {
        $config = getSndaConfig();
        $snda_oauth = new OauthSDK($config['appId'], $config['appSecret'], $config['redirectURI']);
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
