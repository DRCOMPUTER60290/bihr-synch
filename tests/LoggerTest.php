<?php
/**
 * Test cases for BihrWI_Logger.
 *
 * @runTestsInSeparateProcesses
 */

class LoggerTest extends PHPUnit\Framework\TestCase {

    private $log_dir;

    protected function setUp(): void {
        $this->log_dir = sys_get_temp_dir() . '/bihr-test-logs-' . uniqid();
        if ( ! is_dir( $this->log_dir ) ) {
            mkdir( $this->log_dir, 0777, true );
        }
    }

    protected function tearDown(): void {
        array_map( 'unlink', glob( $this->log_dir . '/*' ) ?: array() );
        rmdir( $this->log_dir );
    }

    private function createLogger(): BihrWI_Logger {
        $logger = new BihrWI_Logger();
        // Override log directory via reflection
        $ref = new ReflectionClass( $logger );
        $prop = $ref->getProperty( 'log_dir' );
        $prop->setAccessible( true );
        $prop->setValue( $logger, $this->log_dir );
        return $logger;
    }

    private function getCurrentLogFile( $logger ): string {
        $ref = new ReflectionClass( $logger );
        $prop = $ref->getProperty( 'current_log_file' );
        $prop->setAccessible( true );
        return $prop->getValue( $logger );
    }

    public function test_log_writes_message(): void {
        $logger = $this->createLogger();
        $logger->log( 'Test message' );

        $log_file = $this->getCurrentLogFile( $logger );
        $this->assertFileExists( $log_file );

        $content = file_get_contents( $log_file );
        $this->assertStringContainsString( 'Test message', $content );
    }

    public function test_log_append_only(): void {
        $logger = $this->createLogger();
        $logger->log( 'Message 1' );
        $logger->log( 'Message 2' );

        $content = file_get_contents( $this->getCurrentLogFile( $logger ) );
        $this->assertStringContainsString( 'Message 1', $content );
        $this->assertStringContainsString( 'Message 2', $content );

        $lines = array_filter( explode( "\n", trim( $content ) ) );
        $this->assertCount( 2, $lines );
    }

    public function test_log_includes_timestamp(): void {
        $logger = $this->createLogger();
        $logger->log( 'Timestamp test' );

        $content = file_get_contents( $this->getCurrentLogFile( $logger ) );
        $this->assertMatchesRegularExpression( '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $content );
    }
}
