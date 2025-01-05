<?php 
if (!defined('ABSPATH')) {
    exit;
}

class Plugin_Logger {
    // Path to the log file
    private static $log_file_path;

    // Initializes the logger by setting the log file path
    public static function initialize($log_file_name = 'plugin.log') {
        // Set log file path in the plugin directory
        self::$log_file_path = plugin_dir_path(dirname( __FILE__ )) . $log_file_name;

        // Check if the log file already exists
        if (!file_exists(self::$log_file_path)) {
            // Create the log file and add an initial message
            file_put_contents(self::$log_file_path, "# Log file created on: " . date('Y-m-d H:i:s') . PHP_EOL);
        }
    }

    // Logs a message to the log file
    public static function log($message) {
        // Check if logging is enabled
        if (!get_option('qss_plugin_enable_logging', false)) {
            return;
        }

        // Convert array or object to a readable string format
        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }

        // Format the message with a timestamp
        $date = date('Y-m-d H:i:s');
        $log_message = sprintf("[%s] %s\n", $date, $message);

        // Append the formatted message to the log file
        file_put_contents(self::$log_file_path, $log_message, FILE_APPEND);
    }

    // Outputs the content of the log file
    public static function output_log() {
        if (file_exists(self::$log_file_path)) {
            // Set header for plain text content
            header('Content-Type: text/plain');
            // Read and output the log file content
            readfile(self::$log_file_path);
            exit;
        }
    }

    // Returns the contents of the log file as a string
    public static function get_log_contents() {
        if (file_exists(self::$log_file_path)) {
            // Return the file contents
            return file_get_contents(self::$log_file_path);
        }
        return '';
    }

    // Clears the log file and adds an initial message
    public static function clear_log() {
        // Get the directory of the current file
        $current_directory = dirname( __FILE__ );
        
        // Get the parent directory
        $parent_directory = dirname( $current_directory );


        // Check if the log file exists
        if ( file_exists( self::$log_file_path ) ) {
            // Open the file in write mode, which will truncate the file to zero length
            $file = fopen( self::$log_file_path, 'w' );

            // Check if the file was opened successfully
            if ( $file ) {
                fwrite( $file, 'Log cleared: ' . date( 'Y-m-d H:i:s' ) . PHP_EOL );
                fclose( $file ); // Close the file

                // Log file cleared successfully
                return true;
            } else {
               // Failed to open the log file
               return false;
            }
        } else {
            // Log file does not exist
            return false;
        }
    }
}

?>