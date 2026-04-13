<?php
/**
 * TextTune AI REST API
 *
 * Registers and handles the REST API endpoint for text optimization.
 *
 * @package TextTune_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TextTune_REST_API {

    /**
     * Default prompt when no post-type-specific prompt is configured.
     */
    const DEFAULT_PROMPT = 'Optimiere den folgenden Text. Verbessere Grammatik, Stil und Lesbarkeit. Behalte den Inhalt, die Bedeutung und die HTML-Formatierung bei. Gib nur den optimierten Text zurück, ohne zusätzliche Erklärungen.';

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
            '/optimize',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'handle_optimize' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'content'   => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => function ( $value ) {
                            return wp_kses_post( $value );
                        },
                    ),
                    'post_type' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_key',
                    ),
                ),
            )
        );
    }

    /**
     * Check if the current user has permission to optimize posts.
     *
     * @return bool|WP_Error
     */
    public function check_permission() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return new WP_Error(
                'texttune_forbidden',
                __( 'Du hast keine Berechtigung für diese Aktion.', 'texttune-ai' ),
                array( 'status' => 403 )
            );
        }
        return true;
    }

    /**
     * Handle the optimize request.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error
     */
    public function handle_optimize( $request ) {
        $content   = $request->get_param( 'content' );
        $post_type = $request->get_param( 'post_type' );

        if ( empty( $content ) ) {
            return new WP_Error(
                'texttune_empty_content',
                __( 'Der Inhalt darf nicht leer sein.', 'texttune-ai' ),
                array( 'status' => 400 )
            );
        }

        // Load settings.
        $settings = get_option( 'texttune_ai_settings', array() );
        $provider = isset( $settings['provider'] ) ? $settings['provider'] : 'openai';
        $model    = isset( $settings['model'] ) ? $settings['model'] : 'gpt-4o';

        // Decrypt API key.
        $api_key_encrypted = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
        $api_key           = TextTune_Encryption::decrypt( $api_key_encrypted );

        if ( empty( $api_key ) ) {
            return new WP_Error(
                'texttune_no_api_key',
                __( 'Kein API-Schlüssel konfiguriert. Bitte gehe zu Einstellungen → TextTune AI.', 'texttune-ai' ),
                array( 'status' => 400 )
            );
        }

        // Get prompt for this post type.
        $prompts = isset( $settings['prompts'] ) ? $settings['prompts'] : array();
        $prompt  = isset( $prompts[ $post_type ] ) && ! empty( $prompts[ $post_type ] )
            ? $prompts[ $post_type ]
            : self::DEFAULT_PROMPT;

        /**
         * Filter the content before sending to AI.
         *
         * @param string $content   The content to optimize.
         * @param string $post_type The post type.
         */
        $content = apply_filters( 'texttune_ai_pre_optimize_content', $content, $post_type );

        /**
         * Filter the prompt before sending to AI.
         *
         * @param string $prompt    The prompt/instruction.
         * @param string $post_type The post type.
         */
        $prompt = apply_filters( 'texttune_ai_prompt', $prompt, $post_type );

        // Create provider instance and call optimize.
        $client = $this->create_provider( $provider, $api_key );
        $result = $client->optimize( $content, $prompt, $model );

        if ( is_wp_error( $result ) ) {
            return new WP_Error(
                $result->get_error_code(),
                $result->get_error_message(),
                array( 'status' => 502 )
            );
        }

        // Sanitize the AI response.
        $result = wp_kses_post( $result );

        /**
         * Filter the optimized content before returning to the editor.
         *
         * @param string $result    The optimized content.
         * @param string $content   The original content.
         * @param string $post_type The post type.
         */
        $result = apply_filters( 'texttune_ai_post_optimize_content', $result, $content, $post_type );

        /**
         * Action fired after successful optimization.
         *
         * @param string $result    The optimized content.
         * @param string $content   The original content.
         * @param string $post_type The post type.
         */
        do_action( 'texttune_ai_optimized', $result, $content, $post_type );

        return rest_ensure_response(
            array(
                'success' => true,
                'content' => $result,
            )
        );
    }

    /**
     * Create the appropriate AI provider instance.
     *
     * @param string $provider The provider key ('openai' or 'anthropic').
     * @param string $api_key  The decrypted API key.
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
