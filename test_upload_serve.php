<?php
/**
 * Test script to check if upload serving works
 */

echo "Testing upload file serving...\n\n";

// Test the existing uploaded file
$testUrl = 'http://localhost:8000/api/uploads/profiles/img_68ebb647cd21a9.29920556.jpeg';

echo "Testing URL: $testUrl\n";

// Use curl to test the endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request only

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Content-Type: $contentType\n";

if ($httpCode === 200) {
    echo "✅ Upload serving is working!\n";
} else {
    echo "❌ Upload serving failed!\n";
    echo "Response headers:\n$response\n";
}

// Test file upload endpoint
echo "\n" . str_repeat("-", 50) . "\n";
echo "Testing upload endpoint...\n";

$uploadUrl = 'http://localhost:8000/api/upload/image';
echo "Upload URL: $uploadUrl\n";

// Create a simple test image (1x1 pixel PNG)
$testImageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChAI9jU77yQAAAABJRU5ErkJggg==');
$tempFile = tempnam(sys_get_temp_dir(), 'test_image') . '.png';
file_put_contents($tempFile, $testImageData);

// Test upload with curl
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $uploadUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'image' => new CURLFile($tempFile, 'image/png', 'test.png')
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer test-token' // You might need a real token
]);

$uploadResponse = curl_exec($ch);
$uploadHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Upload HTTP Status: $uploadHttpCode\n";
echo "Upload Response: $uploadResponse\n";

// Clean up
unlink($tempFile);

echo "\nTest completed.\n";
?>