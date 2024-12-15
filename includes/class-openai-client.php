<?php
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/../vendor/autoload.php';

use OpenAI;

class QSS_OpenAI_Client {
    private static $instance = null;
    private $client = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function initialize($api_key) {
        if (empty($api_key)) {
            throw new Exception('OpenAI API key is required');
        }

        try {
            $this->client = OpenAI::client($api_key);
            return $this->client;
        } catch (Exception $e) {
            Plugin_Logger::log('Error initializing OpenAI client: ' . $e->getMessage());
            throw $e;
        }
    }

    public function get_client() {
        if (!$this->client) {
            throw new Exception('OpenAI client not initialized');
        }
        return $this->client;
    }
}
