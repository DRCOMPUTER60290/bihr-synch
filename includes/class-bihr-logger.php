<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BihrWI_Logger {

    const LEVEL_DEBUG = 0;
    const LEVEL_INFO  = 1;
    const LEVEL_WARN  = 2;
    const LEVEL_ERROR = 3;

    private $min_level = self::LEVEL_DEBUG;
    private $silent    = false;

    public function set_silent( bool $silent ): void {
        $this->silent = $silent;
    }

    public function set_min_level( int $level ): void {
        $this->min_level = $level;
    }

    public function error( string $message ): void {
        $this->write( self::LEVEL_ERROR, '[ERROR] ' . $message );
    }

    public function warning( string $message ): void {
        $this->write( self::LEVEL_WARN, '[WARN] ' . $message );
    }

    public function info( string $message ): void {
        $this->write( self::LEVEL_INFO, $message );
    }

    public function debug( string $message ): void {
        $this->write( self::LEVEL_DEBUG, '[DEBUG] ' . $message );
    }

    private function write( int $level, string $message ): void {
        if ( $this->silent || $level < $this->min_level ) {
            return;
        }
        $this->log( $message );
    }

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
