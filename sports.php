<?php

// --- Configuration ---
// You can change these values
$category = 'football'; // e.g., 'football', 'basketball', 'tennis'
$date = date('Y-m-d');  // Gets today's date in YYYY-MM-DD format, e.g., 2025-09-19

// --- API Request ---

// 1. Construct the full API URL
$apiUrl = "https://www.sofascore.com/api/v1/sport/{$category}/scheduled-events/{$date}";

// 2. Define the necessary request headers
// The 'X-Requested-With' header seems important for this specific API.
$headers = [
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36',
    'Accept: */*',
    'X-Requested-With: 077dd6',
    'DNT: 1'
];

// 3. Initialize a cURL session
$ch = curl_init();

// 4. Set cURL options
curl_setopt($ch, CURLOPT_URL, $apiUrl); // Set the URL
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // Set the custom headers
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Set a 30-second timeout

// 5. Execute the cURL request
echo "Fetching data from: {$apiUrl}\n\n";
$response = curl_exec($ch);

// 6. Check for cURL errors
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
    exit;
}

// 7. Close the cURL session
curl_close($ch);

// 8. Decode the JSON response
// The API returns data in JSON format. We decode it into a PHP associative array.
$data = json_decode($response, true);

// 9. Check if JSON was decoded successfully
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Error decoding JSON: " . json_last_error_msg();
    // You might want to print the raw response to see what you received
    // echo "\nRaw Response:\n" . $response;
    exit;
}

// 10. Display the result
// The data is now in the $data variable. We can print it to see the structure.
// Using <pre> tags makes the output readable in a web browser.
header('Content-Type: text/plain'); // Set header to display as plain text
print_r($data);

?>
