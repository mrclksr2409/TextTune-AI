<?php
/**
 * TextTune AI Anthropic Client
 *
 * Handles communication with the Anthropic Messages API.
 *
 * @package TextTune_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TextTune_Anthropic {

    /**
     * API endpoint.
     *
     * @var string
     */
    private $api_url = 'https://api.anthropic.com/v1/messages';

    /**
     * Decrypted API key.
     *
     * @var string
     */
    private $api_key;

    /**
     * Constructor.
     *
     * @param string $api_key Decrypted API key.
     */
    public function __construct( $api_key ) {
        $this->api_key = $api_key;
    }

    /**
     * Optimize content using Anthropic.
     *
     * @param string $content The text content to optimize.
     * @param string $prompt  The system prompt/instruction.
     * @param string $model   The model identifier (e.g. claude-sonnet-4-20250514).
     * @return string|WP_Error The optimized content or WP_Error on failure.
     */
    public function optimize( $content, $prompt, $model ) {
        $body = array(
            'model'      => $model,
            'max_tokens' => 8192,
            'system'     => $prompt,
            'messages'   => array(
                array(
                    'role'    => 'user',
                    'content' => $content,
                ),
            ),
        );

        $response = wp_remote_post(
            $this->api_url,
            array(
                'timeout' => 120,
                'headers' => array(
                    'x-api-key'         => $this->api_key,
                    'anthropic-version'  => '2023-06-01',
                    'content-type'       => 'application/json',
                ),
                'body'    => wp_json_encode( $body ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return new WP_Error(
                'texttune_anthropic_request_failed',
                sprintf(
                    /* translators: %s: Error message */
                    __( 'Anthropic Anfrage fehlgeschlagen: %s', 'texttune-ai' ),
                    $response->get_error_message()
                )
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body_raw    = wp_remote_retrieve_body( $response );
        $data        = json_decode( $body_raw, true );

        if ( $status_code < 200 || $status_code >= 300 ) {
            $error_message = isset( $data['error']['message'] )
                ? $data['error']['message']
                : __( 'Unbekannter API-Fehler', 'texttune-ai' );

            return new WP_Error(
                'texttune_anthropic_api_error',
                sprintf(
                    /* translators: 1: HTTP status code, 2: Error message */
                    __( 'Anthropic API Fehler (%1$d): %2$s', 'texttune-ai' ),
                    $status_code,
                    $error_message
                )
            );
        }

        if ( ! isset( $data['content'][0]['text'] ) ) {
            return new WP_Error(
                'texttune_anthropic_invalid_response',
                __( 'Unerwartete Antwort von Anthropic. Bitte versuche es erneut.', 'texttune-ai' )
            );
        }

        return trim( $data['content'][0]['text'] );
    }
}
