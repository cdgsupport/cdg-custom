<?php
/**
 * Logger Class
 *
 * Simple PSR-3 style logging for the child theme.
 *
 * @package CDG_Custom
 * @since 2.0.0
 */

declare(strict_types=1);

/**
 * CDG_Logger provides simple logging functionality
 */
class CDG_Logger
{
    /**
     * Logger instance
     *
     * @var CDG_Logger|null
     */
    private static ?CDG_Logger $instance = null;

    /**
     * Log levels
     */
    private const LEVELS = [
        'debug'   => 0,
        'info'    => 1,
        'warning' => 2,
        'error'   => 3,
    ];

    /**
     * Current log level
     *
     * @var string
     */
    private string $log_level;

    /**
     * Get logger instance
     *
     * @return CDG_Logger
     */
    public static function get_instance(): CDG_Logger
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->log_level = defined('CDG_LOG_LEVEL') ? CDG_LOG_LEVEL : 'error';
    }

    /**
     * Log debug message
     *
     * @param string $message Message
     * @param array<string, mixed> $context Context
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Log info message
     *
     * @param string $message Message
     * @param array<string, mixed> $context Context
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * Log warning message
     *
     * @param string $message Message
     * @param array<string, mixed> $context Context
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Log error message
     *
     * @param string $message Message
     * @param array<string, mixed> $context Context
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * Log message
     *
     * @param string $level Log level
     * @param string $message Message
     * @param array<string, mixed> $context Context
     * @return void
     */
    private function log(string $level, string $message, array $context = []): void
    {
        // Check if this level should be logged
        if (self::LEVELS[$level] < self::LEVELS[$this->log_level]) {
            return;
        }

        // Format message
        $formatted = sprintf(
            '[CDG Theme] [%s] %s',
            strtoupper($level),
            $this->interpolate($message, $context)
        );

        // Add context if present
        if (!empty($context)) {
            $formatted .= ' | Context: ' . wp_json_encode($context);
        }

        // Log to error log
        error_log($formatted);
    }

    /**
     * Interpolate context values into message
     *
     * @param string $message Message
     * @param array<string, mixed> $context Context
     * @return string
     */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (is_string($val) || is_numeric($val)) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        return strtr($message, $replace);
    }

    /**
     * Cleanup old log files
     *
     * @return void
     */
    public function cleanup_old_logs(): void
    {
        // This method exists for compatibility
        // Actual log rotation is handled by the server
    }
}
