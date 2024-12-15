<?php
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/../vendor/autoload.php';

use Gemini;

/**
 * Google Gemini Client Class
 */
class QSS_Gemini_Client {
    private static $instance = null;
    private $client;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor for singleton
     */
    private function __construct() {}

    /**
     * Initialize the Gemini client
     */
    public function initialize($api_key) {
        if (empty($api_key)) {
            throw new Exception('Google Gemini API key is required');
        }

        try {
            $this->client = Gemini::client($api_key);
            return $this->client;
        } catch (Exception $e) {
            Plugin_Logger::log('Error initializing Gemini client: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get the initialized client
     */
    public function chat() {
        if (!$this->client) {
            throw new Exception('Gemini client not initialized');
        }
        return $this->client;
    }
}
