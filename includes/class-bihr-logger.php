<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BIHRWI_LOG_MAX_SIZE', 50 * 1024 * 1024 ); // 50 Mo

class BihrWI_Logger {

    const LEVEL_DEBUG = 0;
    const LEVEL_INFO  = 1;
    const LEVEL_WARN  = 2;
    const LEVEL_ERROR = 3;

    private $min_level = self::LEVEL_DEBUG;
    private $silent    = false;
    private $log_file;
    private $buffer = array();
    private $buffering = false;

    public function __construct( ?string $log_file = null ) {
        $this->log_file = $log_file ?? BIHRWI_LOG_FILE;
    }

    public function set_silent( bool $silent ): void {
        $this->silent = $silent;
    }

    public function set_min_level( int $level ): void {
        $this->min_level = $level;
    }

    public function enable_buffer(): void {
        $this->buffer    = array();
        $this->buffering = true;
    }

    public function disable_buffer(): void {
        $this->buffering = false;
    }

    public function flush_buffer(): void {
        if ( empty( $this->buffer ) ) {
            return;
        }
        $lines = implode( '', $this->buffer );
        $this->buffer = array();

        if ( ! $this->ensure_log_dir() ) {
            return;
        }
        $this->rotate_if_needed();
        file_put_contents( $this->log_file, $lines, FILE_APPEND | LOCK_EX );
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

    private function ensure_log_dir(): bool {
        $dir = dirname( $this->log_file );
        if ( ! is_dir( $dir ) ) {
            wp_mkdir_p( $dir );
        }
        return is_dir( $dir );
    }

    public function log( $message ): void {
        $date = function_exists( 'wp_date' ) ? wp_date( 'Y-m-d H:i:s' ) : current_time( 'mysql' );
        $line = "[$date] $message" . PHP_EOL;

        if ( $this->buffering ) {
            $this->buffer[] = $line;
            if ( count( $this->buffer ) >= 200 ) {
                $this->flush_buffer();
            }
            return;
        }

        if ( ! $this->ensure_log_dir() ) {
            return;
        }

        $this->rotate_if_needed();

        file_put_contents( $this->log_file, $line, FILE_APPEND | LOCK_EX );
    }

    private function rotate_if_needed(): void {
        if ( ! file_exists( $this->log_file ) ) {
            return;
        }

        if ( filesize( $this->log_file ) < BIHRWI_LOG_MAX_SIZE ) {
            return;
        }

        $rotated = $this->log_file . '.' . gmdate( 'Ymd-His' ) . '.rotated';
        rename( $this->log_file, $rotated );

        $pattern = dirname( $this->log_file ) . DIRECTORY_SEPARATOR
                 . pathinfo( $this->log_file, PATHINFO_FILENAME )
                 . '.????-??-??-*.*';

        $rotated_files = glob( $pattern );
        if ( is_array( $rotated_files ) && count( $rotated_files ) > 5 ) {
            usort( $rotated_files, 'strcmp' );
            $to_delete = array_slice( $rotated_files, 0, count( $rotated_files ) - 5 );
            foreach ( $to_delete as $old ) {
                @unlink( $old );
            }
        }
    }

    public function get_log_contents(): string {
        if ( ! file_exists( $this->log_file ) ) {
            return '';
        }
        $content = file_get_contents( $this->log_file );
        return false === $content ? '' : $content;
    }

    public function clear_logs(): void {
        if ( file_exists( $this->log_file ) ) {
            file_put_contents( $this->log_file, '' );
        }
    }
}
