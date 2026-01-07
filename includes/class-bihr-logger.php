<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BihrWI_Logger {

    private function get_wp_filesystem() {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;
        return $wp_filesystem;
    }

    public function log( $message ) {
        $wp_filesystem = $this->get_wp_filesystem();
        
        // Utilise le fuseau horaire du site WordPress
        $date = function_exists( 'wp_date' ) ? wp_date( 'Y-m-d H:i:s' ) : current_time( 'mysql' );
        $line = "[$date] $message" . PHP_EOL;

        if ( ! $wp_filesystem->exists( BIHRWI_LOG_FILE ) ) {
            $dir = dirname( BIHRWI_LOG_FILE );
            if ( ! $wp_filesystem->exists( $dir ) ) {
                wp_mkdir_p( $dir );
            }
            $wp_filesystem->put_contents( BIHRWI_LOG_FILE, '' );
        }

        $current_content = $wp_filesystem->get_contents( BIHRWI_LOG_FILE );
        if ( false === $current_content ) {
            $current_content = '';
        }
        $wp_filesystem->put_contents( BIHRWI_LOG_FILE, $current_content . $line );
    }

    public function get_log_contents() {
        $wp_filesystem = $this->get_wp_filesystem();
        
        if ( $wp_filesystem->exists( BIHRWI_LOG_FILE ) ) {
            return $wp_filesystem->get_contents( BIHRWI_LOG_FILE );
        }
        return '';
    }

    public function clear_logs() {
        $wp_filesystem = $this->get_wp_filesystem();
        
        if ( $wp_filesystem->exists( BIHRWI_LOG_FILE ) ) {
            $wp_filesystem->put_contents( BIHRWI_LOG_FILE, '' );
        }
    }
}
