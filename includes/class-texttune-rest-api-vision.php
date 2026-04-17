<?php
/**
 * TextTune AI Vision REST API
 *
 * Registers the /analyze-image endpoint used by the media-library integration
 * to generate alt/title/caption/description for image attachments.
 *
 * @package TextTune_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TextTune_REST_API_Vision {

    const DEFAULT_PROMPT = "Du bist ein Bildanalyse-Assistent für eine WordPress-Mediathek. Analysiere das bereitgestellte Bild und erzeuge Metadaten in der Sprache: {language}.\n\nDateiname (nur als Kontext, ignoriere generische Kamera-Namen wie IMG_1234):\n{filename}\n\nGib ausschließlich ein einzelnes JSON-Objekt mit genau diesen vier Schlüsseln zurück:\n- \"alt\":         kurzer beschreibender Alt-Text (max. 125 Zeichen, kein \"Bild von ...\")\n- \"title\":       prägnanter Titel (max. 60 Zeichen)\n- \"caption\":     ein Satz als Bildunterschrift\n- \"description\": 2-4 Sätze ausführliche Beschreibung\n\nKeine Einleitung, kein Markdown, keine Code-Fences. Nur das JSON-Objekt.";

    const ALLOWED_FIELDS = array( 'alt', 'title', 'caption', 'description' );

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register REST API routes.
     */
    public function register_routes() {
        register_rest_route(
            'texttune/v1',
            '/analyze-image',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'handle_analyze' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'attachment_id'  => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'fields'         => array(
                        'required' => false,
                        'type'     => 'array',
                    ),
                    'save'           => array(
                        'required' => false,
                        'type'     => 'boolean',
                        'default'  => false,
                    ),
                    'overwrite_map'  => array(
                        'required' => false,
                        'type'     => 'object',
                    ),
                    'locale'         => array(
                        'required' => false,
                        'type'     => 'string',
                    ),
                ),
            )
        );
    }

    /**
     * Permission check: user must be able to upload files AND edit this attachment.
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function check_permission( $request ) {
        if ( ! current_user_can( 'upload_files' ) ) {
            return new WP_Error(
                'texttune_forbidden',
                __( 'Du hast keine Berechtigung für diese Aktion.', 'texttune-ai' ),
                array( 'status' => 403 )
            );
        }
        $attachment_id = (int) $request->get_param( 'attachment_id' );
        if ( $attachment_id && ! current_user_can( 'edit_post', $attachment_id ) ) {
            return new WP_Error(
                'texttune_forbidden',
                __( 'Du hast keine Berechtigung, diesen Anhang zu bearbeiten.', 'texttune-ai' ),
                array( 'status' => 403 )
            );
        }
        return true;
    }

    /**
     * Handle the analyze-image request.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function handle_analyze( $request ) {
        $attachment_id = (int) $request->get_param( 'attachment_id' );
        if ( ! $attachment_id ) {
            return new WP_Error(
                'texttune_not_an_image',
                __( 'Kein Anhang angegeben.', 'texttune-ai' ),
                array( 'status' => 400 )
            );
        }

        $fields_input = $request->get_param( 'fields' );
        $fields       = self::sanitize_fields( $fields_input );

        $settings = get_option( 'texttune_ai_settings', array() );
        $provider = isset( $settings['provider'] ) ? $settings['provider'] : 'openai';

        $api_key_encrypted = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
        $api_key           = TextTune_Encryption::decrypt( $api_key_encrypted );

        if ( empty( $api_key ) ) {
            return new WP_Error(
                'texttune_no_api_key',
                __( 'Kein API-Schlüssel konfiguriert. Bitte gehe zu Einstellungen → TextTune AI.', 'texttune-ai' ),
                array( 'status' => 400 )
            );
        }

        // Choose vision model (override) or fall back to text model.
        $vision_settings = isset( $settings['vision'] ) && is_array( $settings['vision'] ) ? $settings['vision'] : array();
        $model           = ! empty( $vision_settings['model'] )
            ? $vision_settings['model']
            : ( isset( $settings['model'] ) ? $settings['model'] : 'gpt-4o' );

        $max_edge = isset( $vision_settings['max_edge'] ) ? (int) $vision_settings['max_edge'] : 0;

        // Build image payload (fetch + optionally downscale + base64).
        $payload = TextTune_Image_Analyzer::get_image_payload( $attachment_id, $max_edge );
        if ( is_wp_error( $payload ) ) {
            return $payload;
        }

        /**
         * Filter the image payload before sending to the provider.
         *
         * @param array $payload      [ 'mime', 'base64', 'filename' ]
         * @param int   $attachment_id
         */
        $payload = apply_filters( 'texttune_ai_pre_analyze_image', $payload, $attachment_id );

        // Build prompt.
        $prompt_template = ! empty( $vision_settings['prompt'] )
            ? $vision_settings['prompt']
            : self::DEFAULT_PROMPT;

        $locale_param = (string) $request->get_param( 'locale' );
        $locale       = $locale_param ? $locale_param : determine_locale();
        $language     = TextTune_Image_Analyzer::locale_to_language( $locale );

        $prompt = strtr(
            $prompt_template,
            array(
                '{language}' => $language,
                '{locale}'   => $locale,
                '{filename}' => isset( $payload['filename'] ) ? $payload['filename'] : '',
                '{fields}'   => implode( ', ', $fields ),
            )
        );

        /**
         * Filter the vision prompt before sending to the provider.
         *
         * @param string $prompt
         * @param int    $attachment_id
         * @param array  $fields
         */
        $prompt = apply_filters( 'texttune_ai_image_prompt', $prompt, $attachment_id, $fields );

        // Dispatch to provider.
        $client = $this->create_provider( $provider, $api_key );
        $raw    = $client->analyze_image(
            $payload['base64'],
            $payload['mime'],
            $prompt,
            $model
        );

        if ( is_wp_error( $raw ) ) {
            $status = 502;
            if ( 'texttune_vision_rate_limited' === $raw->get_error_code() ) {
                $status = 429;
            } elseif ( 'texttune_no_api_key' === $raw->get_error_code() ) {
                $status = 400;
            }
            return new WP_Error(
                $raw->get_error_code(),
                $raw->get_error_message(),
                array( 'status' => $status )
            );
        }

        $parsed = TextTune_Image_Analyzer::parse_response( $raw );
        if ( is_wp_error( $parsed ) ) {
            return $parsed;
        }

        /**
         * Filter parsed fields before returning / saving.
         *
         * @param array  $parsed
         * @param int    $attachment_id
         * @param string $raw
         */
        $parsed = apply_filters( 'texttune_ai_post_analyze_image', $parsed, $attachment_id, $raw );

        $current = TextTune_Image_Analyzer::get_current_fields( $attachment_id );
        $generated = array(
            'alt'         => in_array( 'alt', $fields, true ) ? (string) ( $parsed['alt'] ?? '' ) : '',
            'title'       => in_array( 'title', $fields, true ) ? (string) ( $parsed['title'] ?? '' ) : '',
            'caption'     => in_array( 'caption', $fields, true ) ? (string) ( $parsed['caption'] ?? '' ) : '',
            'description' => in_array( 'description', $fields, true ) ? (string) ( $parsed['description'] ?? '' ) : '',
        );

        $save = (bool) $request->get_param( 'save' );
        $saved = false;
        $applied = array();

        if ( $save ) {
            $overwrite_map = $request->get_param( 'overwrite_map' );
            if ( ! is_array( $overwrite_map ) ) {
                $overwrite_map = array();
            }
            $applied = $this->apply_fields( $attachment_id, $generated, $current, $overwrite_map );
            $saved   = ! empty( $applied );
        }

        /**
         * Fires after a vision analysis completes.
         *
         * @param int   $attachment_id
         * @param array $generated
         * @param bool  $saved
         */
        do_action( 'texttune_ai_image_analyzed', $attachment_id, $generated, $saved );

        return rest_ensure_response(
            array(
                'success'       => true,
                'attachment_id' => $attachment_id,
                'generated'     => $generated,
                'current'       => $current,
                'saved'         => $saved,
                'applied'       => $applied,
            )
        );
    }

    /**
     * Apply generated field values to the attachment.
     *
     * @param int   $attachment_id
     * @param array $generated     generated values (already filtered to enabled fields)
     * @param array $current       current values on the attachment
     * @param array $overwrite_map { field => 'overwrite'|'keep' }
     * @return array list of fields that were actually written
     */
    private function apply_fields( $attachment_id, $generated, $current, $overwrite_map ) {
        $applied    = array();
        $post_data  = array();

        foreach ( self::ALLOWED_FIELDS as $field ) {
            $new = isset( $generated[ $field ] ) ? (string) $generated[ $field ] : '';
            if ( '' === $new ) {
                continue;
            }
            $decision    = isset( $overwrite_map[ $field ] ) ? (string) $overwrite_map[ $field ] : '';
            $is_empty    = ( '' === trim( (string) ( $current[ $field ] ?? '' ) ) );
            $overwrite   = ( 'overwrite' === $decision ) || ( 'keep' !== $decision && $is_empty );

            if ( ! $overwrite ) {
                continue;
            }

            /**
             * Filter the final value for a specific attachment field before saving.
             *
             * @param string $new
             * @param string $field
             * @param int    $attachment_id
             */
            $new = apply_filters( 'texttune_ai_image_field_value', $new, $field, $attachment_id );

            switch ( $field ) {
                case 'alt':
                    update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $new ) );
                    $applied[] = 'alt';
                    break;
                case 'title':
                    $post_data['post_title'] = sanitize_text_field( $new );
                    $applied[]               = 'title';
                    break;
                case 'caption':
                    $post_data['post_excerpt'] = sanitize_text_field( $new );
                    $applied[]                 = 'caption';
                    break;
                case 'description':
                    $post_data['post_content'] = wp_kses_post( $new );
                    $applied[]                 = 'description';
                    break;
            }
        }

        if ( ! empty( $post_data ) ) {
            $post_data['ID'] = $attachment_id;
            wp_update_post( $post_data );
        }

        return $applied;
    }

    /**
     * Normalize and validate the list of target fields.
     *
     * @param mixed $input
     * @return array
     */
    private static function sanitize_fields( $input ) {
        if ( ! is_array( $input ) || empty( $input ) ) {
            return self::ALLOWED_FIELDS;
        }
        $clean = array();
        foreach ( $input as $value ) {
            $value = sanitize_key( $value );
            if ( in_array( $value, self::ALLOWED_FIELDS, true ) ) {
                $clean[] = $value;
            }
        }
        return empty( $clean ) ? self::ALLOWED_FIELDS : array_values( array_unique( $clean ) );
    }

    /**
     * Create the provider instance.
     *
     * @param string $provider
     * @param string $api_key
     * @return TextTune_OpenAI|TextTune_Anthropic
     */
    private function create_provider( $provider, $api_key ) {
        switch ( $provider ) {
            case 'anthropic':
                return new TextTune_Anthropic( $api_key );
            case 'openai':
            default:
                return new TextTune_OpenAI( $api_key );
        }
    }
}
