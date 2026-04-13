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
     * Run on plugin activation.
     */
    public static function activate() {
        // Check PHP version.
        if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
            deactivate_plugins( TEXTTUNE_PLUGIN_BASENAME );
            wp_die(
                esc_html__( 'TextTune AI benötigt PHP 7.4 oder höher.', 'texttune-ai' ),
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
            );
            add_option( 'texttune_ai_settings', $defaults );
        }
    }
}
