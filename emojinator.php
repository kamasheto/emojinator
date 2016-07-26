<?php

// Idea inspiration: Late evening talks @ moovel
// Code inspiration: https://github.com/smashwilson/slack-emojinator

require_once 'config.php';
define(COOKIES, sprintf('a=%s;a-%s=%s', A, A, B));

// Get emojis
$team_url = 'https://'.SITE.'.slack.com/stats';
$response = get($team_url);
$body = preg_replace('/\s+/', ' ', $response['content']);
// By only taking avatars.slack imgs, we're ignoring gravatars (and default slack icons)
preg_match_all('/<tr.*?>.*?(https?:\/\/avatars.slack[^\'"]*).*?(@[\w.]+).*?<\/tr>/', $body, $matches);

if (! count($matches[0])) {
	echo "Found nobody. Your cookies are probably wrongish.\n";
	die;
}

$users = $argv;
array_shift($users);

foreach ($matches[2] as $k => $username) {
	if (!process_user($username))
		continue;

	echo 'Downloading '.$username.'\'s avatar... ';
	$username = str_replace('@', '-', $username);
	$img = $matches[1][$k];
	$info = pathinfo($img);
	file_put_contents('emojis/'.$username.'.'.$info['extension'], fopen($img, 'r'));
	echo "done\n";
}

// POST emojis
$emojis_url = 'https://'.SITE.'.slack.com/customize/emoji';
$response = get($emojis_url);
preg_match('/<input.*name=([\'\"])crumb\1.*value=([\'\"])(.*)\2.*\/>/', $response['content'], $matches);

foreach (scandir('emojis') as $file) {
	if (in_array($file, array('.', '..', '.keep', '.DS_Store')))
		continue;

	$info = pathinfo($file);

	if (!process_user($info['filename'])) {
		continue;
	}

	echo "Uploading ".$file."... ";
	$file = __DIR__ . '/emojis/' . $file;

	post($emojis_url, array(
		'add' => 1,
		'crumb' => $matches[3],
		'name' => $info['filename'],
		'mode' => 'data',
		'img' => "@$file"
	));
	echo "done\n";
}

function process_user($user) {
	global $users;

	return count($users) > 0 ? in_array(substr($user, 1), $users) : true;
}

// Source: http://stackoverflow.com/a/21943596
function get($url, $post_data = false) {
	$options = array(
		CURLOPT_RETURNTRANSFER => true,     // return web page
		CURLOPT_HEADER         => true,     //return headers in addition to content
		CURLOPT_FOLLOWLOCATION => true,     // follow redirects
		CURLOPT_ENCODING       => "",       // handle all encodings
		CURLOPT_AUTOREFERER    => true,     // set referer on redirect
		CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
		CURLOPT_TIMEOUT        => 120,      // timeout on response
		CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
		CURLINFO_HEADER_OUT    => true,
		CURLOPT_SSL_VERIFYPEER => false,     // Disabled SSL Cert checks
		CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
		CURLOPT_COOKIE         => COOKIES
	);

	if ($post_data) {
		$options += array(
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $post_data
		);
	}
	$ch      = curl_init( $url );
	curl_setopt_array( $ch, $options );
	$rough_content = curl_exec( $ch );
	$err     = curl_errno( $ch );
	$errmsg  = curl_error( $ch );
	$header  = curl_getinfo( $ch );
	curl_close( $ch );

	$header_content = substr($rough_content, 0, $header['header_size']);
	$body_content = trim(str_replace($header_content, '', $rough_content));
	$pattern = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m"; 
	preg_match_all($pattern, $header_content, $matches); 
	$cookiesOut = implode("; ", $matches['cookie']);

	$header['errno']   = $err;
	$header['errmsg']  = $errmsg;
	$header['headers']  = $header_content;
	$header['content'] = $body_content;
	$header['cookies'] = $cookiesOut;

	return $header;
}

// Source: meself
function post($url, $data) {
	get($url, $data);
}