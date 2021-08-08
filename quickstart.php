<?php

/**
 * https://github.com/googleworkspace/php-samples/blob/master/gmail/quickstart/quickstart.php
 */
require __DIR__ . '/vendor/autoload.php';
if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Gmail API PHP Quickstart');
    $client->addScope(Google_Service_Gmail::GMAIL_SEND);

    $client->setAuthConfig('credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Load previously authorized token from a file, if it exists.
    // The file token.json stores the user's access and refresh tokens, and is
    // created automatically when the authorization flow completes for the first
    // time.
    $tokenPath = 'token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }
        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }
    return $client;
}


try {
    // Get the API client and construct the service object.
    $client = getClient();
    $service = new Google_Service_Gmail($client);

    //The special value **me** can be used to indicate the authenticated user.
    $user = 'me';

    $subject = "Test mail using GMail API";
    $rawMessage = "From: Sender Name <senderemail@gmail.com>\r\n";
    $rawMessage .= "To: Receiver Name <receiveremail@gmail.com>\r\n";
    $rawMessage .= "Subject: =?utf-8?B?" . base64_encode($subject) . "?=\r\n";
    $rawMessage .= "MIME-Version: 1.0\r\n";
    $rawMessage .= "Content-Type: text/html; charset=utf-8\r\n";
    $rawMessage .= "Content-Transfer-Encoding: quoted-printable" . "\r\n\r\n";
    $rawMessage .= "You got this!";

    // The message needs to be encoded in Base64URL
    $mime = rtrim(strtr(base64_encode($strRawMessage), '+/', '-_'), '=');

    $msg = new Google_Service_Gmail_Message();
    $msg->setRaw($mime);
    $service->users_messages->send("me", $msg);
    
} catch (\Throwable $th) {
    echo '<pre>';
    echo print_r($th);
    echo '</pre';
}
