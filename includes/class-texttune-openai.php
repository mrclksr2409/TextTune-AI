<?php
/**
 * TextTune AI OpenAI Client
 *
 * Handles communication with the OpenAI Chat Completions API.
 *
 * @package TextTune_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TextTune_OpenAI {

    /**
     * API endpoint.
     *
     * @var string
     */
    private $api_url = 'https://api.openai.com/v1/chat/completions';

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
     * Optimize content using OpenAI.
     *
     * @param string $content The text content to optimize.
     * @param string $prompt  The system prompt/instruction.
     * @param string $model   The model identifier (e.g. gpt-4o).
     * @return string|WP_Error The optimized content or WP_Error on failure.
     */
    public function optimize( $content, $prompt, $model ) {
        $body = array(
            'model'       => $model,
            'messages'    => array(
                array(
                    'role'    => 'system',
                    'content' => $prompt,
                ),
                array(
                    'role'    => 'user',
                    'content' => $content,
                ),
            ),
            'temperature' => 0.7,
        );

        $response = wp_remote_post(
            $this->api_url,
            array(
                'timeout' => 120,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode( $body ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return new WP_Error(
                'texttune_openai_request_failed',
                sprintf(
                    /* translators: %s: Error message */
                    __( 'OpenAI Anfrage fehlgeschlagen: %s', 'texttune-ai' ),
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
                'texttune_openai_api_error',
                sprintf(
                    /* translators: 1: HTTP status code, 2: Error message */
                    __( 'OpenAI API Fehler (%1$d): %2$s', 'texttune-ai' ),
                    $status_code,
                    $error_message
                )
            );
        }

        if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
            return new WP_Error(
                'texttune_openai_invalid_response',
                __( 'Unerwartete Antwort von OpenAI. Bitte versuche es erneut.', 'texttune-ai' )
            );
        }

        return trim( $data['choices'][0]['message']['content'] );
    }
}
