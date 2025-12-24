<?php
/*
Plugin Name: Özel JSONPlaceholder Eklentisi
Description: JSONPlaceholder API'sinden veri çeken ve WordPress'te gösteren özel bir eklenti.
Version: 1.1
Author: Burak
Text Domain: custom-jsonplaceholder
*/

defined( 'ABSPATH' ) or die( 'Erişim Engellendi' );

class Custom_JSONPlaceholder_Plugin {

    /**
     * Slug for the endpoint.
     *
     * @var string
     */
    private $endpoint = 'custom-endpoint';

    public function __construct() {
        add_action( 'init', array( $this, 'add_custom_endpoint' ) );
        add_filter( 'query_vars', array( $this, 'register_query_var' ) );
        add_action( 'template_redirect', array( $this, 'handle_custom_endpoint_request' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_get_user_details', array( $this, 'get_user_details_callback' ) );
        add_action( 'wp_ajax_nopriv_get_user_details', array( $this, 'get_user_details_callback' ) );

        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
    }

    public function activate() {
        $this->add_custom_endpoint();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function add_custom_endpoint() {
        add_rewrite_rule( '^' . $this->endpoint . '/?$', 'index.php?custom_endpoint=1', 'top' );
        add_rewrite_tag( '%custom_endpoint%', '1' );
    }

    public function register_query_var( $vars ) {
        $vars[] = 'custom_endpoint';
        return $vars;
    }

    public function handle_custom_endpoint_request() {
        if ( get_query_var( 'custom_endpoint' ) ) {
            status_header( 200 );
            $this->render_user_table();
            exit;
        }
    }

    public function render_user_table() {
        $cache_key = 'custom_jsonplaceholder_users';
        $users     = get_transient( $cache_key );

        if ( false === $users ) {
            $response = wp_safe_remote_get(
                'https://jsonplaceholder.typicode.com/users',
                array(
                    'timeout' => 8,
                )
            );

            if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
                wp_die( esc_html__( 'Kullanıcılar alınamadı.', 'custom-jsonplaceholder' ) );
            }

            $users = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( ! is_array( $users ) ) {
                wp_die( esc_html__( 'Geçersiz API yanıtı.', 'custom-jsonplaceholder' ) );
            }

            set_transient( $cache_key, $users, HOUR_IN_SECONDS );
        }

        get_header();

        echo '<div class="custom-jsonplaceholder" style="display:flex;gap:24px;">';
        echo '<table style="width:70%;">';
        echo '<thead><tr><th>' . esc_html__( 'ID', 'custom-jsonplaceholder' ) . '</th><th>' . esc_html__( 'Name', 'custom-jsonplaceholder' ) . '</th><th>' . esc_html__( 'Username', 'custom-jsonplaceholder' ) . '</th></tr></thead><tbody>';

        foreach ( $users as $user ) {
            $id       = isset( $user['id'] ) ? absint( $user['id'] ) : 0;
            $name     = isset( $user['name'] ) ? $user['name'] : '';
            $username = isset( $user['username'] ) ? $user['username'] : '';

            if ( 0 === $id ) {
                continue;
            }

            echo '<tr>';
            echo '<td>' . esc_html( $id ) . '</td>';
            echo '<td>' . esc_html( $name ) . '</td>';
            echo '<td><a href="#" class="user-link" data-user-id="' . esc_attr( $id ) . '">' . esc_html( $username ) . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '<div id="user-details" style="width:30%"></div>';
        echo '</div>';

        get_footer();
    }

    public function enqueue_scripts() {
        // Only load on our endpoint to avoid site-wide enqueue.
        if ( ! get_query_var( 'custom_endpoint' ) ) {
            return;
        }

        wp_enqueue_script(
            'custom-jsonplaceholder-scripts',
            plugin_dir_url( __FILE__ ) . 'scripts.js',
            array( 'jquery' ),
            '1.1',
            true
        );

        wp_localize_script(
            'custom-jsonplaceholder-scripts',
            'custom_jsonplaceholder_ajax',
            array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'custom_jsonplaceholder_nonce' ),
            )
        );
    }

    public function get_user_details_callback() {
        check_ajax_referer( 'custom_jsonplaceholder_nonce', 'nonce' );

        $user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

        if ( $user_id <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Geçersiz kullanıcı.', 'custom-jsonplaceholder' ) ), 400 );
        }

        $cache_key   = 'custom_jsonplaceholder_user_' . $user_id;
        $user_details = get_transient( $cache_key );

        if ( false === $user_details ) {
            $response = wp_safe_remote_get(
                'https://jsonplaceholder.typicode.com/users/' . $user_id,
                array(
                    'timeout' => 8,
                )
            );

            if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
                wp_send_json_error( array( 'message' => __( 'Kullanıcı detayları alınamadı.', 'custom-jsonplaceholder' ) ), 500 );
            }

            $user_details = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( ! is_array( $user_details ) ) {
                wp_send_json_error( array( 'message' => __( 'Geçersiz API yanıtı.', 'custom-jsonplaceholder' ) ), 500 );
            }

            set_transient( $cache_key, $user_details, HOUR_IN_SECONDS );
        }

        $safe_details = array(
            'id'       => $user_details['id'] ?? '',
            'name'     => $user_details['name'] ?? '',
            'username' => $user_details['username'] ?? '',
            'email'    => $user_details['email'] ?? '',
            'phone'    => $user_details['phone'] ?? '',
            'website'  => $user_details['website'] ?? '',
            'company'  => isset( $user_details['company']['name'] ) ? $user_details['company']['name'] : '',
            'address'  => array(),
        );

        if ( isset( $user_details['address'] ) && is_array( $user_details['address'] ) ) {
            foreach ( $user_details['address'] as $key => $value ) {
                // Skip nested objects/arrays (e.g., geo) that are not scalar.
                if ( is_scalar( $value ) ) {
                    $safe_details['address'][ $key ] = $value;
                }
            }
        }

        wp_send_json_success( $safe_details );
    }
}

new Custom_JSONPlaceholder_Plugin();