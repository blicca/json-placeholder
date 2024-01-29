<?php
/*
Plugin Name: Özel JSONPlaceholder Eklentisi
Description: JSONPlaceholder API'sinden veri çeken ve WordPress'te gösteren özel bir eklenti.
Version: 1.0
Author: Burak
*/

defined( 'ABSPATH' ) or die( 'Erişim Engellendi' );

class Custom_JSONPlaceholder_Plugin {

    public function __construct() {
        add_action( 'init', array( $this, 'add_custom_endpoint' ) );
        add_action( 'template_redirect', array( $this, 'handle_custom_endpoint_request' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_get_user_details', array( $this, 'get_user_details_callback' ) );
        add_action( 'wp_ajax_nopriv_get_user_details', array( $this, 'get_user_details_callback' ) );
    }

    public function add_custom_endpoint() {
        add_rewrite_rule( '^custom-endpoint/?$', 'index.php?custom_endpoint=1', 'top' );
        add_rewrite_tag( '%custom_endpoint%', '1' );
    }

    public function handle_custom_endpoint_request() {
        if ( get_query_var( 'custom_endpoint' ) ) {
            $this->render_user_table();
            exit;
        }
    }

    public function render_user_table() {
        // Önbellek anahtarı
        $cache_key = 'custom_jsonplaceholder_users';

        // Önbellekten verileri kontrol et
        $cached_users = get_transient( $cache_key );      
        
        if ( ! $cached_users ) {  
            // API isteğini yap ve veriyi al
            $response = wp_remote_get( 'https://jsonplaceholder.typicode.com/users' );

            if ( is_wp_error( $response ) ) {
                return;
            }

            $users = json_decode( wp_remote_retrieve_body( $response ), true );

            // Verileri önbelleğe al
            set_transient( $cache_key, $users, DAY_IN_SECONDS ); // 24 saatlik önbellekleme

            $cached_users = $users;   
        }
        // Var sayılan WordPress Header 
        get_header();

        // Kullanıcı Bilgi Tablosu
        echo '<div style="display:flex;">';
        echo '<table style="width:70%;">';
        echo '<tr><th>ID</th><th>Name</th><th>Username</th></tr>';

        foreach ( $cached_users as $user ) {
            echo '<tr>';
            echo '<td>' . esc_html( $user['id'] ) . '</td>';
            echo '<td>' . esc_html( $user['name'] ) . '</td>';
            echo '<td><a href="#" class="user-link" data-user-id="' . esc_attr( $user['id'] ) . '">' . esc_html( $user['username'] ) . '</a></td>';
            echo '</tr>';
        }

        echo '</table>';
        echo '<div id="user-details" style="width:30%"></div>'; // AJAX ile yüklenecek kullanıcı detayları bölümü
        echo '</div>';

        // Varsayılan WordPress Footer
        get_footer();
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'custom-jsonplaceholder-scripts', plugin_dir_url( __FILE__ ) . 'scripts.js', array( 'jquery' ), '1.0', true );
        wp_localize_script( 'custom-jsonplaceholder-scripts', 'custom_jsonplaceholder_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
    }

    public function get_user_details_callback() {

        // Önbellek anahtarı
        $cache_key = 'custom_jsonplaceholder_user_' . $_GET['user_id'];        

        // Önbellekten verileri kontrol et
        $cached_user_details = get_transient( $cache_key );        

        if ( ! $cached_user_details ) {
            $user_id = $_GET['user_id'];

            $response = wp_remote_get( 'https://jsonplaceholder.typicode.com/users/' . $user_id );

            if ( is_wp_error( $response ) ) {
                echo 'Kullanıcı detayları alınamadı.';
                die();
            }

            $user_details = json_decode( wp_remote_retrieve_body( $response ), true );

            // Verileri önbelleğe al
            set_transient( $cache_key, $user_details, DAY_IN_SECONDS ); // 24 saatlik önbellekleme

            $cached_user_details = $user_details;
        }

        echo '<p><strong>ID:</strong> ' . esc_html( $cached_user_details['id'] ) . '</p>';
        echo '<p><strong>Name:</strong> ' . esc_html( $cached_user_details['name'] ) . '</p>';
        echo '<p><strong>Username:</strong> ' . esc_html( $cached_user_details['username'] ) . '</p>';
        echo '<p><strong>Email:</strong> ' . esc_html( $cached_user_details['email'] ) . '</p>';
        echo '<p><strong>Phone:</strong> ' . esc_html( $cached_user_details['phone'] ) . '</p>';
        echo '<p><strong>Address:</strong></p>';
        echo '<ul>';

        foreach ( $cached_user_details['address'] as $key => $value ) {
            echo '<li><strong>' . ucwords( str_replace( '_', ' ', $key ) ) . ':</strong> ' . esc_html( $value ) . '</li>';
        }

        echo '</ul>';        
        echo '<p><strong>Website:</strong> ' . esc_html( $cached_user_details['website'] ) . '</p>';
        echo '<p><strong>Company:</strong> ' . esc_html( $cached_user_details['company']['name']) . '</p>';

        die();
    }

}

new Custom_JSONPlaceholder_Plugin();
