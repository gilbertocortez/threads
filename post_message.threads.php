<?php
session_start();
$user_data = $_SESSION['user_data'];
$threads_user_id = $user_data->id;
$access_token = $_SESSION['threads_access_token'];

// Define parameters
$media_type = 'IMAGE';
$image_url = 'https://interactiveutopia.com/images/portfolio/gctermitecontrol_desktop.jpg';
$text = 'This is a sample post using Meta Threads API';
$is_carousel_item = 'false';

// Helper function to execute cURL requests
function execute_curl($url, $method = 'GET', $post_data = null)
{
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
    ]);

    if ($method === 'POST' && $post_data) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    }

    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        echo 'cURL Error: ' . curl_error($curl);
        curl_close($curl);
        exit;
    }

    curl_close($curl);
    return $response;
}

// Step 1: Create Media Container
$url = "https://graph.threads.net/v1.0/$threads_user_id/threads";
$post_data = [
    'media_type' => $media_type,
    'image_url' => $image_url,
    'text' => $text,
    'is_carousel_item' => $is_carousel_item,
    'access_token' => $access_token
];
$response = execute_curl($url, 'POST', $post_data);
$response_data = json_decode($response, true);

if (empty($response_data['id'])) {
    echo 'Error: Media container not created';
    exit;
}

$media_container_id = $response_data['id'];

// Step 2: Publish Thread
$url = "https://graph.threads.net/v1.0/{$threads_user_id}/threads_publish?creation_id={$media_container_id}&access_token={$access_token}";
$response = execute_curl($url, 'POST');
$response_data = json_decode($response, true);

if (empty($response_data['id'])) {
    echo 'Error: Thread not published';
    exit;
}

$threads_media_id = $response_data['id'];

// Step 3: Fetch Thread Details
$url = "https://graph.threads.net/v1.0/{$threads_media_id}?fields=id,media_product_type,media_type,media_url,permalink,owner,username,text,timestamp,shortcode,thumbnail_url,children,is_quote_post&access_token={$access_token}";
$response = execute_curl($url);
$data = json_decode($response, true);

if (!$data) {
    echo "Error: Invalid JSON data";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Threads Post</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="card mx-auto my-4 shadow-sm" style="max-width: 600px;">
        <div class="card-body">
            <!-- Header Section -->
            <div class="d-flex align-items-center mb-3">
                <div>
                    <h5 class="card-title mb-1">@<?= htmlspecialchars($data['username']); ?></h5>
                    <small class="text-muted">
                        <?php
                        $datetime = new DateTime($data['timestamp'], new DateTimeZone('UTC')); // Assuming the timestamp is in UTC
                        $datetime->setTimezone(new DateTimeZone('America/Los_Angeles')); // Convert to Pacific Time
                        echo $datetime->format("F j, Y, g:i a"); // Format the date in Pacific Time
                        ?>
                    </small>

                </div>
            </div>

            <!-- Content Section -->
            <?php if ($data['media_type'] === 'IMAGE'): ?>
                <img src="<?= htmlspecialchars($data['media_url']); ?>" alt="Thread Media" class="img-fluid rounded mb-3">
            <?php endif; ?>

            <p class="card-text"><?= nl2br(htmlspecialchars($data['text'])); ?></p>

            <!-- Footer Section -->
            <a href="<?= htmlspecialchars($data['permalink']); ?>" class="btn btn-primary btn-sm" target="_blank">View on Threads</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>