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
    $date_from      = sanitize_text_field( $_POST['date_from'] ?? '' );
    $date_to        = sanitize_text_field( $_POST['date_to'] ?? '' );
    $template_id    = intval( $_POST['template_id'] ?? 0 );
    $search_in_title = $_POST['search_in_title'] ?? 'yes';
    $search_in_content = $_POST['search_in_content'] ?? 'yes';
    $search_in_acf = $_POST['search_in_acf'] ?? 'yes';
    
    // Debug logging
    error_log('GPW AJAX Request - Search Term: ' . $search_term);
    error_log('GPW AJAX Request - ACF Filters: ' . print_r($acf_filters, true));
    error_log('GPW AJAX Request - Tax Filters: ' . print_r($tax_filters, true));

    // Build the query arguments
    $args = [
        'post_type'      => $post_type,
        'posts_per_page' => $posts_per_page,
        'paged'          => $paged,
        'post_status'    => 'publish',
        'meta_query'     => [],
        'tax_query'      => [],
        'date_query'     => []
    ];

    // Handle search functionality
    if ( ! empty( $search_term ) ) {
        $search_queries = [];
        
        // Search in title and content (WordPress default)
        if ( $search_in_title === 'yes' || $search_in_content === 'yes' ) {
            $args['s'] = $search_term;
        }
        
        // Search in ACF fields
        if ( $search_in_acf === 'yes' && function_exists( 'get_field_objects' ) ) {
            $acf_search_query = [];
            $acf_search_query['relation'] = 'OR';
            
            // Get all ACF fields for this post type
            $sample_posts = get_posts([
                'post_type' => $post_type,
                'posts_per_page' => 10,
                'post_status' => 'publish'
            ]);
            
            $acf_field_names = [];
            foreach ( $sample_posts as $post ) {
                $fields = get_field_objects( $post->ID );
                if ( $fields ) {
                    foreach ( $fields as $field ) {
                        $acf_field_names[] = $field['name'];
                    }
                }
            }
            
            // Remove duplicates
            $acf_field_names = array_unique( $acf_field_names );
            
            // Add search conditions for each ACF field
            foreach ( $acf_field_names as $field_name ) {
                $acf_search_query[] = [
                    'key'     => $field_name,
                    'value'   => $search_term,
                    'compare' => 'LIKE'
                ];
            }
            
            if ( ! empty( $acf_search_query ) && count( $acf_search_query ) > 1 ) {
                $args['meta_query'][] = $acf_search_query;
            }
        }
    }

    // Build meta query for ACF field filters
    if ( ! empty( $acf_filters ) ) {
        foreach ( $acf_filters as $key => $val ) {
            if ( ! empty( $val ) ) {
                $key = sanitize_text_field( $key );
                
                if ( is_array( $val ) ) {
                    // Handle checkbox/radio groups - filter out empty values
                    $val = array_filter( array_map( 'sanitize_text_field', $val ) );
                    if ( ! empty( $val ) ) {
                        $args['meta_query'][] = [
                            'key'     => $key,
                            'value'   => $val,
                            'compare' => 'IN'
                        ];
                    }
                } else {
                    $val = sanitize_text_field( $val );
                    if ( $val !== '' ) {
                        $args['meta_query'][] = [
                            'key'     => $key,
                            'value'   => $val,
                            'compare' => 'LIKE'
                        ];
                    }
                }
            }
        }
    }

    // Build taxonomy query
    if ( ! empty( $tax_filters ) ) {
        foreach ( $tax_filters as $taxonomy => $term_ids ) {
            if ( ! empty( $term_ids ) ) {
                $taxonomy = sanitize_text_field( $taxonomy );
                
                if ( is_array( $term_ids ) ) {
                    $term_ids = array_filter( array_map( 'intval', $term_ids ) );
                    if ( ! empty( $term_ids ) ) {
                        $args['tax_query'][] = [
                            'taxonomy' => $taxonomy,
                            'field'    => 'term_id',
                            'terms'    => $term_ids,
                            'operator' => 'IN'
                        ];
                    }
                } else {
                    $term_id = intval( $term_ids );
                    if ( $term_id > 0 ) {
                        $args['tax_query'][] = [
                            'taxonomy' => $taxonomy,
                            'field'    => 'term_id',
                            'terms'    => $term_id
                        ];
                    }
                }
            }
        }
    }

    // Build date query
    if ( ! empty( $date_from ) || ! empty( $date_to ) ) {
        $date_args = [];
        
        if ( ! empty( $date_from ) ) {
            $date_args['after'] = $date_from;
            $date_args['inclusive'] = true;
        }
        
        if ( ! empty( $date_to ) ) {
            $date_args['before'] = $date_to;
            $date_args['inclusive'] = true;
        }
        
        if ( ! empty( $date_args ) ) {
            $args['date_query'][] = $date_args;
        }
    }

    // Set relations for multiple queries
    if ( count( $args['meta_query'] ) > 1 ) {
        $args['meta_query']['relation'] = 'AND';
    }

    if ( count( $args['tax_query'] ) > 1 ) {
        $args['tax_query']['relation'] = 'AND';
    }

    // Debug: Log final query args
    error_log('GPW Final Query Args: ' . print_r($args, true));

    // Execute the query
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
                echo '<span class="gpw-date">📅 ' . get_the_date() . '</span>';
                if ( has_category() ) {
                    echo '<span class="gpw-categories">🏷️ ' . get_the_category_list( ', ' ) . '</span>';
                }
                echo '</div>';
                echo '</div>';
            }
        }
        wp_reset_postdata();
        $html = ob_get_clean();

        wp_send_json_success([
            'html'         => $html,
            'max_pages'    => $query->max_num_pages,
            'found_posts'  => $query->found_posts,
            'current_page' => $paged
        ]);
    } else {
        wp_send_json_success([
            'html'         => '<div class="gpw-no-posts">No posts found matching your criteria.</div>',
            'max_pages'    => 0,
            'found_posts'  => 0,
            'current_page' => $paged
        ]);
    }
}