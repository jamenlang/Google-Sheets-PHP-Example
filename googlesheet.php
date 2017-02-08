<?php

//specify the spreadsheet from the sheet url
$spreadsheetId = '';

//specify whether to update existing cells or append
$updateonly = false;

//specify max rows
$maxrows = 10;

//specify the range in A1 notation (note D11 for later)
$range = 'A1:D' . $maxrows;

//initialise the array
$testdata = array();

//make the first row something static
$testdata[0][] = 'this will be in A1';
$testdata[0][] = 'this will be in B1';
$testdata[0][] = 'this will be in C1';
$testdata[0][] = 'this will be in D1';

$extradata = array(
	0 => "this will be in column A",
	1 => "this will be in column B",
	2 => "this will be in column C",
	3 => "this will be in column D"
);

//add additional rows ($note that $i is less than the 11 in D11)
for($i=1;$i<$maxrows;$i++){
	$testdata[$i] = $extradata;
}

//google is not expecting an array with keys, it might work with keys up to a point, but it's not reliable.
$submitdata = array_values($testdata);

if($submitdata){
	require_once __DIR__ . '/google-api-php-client/vendor/autoload.php';
	define('CREDENTIALS_PATH', __DIR__ . '/sheets.json');
	define('CLIENT_SECRET_PATH', __DIR__ . '/client_secrets.json');
	// If modifying these scopes, delete your previously saved credentials
	define('SCOPES', implode(' ', array(
	        Google_Service_Sheets::SPREADSHEETS)
	));
	define('APPLICATION_NAME', 'Sheet Test');
	/**
	 * Returns an authorized API client.
	 * @return Google_Client the authorized client object
	 */
	function getClient() {
		$client = new Google_Client();
		$client->setApplicationName(APPLICATION_NAME);
		$client->setScopes(SCOPES);
		$client->setAuthConfig(CLIENT_SECRET_PATH);
		$client->setAccessType('offline');
		// Load previously authorized credentials from a file.
		$credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
		if (file_exists($credentialsPath)) {
			$accessToken = json_decode(file_get_contents($credentialsPath), true);
		} else {
			// Request authorization from the user.
			$authUrl = $client->createAuthUrl();
			printf("Open the following link in your browser:\n%s\n", $authUrl);
			print 'Enter verification code: ';
			$authCode = trim(fgets(STDIN));
			// Exchange authorization code for an access token.
			$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
			// Store the credentials to disk.
			if(!file_exists(dirname($credentialsPath))) {
				mkdir(dirname($credentialsPath), 0700, true);
			}
			file_put_contents($credentialsPath, json_encode($accessToken));
			printf("Credentials saved to %s\n", $credentialsPath);
		}
		$client->setAccessToken($accessToken);
		// Refresh the token if it's expired.
		if ($client->isAccessTokenExpired()) {
			$client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
			file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
		}
		return $client;
	}
	/*
	 * Expands the home directory alias '~' to the full path.
	 * @param string $path the path to expand.
	 * @return string the expanded path.
	 */
	function expandHomeDirectory($path) {
		$homeDirectory = getenv('HOME');
		if (empty($homeDirectory)) {
			$homeDirectory = getenv("HOMEDRIVE") . getenv("HOMEPATH");
		}
		return str_replace('~', realpath($homeDirectory), $path);
	}
	// Get the API client and construct the service object.
	$client = getClient();
	$service = new Google_Service_Sheets($client);
	if(!$service){
		echo 'no sheet found';
		exit;
	}
	$body = new Google_Service_Sheets_ValueRange(array(
		'values' => $submitdata
	));
	$params = array(
		'valueInputOption' => 'RAW'
	);
	if($updateonly)
		$result = $service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);
	else
		$result = $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
}
