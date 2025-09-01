<?php
/**
 * Test file for Generic Posts Elementor Widget
 * Place this in your theme directory and access it via your browser
 */

// Include WordPress
require_once('../../../wp-load.php');

// Check if ACF is active
if (function_exists('get_fields')) {
    echo '<h2>✅ ACF Plugin is Active</h2>';
    
    // Get available field groups
    $field_groups = acf_get_field_groups();
    echo '<h3>Available ACF Field Groups:</h3>';
    echo '<ul>';
    foreach ($field_groups as $group) {
        echo '<li>' . $group['title'] . ' (ID: ' . $group['ID'] . ')</li>';
    }
    echo '</ul>';
    
    // Get fields for posts
    $post_fields = acf_get_field_groups(['post_type' => 'post']);
    echo '<h3>ACF Fields for Posts:</h3>';
    echo '<ul>';
    foreach ($post_fields as $group) {
        $fields = acf_get_fields($group);
        if ($fields) {
            foreach ($fields as $field) {
                echo '<li>' . $field['label'] . ' (' . $field['name'] . ') - Type: ' . $field['type'] . '</li>';
            }
        }
    }
    echo '</ul>';
    
} else {
    echo '<h2>❌ ACF Plugin is NOT Active</h2>';
    echo '<p>Install and activate Advanced Custom Fields plugin for full functionality.</p>';
}

// Check if Elementor is active
if (class_exists('\Elementor\Plugin')) {
    echo '<h2>✅ Elementor Plugin is Active</h2>';
} else {
    echo '<h2>❌ Elementor Plugin is NOT Active</h2>';
}

// Check if our widget class exists
if (class_exists('Generic_Posts_Widget')) {
    echo '<h2>✅ Generic Posts Widget Class is Loaded</h2>';
} else {
    echo '<h2>❌ Generic Posts Widget Class is NOT Loaded</h2>';
}

// Test AJAX endpoint
echo '<h2>Testing AJAX Endpoint</h2>';
echo '<p>AJAX URL: ' . admin_url('admin-ajax.php') . '</p>';
echo '<p>Action: gpw_filter_posts</p>';

// Test post query
echo '<h2>Testing Basic Post Query</h2>';
$test_query = new WP_Query([
    'post_type' => 'post',
    'posts_per_page' => 5,
    'post_status' => 'publish'
]);

if ($test_query->have_posts()) {
    echo '<ul>';
    while ($test_query->have_posts()) {
        $test_query->the_post();
        echo '<li>' . get_the_title() . ' (ID: ' . get_the_ID() . ')</li>';
    }
    echo '</ul>';
    wp_reset_postdata();
} else {
    echo '<p>No posts found.</p>';
}

echo '<hr>';
echo '<p><strong>Note:</strong> This test file helps verify the widget setup. Remove it from production.</p>';
?>
