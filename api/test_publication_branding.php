<?php
/**
 * Test script for publication branding features
 * Run this to test the new branding functionality
 */

require_once 'config/database.php';
require_once 'models/Publication.php';

try {
    $publication = new Publication();
    
    echo "Testing Publication Branding Features\n";
    echo "=====================================\n\n";
    
    // Test creating a publication with branding
    $testData = [
        'name' => 'Test Publication with Branding',
        'description' => 'A test publication to verify branding features',
        'logo_url' => 'https://example.com/logo.png',
        'website_url' => 'https://testpub.com',
        'social_links' => [
            'twitter' => 'https://twitter.com/testpub',
            'facebook' => 'https://facebook.com/testpub',
            'linkedin' => 'https://linkedin.com/company/testpub'
        ],
        'theme_color' => '#FF6B6B',
        'custom_css' => '.publication-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }',
        'owner_id' => 1 // Assuming user ID 1 exists
    ];
    
    echo "1. Testing publication creation with branding...\n";
    $publicationId = $publication->create($testData);
    
    if ($publicationId) {
        echo "✓ Publication created successfully with ID: $publicationId\n";
        
        // Test retrieving the publication
        echo "\n2. Testing publication retrieval...\n";
        $retrievedPub = $publication->getById($publicationId);
        
        if ($retrievedPub) {
            echo "✓ Publication retrieved successfully\n";
            echo "   Name: " . $retrievedPub['name'] . "\n";
            echo "   Website: " . ($retrievedPub['website_url'] ?? 'None') . "\n";
            echo "   Theme Color: " . ($retrievedPub['theme_color'] ?? 'None') . "\n";
            
            if ($retrievedPub['social_links']) {
                $socialLinks = is_string($retrievedPub['social_links']) 
                    ? json_decode($retrievedPub['social_links'], true) 
                    : $retrievedPub['social_links'];
                echo "   Social Links: " . json_encode($socialLinks) . "\n";
            }
            
            // Test updating the publication
            echo "\n3. Testing publication update...\n";
            $updateData = [
                'name' => 'Updated Test Publication',
                'description' => 'Updated description',
                'logo_url' => 'https://example.com/new-logo.png',
                'website_url' => 'https://newtestpub.com',
                'social_links' => [
                    'twitter' => 'https://twitter.com/newtestpub',
                    'instagram' => 'https://instagram.com/newtestpub'
                ],
                'theme_color' => '#4ECDC4',
                'custom_css' => '.publication-header { background: #4ECDC4; }'
            ];
            
            $updateResult = $publication->update($publicationId, $updateData);
            
            if ($updateResult) {
                echo "✓ Publication updated successfully\n";
                
                // Verify the update
                $updatedPub = $publication->getById($publicationId);
                echo "   Updated Name: " . $updatedPub['name'] . "\n";
                echo "   Updated Theme Color: " . $updatedPub['theme_color'] . "\n";
            } else {
                echo "✗ Failed to update publication\n";
            }
            
            // Clean up - delete the test publication
            echo "\n4. Cleaning up test data...\n";
            $deleteResult = $publication->delete($publicationId);
            
            if ($deleteResult) {
                echo "✓ Test publication deleted successfully\n";
            } else {
                echo "✗ Failed to delete test publication\n";
            }
            
        } else {
            echo "✗ Failed to retrieve publication\n";
        }
    } else {
        echo "✗ Failed to create publication\n";
    }
    
    echo "\nTest completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Note: Make sure the database is set up and the publications table has the new branding columns.\n";
    echo "Run the SQL script in database/add_publication_branding.sql first.\n";
}
?>