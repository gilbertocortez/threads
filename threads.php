<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facebook Login</title>
    <script>
        // Replace these values with your actual app details
        const appId = "APP_ID"; // Threads/Facebook App ID
        const redirectUri = encodeURIComponent("REDIRECT_URL"); // Redirect URI
        const scope = encodeURIComponent("threads_basic", "threads_content_publish"); // Required permissions
        const state = "custom_state"; // Optional state parameter for CSRF protection
        
        // Construct the authorization URL
        const log_in = `https://threads.net/oauth/authorize
			?client_id=${appId}
			&redirect_uri=${redirectUri}
			&scope=${scope}
			&response_type=code
			&state=${state}`;

        // Function to open the authorization URL
        function openLoginWindow() {
            window.open(log_in, "_self", "width=500,height=600");
        }
    </script>
</head>

<body>
    <!-- Login Button -->
    <?php
    session_start();
    // ------------------------------------------------------------------------------------------------------------------------
    // User is logged in
    // ------------------------------------------------------------------------------------------------------------------------
    if ($_SESSION['threads_access_token']) {
        // Print access token information
        // echo '<pre>' . print_r($_SESSION['threads_access_token'], true) . '</pre>';
        // echo '<pre>' . print_r($_SESSION['threads_access_token_type'], true) . '</pre>';
        // echo '<pre>' . print_r($_SESSION['threads_access_token_expiration'], true) . '</pre>';

        // --------------------------------------------------------------------------------------------------------------------
        // Obtain user basic information via cURL
        // --------------------------------------------------------------------------------------------------------------------
        // Define variables
        $api_url = "https://graph.threads.net/v1.0/me";
        $access_token = $_SESSION['threads_access_token']; // Replace with your access token
        $fields = "id,username,name,threads_profile_picture_url,threads_biography"; // Fields to retrieve

        // Build the query parameters
        $query_params = [
            'fields' => $fields,
            'access_token' => $access_token,
        ];

        // Initialize cURL
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $api_url . '?' . http_build_query($query_params)); // Add query parameters to URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as a string
        curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in the output

        // Execute the cURL request
        $response = curl_exec($ch);

        // Check for cURL errors
        if (curl_errno($ch)) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            // Separate headers and body
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headers = substr($response, 0, $header_size);
            $body = substr($response, $header_size);

            // Decode JSON
            $response_data = json_decode($body);

            // Check for decoding errors
            if (json_last_error() === JSON_ERROR_NONE) {
                // Successfully decoded
                echo "ID: " . $response_data->id . "\n<br/>";
                echo "Username: " . $response_data->username . "\n<br/>";
                echo "Name: " . $response_data->name . "\n<br/>";
                echo "Profile Picture URL: " . $response_data->threads_profile_picture_url . "\n<br/>";
                echo "Biography: " . $response_data->threads_biography . "\n<br/>";
            } else {
                // Handle JSON decoding error
                echo "Error decoding JSON: " . json_last_error_msg();
            }
        }

        // Close cURL
        curl_close($ch);

        // --------------------------------------------------------------------------------------------------------------------
        // HTML Front End when logged in
        // --------------------------------------------------------------------------------------------------------------------
    ?>
        <button onclick="openLoginWindow()" disabled>Already logged with Threads/Facebook</button>
    <?php
    }
    // ------------------------------------------------------------------------------------------------------------------------
    // User is not logged in
    // ------------------------------------------------------------------------------------------------------------------------

    else {
    ?>
        <button onclick="openLoginWindow()">Login with Threads/Facebook</button>
    <?php
    }
    ?>
    <p><a href="../logout.php">Log Out</a></p>
</body>

</html>