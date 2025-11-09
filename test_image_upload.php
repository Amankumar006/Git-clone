<?php
/**
 * Test image upload functionality
 */

echo "🧪 Testing Image Upload System\n";
echo str_repeat("=", 50) . "\n\n";

// Create a simple 1x1 pixel PNG image for testing
$testImageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChAI9jU77yQAAAABJRU5ErkJggg==');
$tempFile = tempnam(sys_get_temp_dir(), 'test_image') . '.png';
file_put_contents($tempFile, $testImageData);

echo "📁 Created test image: $tempFile\n";
echo "📏 Image size: " . filesize($tempFile) . " bytes\n\n";

// Test the upload endpoint
$uploadUrl = 'http://localhost:8000/api/upload/image';
echo "🌐 Testing upload endpoint: $uploadUrl\n";

// No token for development testing
$testToken = null;

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $uploadUrl,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => [
        'image' => new CURLFile($tempFile, 'image/png', 'test.png')
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $testToken ? [
        'Authorization: Bearer ' . $testToken
    ] : [],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "📊 HTTP Status: $httpCode\n";

if ($error) {
    echo "❌ cURL Error: $error\n";
} else {
    echo "📝 Response: $response\n";
    
    $responseData = json_decode($response, true);
    if ($responseData && isset($responseData['success'])) {
        if ($responseData['success']) {
            echo "✅ Upload successful!\n";
            if (isset($responseData['data']['url'])) {
                $imageUrl = $responseData['data']['url'];
                echo "🖼️  Image URL: $imageUrl\n";
                
                // Test if the image can be served
                echo "\n🔍 Testing image serving...\n";
                $serveUrl = 'http://localhost:8000' . $imageUrl;
                
                $ch2 = curl_init();
                curl_setopt_array($ch2, [
                    CURLOPT_URL => $serveUrl,
                    CURLOPT_NOBODY => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 10
                ]);
                
                curl_exec($ch2);
                $serveHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                $contentType = curl_getinfo($ch2, CURLINFO_CONTENT_TYPE);
                curl_close($ch2);
                
                echo "📊 Serve HTTP Status: $serveHttpCode\n";
                echo "📄 Content-Type: $contentType\n";
                
                if ($serveHttpCode === 200) {
                    echo "✅ Image serving works!\n";
                } else {
                    echo "❌ Image serving failed!\n";
                }
            }
        } else {
            echo "❌ Upload failed: " . ($responseData['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "❌ Invalid response format\n";
    }
}

// Clean up
unlink($tempFile);
echo "\n🧹 Cleaned up test file\n";
echo "\n✅ Test completed\n";
?>