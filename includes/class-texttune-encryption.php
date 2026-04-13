<?php
/**
 * TextTune AI Encryption
 *
 * Handles encryption and decryption of API keys using AES-256-CBC.
 *
 * @package TextTune_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TextTune_Encryption {

    private static $cipher = 'aes-256-cbc';

    /**
     * Get the encryption key derived from WordPress salts.
     *
     * @return string
     */
    private static function get_key() {
        $key = '';
        if ( defined( 'AUTH_KEY' ) ) {
            $key .= AUTH_KEY;
        }
        if ( defined( 'SECURE_AUTH_KEY' ) ) {
            $key .= SECURE_AUTH_KEY;
        }
        if ( empty( $key ) ) {
            $key = 'texttune-ai-default-key';
        }
        return hash( 'sha256', $key, true );
    }

    /**
     * Encrypt a plain text string.
     *
     * @param string $plain_text The text to encrypt.
     * @return string The encrypted text (base64 encoded with IV prepended).
     */
    public static function encrypt( $plain_text ) {
        if ( empty( $plain_text ) ) {
            return '';
        }

        if ( ! function_exists( 'openssl_encrypt' ) ) {
            return base64_encode( $plain_text );
        }

        $key    = self::get_key();
        $iv_len = openssl_cipher_iv_length( self::$cipher );
        $iv     = openssl_random_pseudo_bytes( $iv_len );

        $encrypted = openssl_encrypt( $plain_text, self::$cipher, $key, OPENSSL_RAW_DATA, $iv );

        if ( false === $encrypted ) {
            return base64_encode( $plain_text );
        }

        return base64_encode( $iv . $encrypted );
    }

    /**
     * Decrypt an encrypted string.
     *
     * @param string $cipher_text The encrypted text (base64 encoded with IV prepended).
     * @return string The decrypted plain text.
     */
    public static function decrypt( $cipher_text ) {
        if ( empty( $cipher_text ) ) {
            return '';
        }

        if ( ! function_exists( 'openssl_decrypt' ) ) {
            return base64_decode( $cipher_text );
        }

        $key    = self::get_key();
        $data   = base64_decode( $cipher_text );
        $iv_len = openssl_cipher_iv_length( self::$cipher );

        if ( strlen( $data ) <= $iv_len ) {
            return '';
        }

        $iv        = substr( $data, 0, $iv_len );
        $encrypted = substr( $data, $iv_len );

        $decrypted = openssl_decrypt( $encrypted, self::$cipher, $key, OPENSSL_RAW_DATA, $iv );

        if ( false === $decrypted ) {
            return '';
        }

        return $decrypted;
    }

    /**
     * Check if OpenSSL encryption is available.
     *
     * @return bool
     */
    public static function is_available() {
        return function_exists( 'openssl_encrypt' ) && function_exists( 'openssl_decrypt' );
    }
}
