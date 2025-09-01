<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Generic_Posts_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'generic_posts_widget';
    }

    public function get_title() {
        return 'Generic Posts Widget';
    }

    public function get_icon() {
        return 'eicon-post-list';
    }

    public function get_categories() {
        return [ 'general' ];
    }

    protected function register_controls() {
        // Content Section
        $this->start_controls_section( 'content_section', [
            'label' => 'Content Settings',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'post_type', [
            'label'   => 'Post Type',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => $this->get_post_types(),
            'default' => 'post',
        ] );

        $this->add_control( 'posts_per_page', [
            'label'   => 'Posts Per Page',
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 6,
            'min'     => 1,
            'max'     => 50,
        ] );

        $this->add_control( 'grid_columns', [
            'label'   => 'Grid Columns',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                '1' => '1 Column',
                '2' => '2 Columns',
                '3' => '3 Columns',
                '4' => '4 Columns',
                '5' => '5 Columns',
                '6' => '6 Columns',
            ],
            'default' => '3',
        ] );

        $this->add_control( 'grid_columns_tablet', [
            'label'   => 'Grid Columns (Tablet)',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                '1' => '1 Column',
                '2' => '2 Columns',
                '3' => '3 Columns',
                '4' => '4 Columns',
            ],
            'default' => '2',
        ] );

        $this->add_control( 'grid_columns_mobile', [
            'label'   => 'Grid Columns (Mobile)',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                '1' => '1 Column',
                '2' => '2 Columns',
            ],
            'default' => '1',
        ] );

        $this->add_control( 'template_id', [
            'label'   => 'Elementor Template',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => $this->get_elementor_templates(),
            'description' => 'Select an Elementor template to render each post',
        ] );

        $this->end_controls_section();

        // Search Section
        $this->start_controls_section( 'search_section', [
            'label' => 'Search Settings',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'show_search', [
            'label'   => 'Show Search Bar',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ] );

        $this->add_control( 'search_placeholder', [
            'label'     => 'Search Placeholder',
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => 'Search posts...',
            'condition' => [
                'show_search' => 'yes',
            ],
        ] );

        $this->add_control( 'search_in_title', [
            'label'     => 'Search in Post Title',
            'type'      => \Elementor\Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'condition' => [
                'show_search' => 'yes',
            ],
        ] );

        $this->add_control( 'search_in_content', [
            'label'     => 'Search in Post Content',
            'type'      => \Elementor\Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'condition' => [
                'show_search' => 'yes',
            ],
        ] );

        $this->add_control( 'search_in_acf', [
            'label'     => 'Search in ACF Fields',
            'type'      => \Elementor\Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'condition' => [
                'show_search' => 'yes',
            ],
        ] );

        $this->end_controls_section();

        // ACF Filters Section
        $this->start_controls_section( 'acf_filters_section', [
            'label' => 'ACF Field Filters',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'show_acf_filters', [
            'label'   => 'Show ACF Field Filters',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ] );

        $this->add_control( 'acf_fields', [
            'label'     => 'ACF Fields to Display',
            'type'      => \Elementor\Controls_Manager::REPEATER,
            'fields'    => [
                [
                    'name'        => 'field_name',
                    'label'       => 'Field Name',
                    'type'        => \Elementor\Controls_Manager::TEXT,
                    'description' => 'Enter the ACF field name (e.g., channels, tags)',
                ],
                [
                    'name'        => 'field_label',
                    'label'       => 'Field Label',
                    'type'        => \Elementor\Controls_Manager::TEXT,
                    'description' => 'Display label for the field',
                ],
                [
                    'name'        => 'field_type',
                    'label'       => 'Field Type',
                    'type'        => \Elementor\Controls_Manager::SELECT,
                    'options'     => [
                        'text'     => 'Text Input',
                        'select'   => 'Dropdown Select',
                        'checkbox' => 'Checkbox Group',
                        'radio'    => 'Radio Buttons',
                        'date'     => 'Date Picker',
                    ],
                    'default'     => 'checkbox',
                ],
                [
                    'name'        => 'get_options_from_acf',
                    'label'       => 'Get Options from ACF Field',
                    'type'        => \Elementor\Controls_Manager::SWITCHER,
                    'default'     => 'yes',
                    'description' => 'Automatically get options from ACF field choices',
                    'condition'   => [
                        'field_type' => ['select', 'checkbox', 'radio'],
                    ],
                ],
                [
                    'name'        => 'field_options',
                    'label'       => 'Manual Field Options (one per line)',
                    'type'        => \Elementor\Controls_Manager::TEXTAREA,
                    'description' => 'Enter options one per line (only used if "Get Options from ACF Field" is disabled)',
                    'condition'   => [
                        'field_type' => ['select', 'checkbox', 'radio'],
                        'get_options_from_acf' => '',
                    ],
                ],
            ],
            'condition' => [
                'show_acf_filters' => 'yes',
            ],
        ] );

        $this->end_controls_section();

        // Additional Filters Section
        $this->start_controls_section( 'filters_section', [
            'label' => 'Additional Filters',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'show_date_filter', [
            'label'   => 'Show Date Filter',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ] );

        $this->add_control( 'date_filter_label', [
            'label'     => 'Date Filter Label',
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => 'DATE',
            'condition' => [
                'show_date_filter' => 'yes',
            ],
        ] );

        $this->add_control( 'date_from_label', [
            'label'     => 'Date From Label',
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => 'From',
            'condition' => [
                'show_date_filter' => 'yes',
            ],
        ] );

        $this->add_control( 'date_to_label', [
            'label'     => 'Date To Label',
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => 'To',
            'condition' => [
                'show_date_filter' => 'yes',
            ],
        ] );

        $this->add_control( 'show_taxonomy_filters', [
            'label'   => 'Show Taxonomy Filters',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'no',
        ] );

        $this->add_control( 'taxonomy_filters', [
            'label'     => 'Taxonomies to Filter',
            'type'      => \Elementor\Controls_Manager::REPEATER,
            'fields'    => [
                [
                    'name'        => 'taxonomy',
                    'label'       => 'Taxonomy',
                    'type'        => \Elementor\Controls_Manager::SELECT,
                    'options'     => $this->get_taxonomies(),
                ],
                [
                    'name'        => 'taxonomy_label',
                    'label'       => 'Taxonomy Label',
                    'type'        => \Elementor\Controls_Manager::TEXT,
                    'description' => 'Display label for the taxonomy',
                ],
            ],
            'condition' => [
                'show_taxonomy_filters' => 'yes',
            ],
        ] );

        $this->add_control( 'show_confirm_button', [
            'label'   => 'Show Confirm Button',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
            'description' => 'Show confirm button to apply filters',
        ] );

        $this->add_control( 'confirm_button_text', [
            'label'     => 'Confirm Button Text',
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => 'CONFIRM',
            'condition' => [
                'show_confirm_button' => 'yes',
            ],
        ] );

        $this->add_control( 'reset_button_text', [
            'label'     => 'Reset Button Text',
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => 'RESET ALL FILTERS',
            'condition' => [
                'show_confirm_button' => 'yes',
            ],
        ] );

        $this->end_controls_section();

        // Pagination Section
        $this->start_controls_section( 'pagination_section', [
            'label' => 'Pagination',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'show_pagination', [
            'label'   => 'Show Pagination',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ] );

        $this->add_control( 'pagination_type', [
            'label'     => 'Pagination Type',
            'type'      => \Elementor\Controls_Manager::SELECT,
            'options'   => [
                'numbers'     => 'Page Numbers',
                'prev_next'   => 'Previous/Next',
                'load_more'   => 'Load More Button',
                'infinite'    => 'Infinite Scroll',
            ],
            'default'   => 'numbers',
            'condition' => [
                'show_pagination' => 'yes',
            ],
        ] );

        $this->add_control( 'load_more_text', [
            'label'     => 'Load More Button Text',
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => 'Load More Posts',
            'condition' => [
                'show_pagination' => 'yes',
                'pagination_type' => 'load_more',
            ],
        ] );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section( 'style_section', [
            'label' => 'Style',
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'filters_layout', [
            'label'   => 'Filters Layout',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'accordion'  => 'Accordion',
                'horizontal' => 'Horizontal',
                'vertical'   => 'Vertical',
                'grid'       => 'Grid',
            ],
            'default' => 'accordion',
        ] );

        $this->add_control( 'filters_spacing', [
            'label'      => 'Filters Spacing',
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => [
                'px' => [
                    'min' => 0,
                    'max' => 50,
                ],
                'em' => [
                    'min' => 0,
                    'max' => 5,
                ],
            ],
            'default'    => [
                'unit' => 'px',
                'size' => 15,
            ],
            'selectors'  => [
                '{{WRAPPER}} .gpw-filter-item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'grid_column_gap', [
            'label'      => 'Grid Column Gap',
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => [
                'px' => [
                    'min' => 0,
                    'max' => 100,
                ],
                'em' => [
                    'min' => 0,
                    'max' => 10,
                ],
            ],
            'default'    => [
                'unit' => 'px',
                'size' => 30,
            ],
            'selectors'  => [
                '{{WRAPPER}} .gpw-posts-grid' => 'column-gap: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'grid_row_gap', [
            'label'      => 'Grid Row Gap',
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => [
                'px' => [
                    'min' => 0,
                    'max' => 100,
                ],
                'em' => [
                    'min' => 0,
                    'max' => 10,
                ],
            ],
            'default'    => [
                'unit' => 'px',
                'size' => 30,
            ],
            'selectors'  => [
                '{{WRAPPER}} .gpw-posts-grid' => 'row-gap: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        ?>
        <div class="gpw-wrapper" data-settings='<?php echo json_encode( $settings ); ?>'>
            <?php if ( $settings['show_search'] === 'yes' ): ?>
                <div class="gpw-search-wrapper">
                    <input type="text" class="gpw-search" placeholder="<?php echo esc_attr( $settings['search_placeholder'] ); ?>">
                </div>
            <?php endif; ?>

            <!-- Selected Filters Display -->
            <div class="gpw-selected-filters" style="display: none;">
                <div class="gpw-selected-filters-header">
                    <span class="gpw-filter-icon">🔍</span>
                    <span>Active Filters (<span class="gpw-selected-count">0</span>)</span>
                </div>
                <div class="gpw-selected-filters-tags"></div>
            </div>

            <!-- Filters Section -->
            <?php if ( $settings['show_acf_filters'] === 'yes' || $settings['show_date_filter'] === 'yes' || ( $settings['show_taxonomy_filters'] === 'yes' && ! empty( $settings['taxonomy_filters'] ) ) ): ?>
                
                <!-- ACF Fields Accordions -->
                <?php if ( $settings['show_acf_filters'] === 'yes' && ! empty( $settings['acf_fields'] ) ): ?>
                    <?php foreach ( $settings['acf_fields'] as $field ): ?>
                        <div class="gpw-filters-accordion">
                            <div class="gpw-accordion-header">
                                <h3><?php echo esc_html( strtoupper( $field['field_label'] ) ); ?> <span class="gpw-field-count"></span></h3>
                                <span class="gpw-accordion-toggle">▼</span>
                            </div>
                            <div class="gpw-accordion-content">
                                <div class="gpw-filter-item">
                                    <?php echo $this->render_filter_field( $field, $settings['post_type'] ); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Date Filter Accordion -->
                <?php if ( $settings['show_date_filter'] === 'yes' ): ?>
                    <div class="gpw-filters-accordion">
                        <div class="gpw-accordion-header">
                            <h3><?php echo esc_html( strtoupper( $settings['date_filter_label'] ) ); ?></h3>
                            <span class="gpw-accordion-toggle">▼</span>
                        </div>
                        <div class="gpw-accordion-content">
                            <div class="gpw-date-range">
                                <div class="gpw-date-input">
                                    <label><?php echo esc_html( $settings['date_from_label'] ); ?></label>
                                    <input type="date" class="gpw-date-filter-from">
                                </div>
                                <div class="gpw-date-input">
                                    <label><?php echo esc_html( $settings['date_to_label'] ); ?></label>
                                    <input type="date" class="gpw-date-filter-to">
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Taxonomy Filters Accordions -->
                <?php if ( $settings['show_taxonomy_filters'] === 'yes' && ! empty( $settings['taxonomy_filters'] ) ): ?>
                    <?php foreach ( $settings['taxonomy_filters'] as $tax_filter ): ?>
                        <div class="gpw-filters-accordion">
                            <div class="gpw-accordion-header">
                                <h3><?php echo esc_html( strtoupper( $tax_filter['taxonomy_label'] ) ); ?></h3>
                                <span class="gpw-accordion-toggle">▼</span>
                            </div>
                            <div class="gpw-accordion-content">
                                <div class="gpw-filter-item">
                                    <div class="gpw-checkbox-group">
                                        <?php echo $this->get_taxonomy_checkboxes( $tax_filter['taxonomy'] ); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Action Buttons -->
                <?php if ( $settings['show_confirm_button'] === 'yes' ): ?>
                    <div class="gpw-filter-actions" style="display: none;">
                        <button type="button" class="gpw-reset-filters"><?php echo esc_html( $settings['reset_button_text'] ); ?></button>
                        <button type="button" class="gpw-confirm-filters"><?php echo esc_html( $settings['confirm_button_text'] ); ?></button>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

            <div class="gpw-results">
                <div class="gpw-posts-grid gpw-grid-<?php echo esc_attr( $settings['grid_columns'] ); ?> gpw-grid-tablet-<?php echo esc_attr( $settings['grid_columns_tablet'] ); ?> gpw-grid-mobile-<?php echo esc_attr( $settings['grid_columns_mobile'] ); ?>"></div>
            </div>
            
            <?php if ( $settings['show_pagination'] === 'yes' ): ?>
                <div class="gpw-pagination gpw-pagination-<?php echo esc_attr( $settings['pagination_type'] ); ?>"></div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_filter_field( $field, $post_type ) {
        $field_name = $field['field_name'];
        $field_type = $field['field_type'];
        $get_from_acf = $field['get_options_from_acf'] === 'yes';
        $options = [];

        // Get options from ACF field or manual input
        if ( $get_from_acf && function_exists( 'get_field_object' ) ) {
            // Try to get field object from ACF
            $acf_field = get_field_object( $field_name );
            if ( $acf_field && isset( $acf_field['choices'] ) ) {
                $options = $acf_field['choices'];
            } else {
                // Fallback: get unique values from posts
                $options = $this->get_acf_field_values( $field_name, $post_type );
            }
        } else if ( ! empty( $field['field_options'] ) ) {
            $manual_options = array_filter( array_map( 'trim', explode( "\n", $field['field_options'] ) ) );
            foreach ( $manual_options as $option ) {
                $options[ $option ] = $option;
            }
        }

        switch ( $field_type ) {
            case 'select':
                $html = '<select class="gpw-acf-filter" data-field="' . esc_attr( $field_name ) . '">';
                $html .= '<option value="">All</option>';
                foreach ( $options as $value => $label ) {
                    $html .= '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
                }
                $html .= '</select>';
                return $html;

            case 'checkbox':
                $html = '<div class="gpw-checkbox-group">';
                foreach ( $options as $value => $label ) {
                    $html .= '<label><input type="checkbox" class="gpw-acf-filter" data-field="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '"> ' . esc_html( $label ) . '</label>';
                }
                $html .= '</div>';
                return $html;

            case 'radio':
                $html = '<div class="gpw-radio-group">';
                $html .= '<label><input type="radio" name="gpw_' . esc_attr( $field_name ) . '" class="gpw-acf-filter" data-field="' . esc_attr( $field_name ) . '" value=""> All</label>';
                foreach ( $options as $value => $label ) {
                    $html .= '<label><input type="radio" name="gpw_' . esc_attr( $field_name ) . '" class="gpw-acf-filter" data-field="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '"> ' . esc_html( $label ) . '</label>';
                }
                $html .= '</div>';
                return $html;

            case 'date':
                return '<input type="date" class="gpw-acf-filter" data-field="' . esc_attr( $field_name ) . '">';

            default:
                return '<input type="text" class="gpw-acf-filter" data-field="' . esc_attr( $field_name ) . '" placeholder="Enter ' . esc_attr( $field['field_label'] ) . '">';
        }
    }

    private function get_acf_field_values( $field_name, $post_type ) {
        global $wpdb;
        
        $values = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT pm.meta_value 
             FROM {$wpdb->postmeta} pm 
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE pm.meta_key = %s 
             AND p.post_type = %s 
             AND p.post_status = 'publish' 
             AND pm.meta_value != '' 
             ORDER BY pm.meta_value",
            $field_name,
            $post_type
        ) );
        
        $options = [];
        foreach ( $values as $value ) {
            // Handle serialized arrays (for checkbox fields)
            if ( is_serialized( $value ) ) {
                $unserialized = maybe_unserialize( $value );
                if ( is_array( $unserialized ) ) {
                    foreach ( $unserialized as $item ) {
                        if ( ! empty( $item ) ) {
                            $options[ $item ] = $item;
                        }
                    }
                }
            } else if ( ! empty( $value ) ) {
                $options[ $value ] = $value;
            }
        }
        
        return $options;
    }

    private function get_post_types() {
        $post_types = get_post_types( [ 'public' => true ], 'objects' );
        $options = [];
        foreach ( $post_types as $pt ) {
            $options[ $pt->name ] = $pt->label;
        }
        return $options;
    }

    private function get_elementor_templates() {
        $templates = get_posts([
            'post_type'      => 'elementor_library',
            'posts_per_page' => -1
        ]);
        $options = [ '' => 'Default Layout' ];
        foreach ( $templates as $tpl ) {
            $options[ $tpl->ID ] = $tpl->post_title;
        }
        return $options;
    }

    private function get_taxonomies() {
        $taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );
        $options = [];
        foreach ( $taxonomies as $tax ) {
            $options[ $tax->name ] = $tax->label;
        }
        return $options;
    }

    private function get_taxonomy_options( $taxonomy ) {
        $terms = get_terms([
            'taxonomy'   => $taxonomy,
            'hide_empty' => true,
        ]);

        if ( is_wp_error( $terms ) ) {
            return '';
        }

        $html = '';
        foreach ( $terms as $term ) {
            $html .= '<option value="' . esc_attr( $term->term_id ) . '">' . esc_html( $term->name ) . '</option>';
        }
        return $html;
    }

    private function get_taxonomy_checkboxes( $taxonomy ) {
        $terms = get_terms([
            'taxonomy'   => $taxonomy,
            'hide_empty' => true,
        ]);

        if ( is_wp_error( $terms ) ) {
            return '';
        }

        $html = '';
        foreach ( $terms as $term ) {
            $html .= '<label><input type="checkbox" class="gpw-tax-filter" data-taxonomy="' . esc_attr( $taxonomy ) . '" value="' . esc_attr( $term->term_id ) . '"> ' . esc_html( $term->name ) . '</label>';
        }
        return $html;
    }
}