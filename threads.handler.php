<?php
// Docs
// https://developers.facebook.com/docs/threads

// Remember to whitelist and add your redirect oAuth url
// https://developers.facebook.com/docs/development/create-an-app/threads-use-case#step-7--add-settings

// Remember to add user as tester if your application is in development mode
// As well as for testet to accept invite in threads
// https://developers.facebook.com/docs/threads/get-started#threads-testers

// ------------------------------------------------------------------------------------------------------------------------
// Enable error reporting
// ------------------------------------------------------------------------------------------------------------------------
error_reporting(E_ALL);          // Report all types of errors
ini_set('display_errors', 1);    // Display errors in the output
ini_set('display_startup_errors', 1);

// ------------------------------------------------------------------------------------------------------------------------
// Start server session
// ------------------------------------------------------------------------------------------------------------------------
session_start();

// ------------------------------------------------------------------------------------------------------------------------
// App details
// Be sure to use threads id and secret (not app one)
// ------------------------------------------------------------------------------------------------------------------------
$app_id = '';
$app_secret = '';
$redirect_uri = ''; // Must match the one used in the login URL

// ------------------------------------------------------------------------------------------------------------------------
// Check to see if user already has a (temporary) access token
// ------------------------------------------------------------------------------------------------------------------------
if (isset($_SESSION['threads_access_token'])) {

    // Define the parameters for cURL call
    $api_url = "https://graph.threads.net/access_token";
    $params = [
        "grant_type" => "th_exchange_token",
        "client_secret" => $app_secret,
        "access_token" => $_SESSION['threads_access_token']
    ];

    // Initialize cURL
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $api_url . '?' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

    // Execute the cURL request
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        echo "cURL Error: " . curl_error($ch);
    } else {
        // Split headers and body
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        // Response Headers
        $headers = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        // Decode JSON response body
        $response_data = json_decode($body, true);

        // Output the access token and details
        if (isset($response_data['access_token'])) {
            // Calculate expiration timestamp
            $expires_in = $response_data['expires_in'];
            $current_time = time();
            date_default_timezone_set('America/Los_Angeles');
            $expiration_time = $current_time + $expires_in;
            $expiration_date = date('Y-m-d H:i:s', $expiration_time);

            $_SESSION['threads_access_token'] = $response_data['access_token'];
            $_SESSION['threads_access_token_type'] = $response_data['token_type'];
            $_SESSION['threads_access_token_expiration'] = $expiration_date;
        } else {
            echo "<h3>Error:</h3>";
            echo "No access token found in response body.<br>";
        }
    }

    // Close cURL
    curl_close($ch);

    header("Location: ../dev/threads.php");

    exit;
}

// ------------------------------------------------------------------------------------------------------------------------
// If user does not have a (temporary) access token, then process code / data received from Threads
// ------------------------------------------------------------------------------------------------------------------------

// --------------------------------------------------------------------------------------------------
// Step 1: Check for errors or user cancellation
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
    $error_description = htmlspecialchars($_GET['error_description'] ?? 'No description provided.');
    // Handle user cancellation or OAuth errors
    die("Authorization Error: $error - $error_description");
}

// --------------------------------------------------------------------------------------------------
// Step 2: Check if the authorization code is present
if (!isset($_GET['code'])) {
    die("Authorization code not found.");
}
$authorization_code = $_GET['code'];

// --------------------------------------------------------------------------------------------------
// Step 3: Exchange the authorization code for an access token using cURL via POST
// Define the parameters for cURL call
$token_url = "https://graph.threads.net/oauth/access_token";
$fields = [
    'client_id' => $app_id,
    'redirect_uri' => $redirect_uri,
    'client_secret' => $app_secret,
    'code' => $authorization_code,
];

// cURL Call
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    die("cURL Error: " . curl_error($ch));
}

// Close cURL
curl_close($ch);

// Decode response
$response_data = json_decode($response, true);

// --------------------------------------------------------------------------------------------------
// Step 4: Handle the access token response
if (isset($response_data['access_token'])) {
    $access_token = $response_data['access_token'];
    $_SESSION['threads_access_token'] = $access_token;

    // Reload page, to now obtain permanent token (code at line 30)
    header("Location: threads.handler.php");
} elseif (isset($response_data['error'])) {
    $error_message = htmlspecialchars($response_data['error']['message'] ?? 'Unknown error.');
    die("Token Error: $error_message");
} else {
    die("Unknown response structure from token endpoint.");
}
