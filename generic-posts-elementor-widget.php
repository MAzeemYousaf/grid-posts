<?php
/**
 * Plugin Name: Generic Posts Elementor Widget
 * Description: Elementor widget with post type, Elementor template rendering, AJAX search, filters, ACF fields, and pagination.
 * Version: 1.0
 * Author: Hussnain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Register widget
add_action( 'elementor/widgets/register', function( $widgets_manager ) {
    require_once __DIR__ . '/widgets/class-generic-posts-widget.php';
    $widgets_manager->register( new \Generic_Posts_Widget() );
});

// Enqueue scripts and styles
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'generic-posts-widget',
        plugin_dir_url( __FILE__ ) . 'assets/css/generic-posts-widget.css',
        [],
        '1.0'
    );

    wp_enqueue_script(
        'generic-posts-widget',
        plugin_dir_url( __FILE__ ) . 'assets/js/generic-posts-widget.js',
        ['jquery'],
        '1.0',
        true
    );

    wp_localize_script( 'generic-posts-widget', 'GPW_Ajax', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'gpw_nonce' )
    ] );
});

// AJAX handler
add_action( 'wp_ajax_gpw_filter_posts', 'gpw_filter_posts' );
add_action( 'wp_ajax_nopriv_gpw_filter_posts', 'gpw_filter_posts' );

function gpw_filter_posts() {
    check_ajax_referer( 'gpw_nonce', 'nonce' );

    $post_type      = sanitize_text_field( $_POST['post_type'] ?? 'post' );
    $posts_per_page = intval( $_POST['posts_per_page'] ?? 6 );
    $paged          = intval( $_POST['paged'] ?? 1 );
    $search_term    = sanitize_text_field( $_POST['search'] ?? '' );
    $acf_filters    = $_POST['acf_filters'] ?? [];
    $tax_filters    = $_POST['tax_filters'] ?? [];
    $date_from      = $_POST['date_from'] ?? '';
    $date_to        = $_POST['date_to'] ?? '';
    $template_id    = intval( $_POST['template_id'] ?? 0 );
    $search_in_title = $_POST['search_in_title'] ?? 'yes';
    $search_in_content = $_POST['search_in_content'] ?? 'yes';
    $search_in_acf = $_POST['search_in_acf'] ?? 'yes';
    
    // Debug: Log all POST data
    error_log('GPW POST Data: ' . print_r($_POST, true));
    error_log('GPW Search Term: ' . $search_term);

    // Build meta query for ACF fields
    $meta_query = [];
    if ( ! empty( $acf_filters ) ) {
        foreach ( $acf_filters as $key => $val ) {
            if ( ! empty( $val ) ) {
                if ( is_array( $val ) ) {
                    // Handle checkbox/radio groups
                    $meta_query[] = [
                        'key'     => sanitize_text_field( $key ),
                        'value'   => $val,
                        'compare' => 'IN'
                    ];
                } else {
                    $meta_query[] = [
                        'key'     => sanitize_text_field( $key ),
                        'value'   => sanitize_text_field( $val ),
                        'compare' => 'LIKE'
                    ];
                }
            }
        }
    }
    
    // Debug logging
    error_log('GPW ACF Filters: ' . print_r($acf_filters, true));
    error_log('GPW Meta Query: ' . print_r($meta_query, true));

    // Build taxonomy query
    $tax_query = [];
    if ( ! empty( $tax_filters ) ) {
        foreach ( $tax_filters as $taxonomy => $term_id ) {
            if ( ! empty( $term_id ) ) {
                $tax_query[] = [
                    'taxonomy' => sanitize_text_field( $taxonomy ),
                    'field'    => 'term_id',
                    'terms'    => intval( $term_id )
                ];
            }
        }
    }

    // Build date query
    $date_query = [];
    if ( ! empty( $date_from ) || ! empty( $date_to ) ) {
        $date_args = [];
        
        if ( ! empty( $date_from ) ) {
            $date_args['after'] = sanitize_text_field( $date_from );
            $date_args['inclusive'] = true;
        }
        
        if ( ! empty( $date_to ) ) {
            $date_args['before'] = sanitize_text_field( $date_to );
            $date_args['inclusive'] = true;
        }
        
        if ( ! empty( $date_args ) ) {
            $date_query[] = $date_args;
        }
    }

    // Build search query
    $search_meta_query = [];
    if ( ! empty( $search_term ) ) {
        if ( $search_in_acf === 'yes' && function_exists( 'get_fields' ) ) {
            // Search in ACF fields
            $acf_search_query = [];
            $acf_search_query['relation'] = 'OR';
            
                // Get all ACF field keys for the post type
    $acf_fields = get_field_objects( $post_type );
    if ( $acf_fields ) {
        foreach ( $acf_fields as $field ) {
            $acf_search_query[] = [
                'key'     => $field['name'],
                'value'   => $search_term,
                'compare' => 'LIKE'
            ];
        }
    } else {
        // Fallback: try to get ACF fields from any post of this type
        $sample_post = get_posts([
            'post_type' => $post_type,
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);
        
        if (!empty($sample_post)) {
            $acf_fields = get_fields($sample_post[0]->ID);
            if ($acf_fields) {
                foreach ($acf_fields as $field_name => $field_value) {
                    $acf_search_query[] = [
                        'key'     => $field_name,
                        'value'   => $search_term,
                        'compare' => 'LIKE'
                    ];
                }
            }
        }
    }
            
            if ( ! empty( $acf_search_query ) ) {
                $search_meta_query[] = $acf_search_query;
            }
        }
    }
    
    // Debug logging for search
    error_log('GPW Search Term: ' . $search_term);
    error_log('GPW Search Meta Query: ' . print_r($search_meta_query, true));
    error_log('GPW ACF Fields Found: ' . print_r($acf_fields, true));
    error_log('GPW Search In Title: ' . $search_in_title);
    error_log('GPW Search In Content: ' . $search_in_content);
    error_log('GPW Search In ACF: ' . $search_in_acf);

    // Combine all queries
    $args = [
        'post_type'      => $post_type,
        'posts_per_page' => $posts_per_page,
        'paged'          => $paged,
        'meta_query'     => $meta_query,
        'tax_query'      => $tax_query,
        'date_query'     => $date_query,
    ];

    // Handle search
    if ( ! empty( $search_term ) ) {
        // Use WordPress default search for title and content
        $args['s'] = $search_term;
        
        // Add ACF field search to meta query if needed
        if ( ! empty( $search_meta_query ) ) {
            if ( ! empty( $args['meta_query'] ) ) {
                $args['meta_query']['relation'] = 'AND';
                $args['meta_query'][] = [
                    'relation' => 'OR',
                    $search_meta_query
                ];
            } else {
                $args['meta_query'] = $search_meta_query;
            }
        }
        
        // Debug: Log search args
        error_log('GPW Search Args: s=' . $args['s'] . ', meta_query=' . print_r($args['meta_query'], true));
    }
    
    // Debug logging for final query
    error_log('GPW Final Args: ' . print_r($args, true));
    error_log('GPW Search Term in Args: ' . (isset($args['s']) ? $args['s'] : 'NOT SET'));

    // Set relation for meta queries if we have multiple
    if ( count( $args['meta_query'] ) > 1 ) {
        $args['meta_query']['relation'] = 'AND';
    }

    // Set relation for tax queries if we have multiple
    if ( count( $args['tax_query'] ) > 1 ) {
        $args['tax_query']['relation'] = 'AND';
    }



    $query = new WP_Query( $args );

    if ( $query->have_posts() ) {
        ob_start();
        while ( $query->have_posts() ) {
            $query->the_post();
            if ( $template_id ) {
                echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $template_id, true );
            } else {
                echo '<div class="gpw-post">';
                echo '<h3><a href="' . get_permalink() . '">' . get_the_title() . '</a></h3>';
                echo '<div class="gpw-excerpt">' . get_the_excerpt() . '</div>';
                echo '<div class="gpw-meta">';
                echo '<span class="gpw-date">' . get_the_date() . '</span>';
                if ( has_category() ) {
                    echo '<span class="gpw-categories">' . get_the_category_list( ', ' ) . '</span>';
                }
                echo '</div>';
                echo '</div>';
            }
        }
        wp_reset_postdata();
        $html = ob_get_clean();

        wp_send_json_success([
            'html'       => $html,
            'max_pages'  => $query->max_num_pages,
            'found_posts' => $query->found_posts,
            'current_page' => $paged
        ]);
    } else {
        wp_send_json_success([
            'html' => '<p class="gpw-no-posts">No posts found matching your criteria.</p>',
            'max_pages' => 0,
            'found_posts' => 0,
            'current_page' => $paged
        ]);
    }
}
