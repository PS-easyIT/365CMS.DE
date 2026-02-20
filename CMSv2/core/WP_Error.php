<?php
/**
 * WP_Error Class - Simple Error Handling
 * 
 * WordPress-kompatible Fehlerklasse für CMSv2
 * 
 * @package CMSv2\Core
 * @version 2.0.0
 */

declare(strict_types=1);

namespace CMS {

    if (!class_exists('\CMS\WP_Error')) {
        
        /**
         * WordPress Error Class
         * 
         * Container for checking for errors and error messages
         */
        class WP_Error {
            
            /**
             * Fehler-Code
             */
            private $code = '';
            
            /**
             * Fehlermeldung
             */
            private $message = '';
            
            /**
             * Zusätzliche Fehler-Daten
             */
            private $data = [];
            
            /**
             * Constructor
             * 
             * @param string $code Fehlercode
             * @param string $message Fehlermeldung
             * @param mixed $data Zusätzliche Daten
             */
            public function __construct($code = '', $message = '', $data = []) {
                if (!empty($code)) {
                    $this->code = $code;
                    $this->message = $message;
                    $this->data = (array)$data;
                }
            }
            
            /**
             * Fehlercode abrufen
             * 
             * @return string
             */
            public function get_error_code(): string {
                return $this->code;
            }
            
            /**
             * Fehlermeldung abrufen
             * 
             * @return string
             */
            public function get_error_message(): string {
                return $this->message;
            }
            
            /**
             * Fehler-Daten abrufen
             * 
             * @return array
             */
            public function get_error_data(): array {
                return $this->data;
            }
            
            /**
             * Füge Fehler hinzu
             *
             * @param string $code Fehlercode
             * @param string $message Fehlermeldung
             * @param mixed $data Zusätzliche Daten
             */
            public function add(string $code, string $message, mixed $data = []): void {
                $this->code = $code;
                $this->message = $message;
                $this->data = (array)$data;
            }
        }
    }
}

namespace {
    if (!function_exists('is_wp_error')) {
        /**
         * Check if variable is a WP_Error object
         * 
         * @param mixed $thing Variable to check
         * @return bool
         */
        function is_wp_error($thing) {
            return ($thing instanceof \CMS\WP_Error);
        }
    }
}

