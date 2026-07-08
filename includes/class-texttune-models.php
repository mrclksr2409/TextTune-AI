<?php
/**
 * TextTune AI Model Catalog
 *
 * Fetches the currently available models from the provider APIs
 * (OpenAI / Anthropic), filters them to chat-capable models and
 * caches the result in transients.
 *
 * @package TextTune_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Guard against a duplicate copy of the plugin defining this class again.
if ( class_exists( 'TextTune_Models' ) ) {
    return;
}

class TextTune_Models {

    /**
     * How long a successfully fetched model list is cached.
     */
    const CACHE_TTL = 12 * HOUR_IN_SECONDS;

    /**
     * How long a failed fetch is remembered (negative cache), so a broken
     * key or network outage doesn't slow down every settings page load.
     */
    const ERROR_TTL = 15 * MINUTE_IN_SECONDS;

    /**
     * Get the available models for a provider.
     *
     * @param string $provider Provider key ('openai' or 'anthropic').
     * @param string $api_key  Decrypted API key.
     * @return array|WP_Error Map of model id => label, or WP_Error on failure.
     */
    public static function get_models( $provider, $api_key ) {
        if ( '' === (string) $api_key ) {
            return new WP_Error(
                'texttune_models_no_key',
                __( 'Kein API-Schlüssel gespeichert.', 'texttune-ai' )
            );
        }

        $key_hash = substr( md5( $api_key ), 0, 8 );

        $cached = get_transient( self::cache_key( $provider ) );
        if ( is_array( $cached )
            && isset( $cached['models'], $cached['key_hash'] )
            && $cached['key_hash'] === $key_hash
            && is_array( $cached['models'] )
            && ! empty( $cached['models'] )
        ) {
            return $cached['models'];
        }

        $failed = get_transient( self::cache_key( $provider ) . '_failed' );
        if ( is_string( $failed ) && '' !== $failed ) {
            return new WP_Error( 'texttune_models_fetch_failed', $failed );
        }

        if ( 'anthropic' === $provider ) {
            $models = self::fetch_anthropic_models( $api_key );
        } else {
            $models = self::fetch_openai_models( $api_key );
        }

        if ( is_wp_error( $models ) ) {
            set_transient( self::cache_key( $provider ) . '_failed', $models->get_error_message(), self::ERROR_TTL );
            return $models;
        }

        set_transient(
            self::cache_key( $provider ),
            array(
                'models'     => $models,
                'key_hash'   => $key_hash,
                'fetched_at' => time(),
            ),
            self::CACHE_TTL
        );

        return $models;
    }

    /**
     * Delete all cached model lists and failure markers.
     */
    public static function flush_cache() {
        foreach ( array( 'openai', 'anthropic' ) as $provider ) {
            delete_transient( self::cache_key( $provider ) );
            delete_transient( self::cache_key( $provider ) . '_failed' );
        }
    }

    /**
     * Get the last fetch error for a provider (from the negative cache).
     *
     * @param string $provider Provider key.
     * @return string|null Error message or null if the last fetch succeeded.
     */
    public static function get_last_error( $provider ) {
        $failed = get_transient( self::cache_key( $provider ) . '_failed' );
        return ( is_string( $failed ) && '' !== $failed ) ? $failed : null;
    }

    /**
     * Fetch the model list from OpenAI.
     *
     * @param string $api_key Decrypted API key.
     * @return array|WP_Error Map of model id => label.
     */
    private static function fetch_openai_models( $api_key ) {
        $response = wp_remote_get(
            'https://api.openai.com/v1/models',
            array(
                'timeout' => 15,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return new WP_Error(
                'texttune_models_request_failed',
                sprintf(
                    /* translators: %s: Error message */
                    __( 'OpenAI Modell-Abfrage fehlgeschlagen: %s', 'texttune-ai' ),
                    $response->get_error_message()
                )
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body        = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== $status_code ) {
            $message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Unbekannter Fehler', 'texttune-ai' );
            return new WP_Error(
                'texttune_models_api_error',
                sprintf(
                    /* translators: 1: HTTP status code, 2: Error message */
                    __( 'OpenAI API Fehler (%1$d): %2$s', 'texttune-ai' ),
                    $status_code,
                    $message
                )
            );
        }

        if ( ! isset( $body['data'] ) || ! is_array( $body['data'] ) ) {
            return new WP_Error(
                'texttune_models_invalid_response',
                __( 'Unerwartete Antwort von der OpenAI API.', 'texttune-ai' )
            );
        }

        $items = self::filter_openai_chat_models( $body['data'] );

        usort(
            $items,
            function ( $a, $b ) {
                $created_a = isset( $a['created'] ) ? (int) $a['created'] : 0;
                $created_b = isset( $b['created'] ) ? (int) $b['created'] : 0;
                return $created_b - $created_a;
            }
        );

        $models = array();
        foreach ( $items as $item ) {
            $models[ $item['id'] ] = self::openai_label( $item['id'] );
        }

        if ( empty( $models ) ) {
            return new WP_Error(
                'texttune_models_empty',
                __( 'Die OpenAI API hat keine passenden Chat-Modelle geliefert.', 'texttune-ai' )
            );
        }

        return $models;
    }

    /**
     * Fetch the model list from Anthropic.
     *
     * @param string $api_key Decrypted API key.
     * @return array|WP_Error Map of model id => label.
     */
    private static function fetch_anthropic_models( $api_key ) {
        // Anthropic's catalog is well below the 100-item page size,
        // so pagination (has_more) is intentionally ignored.
        $response = wp_remote_get(
            'https://api.anthropic.com/v1/models?limit=100',
            array(
                'timeout' => 15,
                'headers' => array(
                    'x-api-key'         => $api_key,
                    'anthropic-version' => '2023-06-01',
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return new WP_Error(
                'texttune_models_request_failed',
                sprintf(
                    /* translators: %s: Error message */
                    __( 'Anthropic Modell-Abfrage fehlgeschlagen: %s', 'texttune-ai' ),
                    $response->get_error_message()
                )
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body        = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== $status_code ) {
            $message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Unbekannter Fehler', 'texttune-ai' );
            return new WP_Error(
                'texttune_models_api_error',
                sprintf(
                    /* translators: 1: HTTP status code, 2: Error message */
                    __( 'Anthropic API Fehler (%1$d): %2$s', 'texttune-ai' ),
                    $status_code,
                    $message
                )
            );
        }

        if ( ! isset( $body['data'] ) || ! is_array( $body['data'] ) ) {
            return new WP_Error(
                'texttune_models_invalid_response',
                __( 'Unerwartete Antwort von der Anthropic API.', 'texttune-ai' )
            );
        }

        $items = array_filter(
            $body['data'],
            function ( $item ) {
                return isset( $item['id'] ) && is_string( $item['id'] );
            }
        );

        usort(
            $items,
            function ( $a, $b ) {
                $created_a = isset( $a['created_at'] ) ? strtotime( $a['created_at'] ) : 0;
                $created_b = isset( $b['created_at'] ) ? strtotime( $b['created_at'] ) : 0;
                return $created_b - $created_a;
            }
        );

        $models = array();
        foreach ( $items as $item ) {
            $models[ $item['id'] ] = isset( $item['display_name'] ) && '' !== $item['display_name']
                ? $item['display_name']
                : $item['id'];
        }

        if ( empty( $models ) ) {
            return new WP_Error(
                'texttune_models_empty',
                __( 'Die Anthropic API hat keine Modelle geliefert.', 'texttune-ai' )
            );
        }

        return $models;
    }

    /**
     * Filter the raw OpenAI model list down to chat-capable models.
     *
     * The OpenAI API exposes no capability flags, so this is a heuristic
     * based on well-known id prefixes and non-chat keywords.
     *
     * @param array $items Raw items from the API ('data' array).
     * @return array Filtered raw items.
     */
    private static function filter_openai_chat_models( array $items ) {
        $include = '/^(gpt-4|gpt-4o|gpt-4\.1|gpt-5|chatgpt-4o|o1|o3|o4)/';
        $exclude = '/(embed|whisper|tts|dall-e|audio|realtime|transcribe|moderation|search|instruct|davinci|babbage|codex|computer-use|image|deep-research)/';

        return array_values(
            array_filter(
                $items,
                function ( $item ) use ( $include, $exclude ) {
                    if ( ! isset( $item['id'] ) || ! is_string( $item['id'] ) ) {
                        return false;
                    }
                    return preg_match( $include, $item['id'] ) && ! preg_match( $exclude, $item['id'] );
                }
            )
        );
    }

    /**
     * Build a human-readable label from an OpenAI model id.
     *
     * @param string $id Model id (e.g. 'gpt-4o-mini').
     * @return string Label (e.g. 'GPT-4o Mini').
     */
    private static function openai_label( $id ) {
        $label = ucwords( str_replace( '-', ' ', $id ) );
        $label = preg_replace( '/^Gpt /', 'GPT-', $label );
        $label = preg_replace( '/^Chatgpt /', 'ChatGPT-', $label );
        return $label;
    }

    /**
     * Transient name for a provider's cached model list.
     *
     * @param string $provider Provider key.
     * @return string
     */
    private static function cache_key( $provider ) {
        return 'texttune_models_' . sanitize_key( $provider );
    }
}
