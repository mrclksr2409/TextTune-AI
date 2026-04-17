<?php
/**
 * TextTune AI Activator
 *
 * Handles plugin activation tasks.
 *
 * @package TextTune_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TextTune_Activator {

    /**
     * Default prompt for text optimization.
     */
    const DEFAULT_PROMPT = 'Optimiere den folgenden Text. Verbessere Grammatik, Stil und Lesbarkeit. Behalte den Inhalt, die Bedeutung und die HTML-Formatierung bei. Gib nur den optimierten Text zurück, ohne zusätzliche Erklärungen.';

    /**
     * Default prompt for image analysis (vision).
     * Supports placeholders: {language}, {locale}, {filename}, {fields}.
     */
    const DEFAULT_VISION_PROMPT = "Du bist ein Bildanalyse-Assistent für eine WordPress-Mediathek. Analysiere das bereitgestellte Bild und erzeuge Metadaten in der Sprache: {language}.\n\nDateiname (nur als Kontext, ignoriere generische Kamera-Namen wie IMG_1234):\n{filename}\n\nGib ausschließlich ein einzelnes JSON-Objekt mit genau diesen vier Schlüsseln zurück:\n- \"alt\":         kurzer beschreibender Alt-Text (max. 125 Zeichen, kein \"Bild von ...\")\n- \"title\":       prägnanter Titel (max. 60 Zeichen)\n- \"caption\":     ein Satz als Bildunterschrift\n- \"description\": 2-4 Sätze ausführliche Beschreibung\n\nKeine Einleitung, kein Markdown, keine Code-Fences. Nur das JSON-Objekt.";

    /**
     * Run on plugin activation.
     */
    public static function activate() {
        try {
            // Check PHP version.
            if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
                deactivate_plugins( TEXTTUNE_PLUGIN_BASENAME );
                wp_die(
                    esc_html__( 'TextTune AI benötigt PHP 7.4 oder höher.', 'texttune-ai' ),
                    'Plugin Activation Error',
                    array( 'back_link' => true )
                );
            }

            // Check WordPress version.
            global $wp_version;
            if ( isset( $wp_version ) && version_compare( $wp_version, '6.0', '<' ) ) {
                deactivate_plugins( TEXTTUNE_PLUGIN_BASENAME );
                wp_die(
                    esc_html__( 'TextTune AI benötigt WordPress 6.0 oder höher.', 'texttune-ai' ),
                    'Plugin Activation Error',
                    array( 'back_link' => true )
                );
            }

            // Set default options if they don't exist.
            if ( false === get_option( 'texttune_ai_settings' ) ) {
                $defaults = array(
                    'provider' => 'openai',
                    'api_key'  => '',
                    'model'    => 'gpt-4o',
                    'prompts'  => array(
                        'post' => self::DEFAULT_PROMPT,
                        'page' => self::DEFAULT_PROMPT,
                    ),
                    'vision'   => array(
                        'prompt'         => self::DEFAULT_VISION_PROMPT,
                        'enabled_fields' => array( 'alt', 'title', 'caption', 'description' ),
                        'model'          => '',
                        'max_edge'       => 1568,
                    ),
                );
                add_option( 'texttune_ai_settings', $defaults );
            } else {
                // Backfill vision defaults on upgrade.
                $existing = get_option( 'texttune_ai_settings', array() );
                if ( is_array( $existing ) && empty( $existing['vision'] ) ) {
                    $existing['vision'] = array(
                        'prompt'         => self::DEFAULT_VISION_PROMPT,
                        'enabled_fields' => array( 'alt', 'title', 'caption', 'description' ),
                        'model'          => '',
                        'max_edge'       => 1568,
                    );
                    update_option( 'texttune_ai_settings', $existing );
                }
            }
        } catch ( \Throwable $e ) {
            // Log the actual error so the user can diagnose it from debug.log.
            error_log(
                sprintf(
                    'TEXTTUNE-AI ACTIVATION ERROR: %s in %s:%d',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                )
            );
            error_log( 'TEXTTUNE-AI ACTIVATION STACK TRACE: ' . $e->getTraceAsString() );

            wp_die(
                sprintf(
                    /* translators: %s: The actual error message. */
                    esc_html__( 'TextTune AI Aktivierungsfehler: %s. Siehe wp-content/debug.log (mit WP_DEBUG_LOG) für Details.', 'texttune-ai' ),
                    esc_html( $e->getMessage() )
                ),
                'Plugin Activation Error',
                array( 'back_link' => true )
            );
        }
    }
}
