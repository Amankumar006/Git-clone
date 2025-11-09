<?php
// Test script for publication following functionality
require_once 'config/database.php';
require_once 'models/Publication.php';

try {
    $publicationModel = new Publication();
    
    echo "Testing Publication Following Functionality\n";
    echo "==========================================\n\n";
    
    // Test 1: Create a test publication (if not exists)
    echo "1. Testing publication creation...\n";
    $testPubData = [
        'name' => 'Test Publication for Following',
        'description' => 'A test publication to verify following functionality',
        'owner_id' => 1 // Assuming user ID 1 exists
    ];
    
    // Check if test publication already exists
    $existingPubs = $publicationModel->search('Test Publication for Following', 1, 0);
    if (empty($existingPubs)) {
        $pubId = $publicationModel->create($testPubData);
        echo "   ✓ Created test publication with ID: $pubId\n";
    } else {
        $pubId = $existingPubs[0]['id'];
        echo "   ✓ Using existing test publication with ID: $pubId\n";
    }
    
    // Test 2: Follow publication
    echo "\n2. Testing publication following...\n";
    $userId = 2; // Assuming user ID 2 exists
    
    $followResult = $publicationModel->followPublication($pubId, $userId);
    if ($followResult) {
        echo "   ✓ Successfully followed publication\n";
    } else {
        echo "   ✗ Failed to follow publication\n";
    }
    
    // Test 3: Check if following
    echo "\n3. Testing follow status check...\n";
    $isFollowing = $publicationModel->isFollowing($pubId, $userId);
    if ($isFollowing) {
        echo "   ✓ User is following the publication\n";
    } else {
        echo "   ✗ User is not following the publication\n";
    }
    
    // Test 4: Get followers count
    echo "\n4. Testing followers count...\n";
    $followersCount = $publicationModel->getFollowersCount($pubId);
    echo "   ✓ Publication has $followersCount followers\n";
    
    // Test 5: Get followed publications by user
    echo "\n5. Testing get followed publications...\n";
    $followedPubs = $publicationModel->getFollowedByUser($userId, 10, 0);
    echo "   ✓ User is following " . count($followedPubs) . " publications\n";
    
    // Test 6: Unfollow publication
    echo "\n6. Testing publication unfollowing...\n";
    $unfollowResult = $publicationModel->unfollowPublication($pubId, $userId);
    if ($unfollowResult) {
        echo "   ✓ Successfully unfollowed publication\n";
    } else {
        echo "   ✗ Failed to unfollow publication\n";
    }
    
    // Test 7: Verify unfollow
    echo "\n7. Verifying unfollow...\n";
    $isFollowingAfter = $publicationModel->isFollowing($pubId, $userId);
    if (!$isFollowingAfter) {
        echo "   ✓ User is no longer following the publication\n";
    } else {
        echo "   ✗ User is still following the publication\n";
    }
    
    echo "\n==========================================\n";
    echo "Publication following tests completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Make sure the database is set up and users exist.\n";
}
?>