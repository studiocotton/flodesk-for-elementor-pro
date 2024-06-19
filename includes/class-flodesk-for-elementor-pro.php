<?php

class Flodesk_Elementor_Pro extends \ElementorPro\Modules\Forms\Classes\Integration_Base {
    const OPTION_NAME_API_KEY = 'flodesk_api_key';

    private function get_global_api_key() {
        return get_option( 'elementor_' . self::OPTION_NAME_API_KEY );
    }

    public function get_name() {
        return 'flodesk';
    }

    public function get_label() {
        return __( 'Flodesk');
    }

    public function run( $record, $ajax_handler ) {
        $settings = $record->get( 'form_settings' );

        $api_key = $this->get_global_api_key();

        if ( empty( $settings['flodesk_segments'] ) ) {
            return;
        }

        // Normalize form data.
        $raw_fields = $record->get( 'fields' );

        $fields = array();
        
        foreach ( $raw_fields as $id => $field ) {
            $fields[ $id ] = $field['value'];
        }

        if( !isset( $fields[ $settings['flodesk_email_field'] ] ) ) {
            return;
        }

        $body = array(
            'segment_ids' => $settings['flodesk_segments'],
            'double_optin' => $settings['flodesk_double_optin'] == 'yes'
        );

        $body['email'] = $fields[ $settings['flodesk_email_field'] ];
        unset( $fields[ $settings['flodesk_email_field'] ] );

        if( isset( $fields[ $settings['flodesk_first_name_field'] ] ) ) {
            $body['first_name'] = $fields[ $settings['flodesk_first_name_field'] ];
            unset( $fields[ $settings['flodesk_first_name_field'] ] );
        }

        if( isset( $fields[ $settings['flodesk_last_name_field'] ] ) ) {
            $body['last_name'] = $fields[ $settings['flodesk_last_name_field'] ];
            unset( $fields[ $settings['flodesk_last_name_field'] ] );
        }

        // if( !empty( $fields ) ) {
        // 	$body['custom_fields'] = $fields;
        // }
        
        $api_key_base64 = base64_encode( $api_key );
        
        $result = wp_remote_post( "https://api.flodesk.com/v1/subscribers", array(
            'body' => wp_json_encode( $body ),
            'headers' => [
                'Authorization' => "Basic $api_key_base64",
                'Content-Type' => 'application/json',
            ],
        ) );
    }

    public function register_settings_section( $widget ) {
        $widget->start_controls_section(
            'flodesk_settings',
            [
                'label' => __( 'Flodesk'),
                'condition' => [
                    'submit_actions' => $this->get_name(),
                ],
            ]
        );
        
        $widget->add_control(
            'flodesk_email_field',
            [
                'label' => esc_html__( 'Email Field ID', 'flodesk-for-elementor-pro' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'email',
            ]
        );
        
        $widget->add_control(
            'flodesk_first_name_field',
            [
                'label' => esc_html__( 'First Name Field ID', 'flodesk-for-elementor-pro' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'first_name',
            ]
        );
        
        $widget->add_control(
            'flodesk_last_name_field',
            [
                'label' => esc_html__( 'Last Name Field ID', 'flodesk-for-elementor-pro' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'last_name',
            ]
        );

        $segments = $this->get_segments();
        $options = array();
        
        foreach ( $segments as $segment ) {
            $options[ $segment['id'] ] = $segment['name'];
        }

        $widget->add_control(
            'flodesk_segments',
            [
                'label' => esc_html__( 'Flodesk Segments', 'flodesk-for-elementor-pro' ),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'separator' => 'before',
                'label_block' => true,
                'multiple' => true,
                'options' => $options,
            ]
        );

        $widget->add_control(
            'flodesk_double_optin',
            [
                'label' => esc_html__( 'Double Opt-In', 'flodesk-for-elementor-pro' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'separator' => 'before',
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $widget->end_controls_section();
    }

    public function on_export( $element ) {
        unset(
            $element['flodesk_email_field'],
            $element['flodesk_first_name_field'],
            $element['flodesk_last_name_field'],
            $element['flodesk_segments'],
            $element['flodesk_double_optin']
        );

        return $element;
    }

    public function get_segments() {
        $api_key = $this->get_global_api_key();

        if ( empty( $api_key ) ) {
            return array();
        }

        $api_key_base64 = base64_encode( $api_key );

        $segments = wp_remote_retrieve_body( wp_remote_get( "https://api.flodesk.com/v1/segments", array(
            'headers' => [
                'Authorization' => "Basic $api_key_base64"
            ],
        ) ) );

        $segments = json_decode($segments, true);
        return $segments['data'];
    }

    public function register_admin_fields( \Elementor\Settings $settings ) {
        $settings->add_section( \Elementor\Settings::TAB_INTEGRATIONS, 'flodesk', [
            'callback' => function() {
                echo '<hr><h2>' . esc_html__( 'Flodesk', 'flodesk-for-elementor-pro' ) . '</h2>';
            },
            'fields' => [
                self::OPTION_NAME_API_KEY => [
                    'label' => __( 'API Key' ),
                    'field_args' => [
                        'type' => 'text',
                        'desc' => sprintf(
                            /* translators: 1: Link opening tag, 2: Link closing tag. */
                            esc_html__( 'To integrate with our forms you need an %1$sAPI Key%2$s.', 'flodesk-for-elementor-pro' ),
                            '<a href="https://help.flodesk.com/en/articles/8128775-about-api-keys" target="_blank">',
                            '</a>'
                        ),
                    ],
                ],
            ],
        ] );
    }

    public function __construct() {
        if ( is_admin() ) {
            add_action( 'elementor/admin/after_create_settings/' . \Elementor\Settings::PAGE_ID, [ $this, 'register_admin_fields' ], 100 );
        }
    }
}
