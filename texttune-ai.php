<?php
/**
 * Plugin Name: TextTune AI
 * Plugin URI:  https://github.com/mrclksr2409/TextTune-AI
 * Description: KI-gestützte Textoptimierung direkt im WordPress Block-Editor. Unterstützt OpenAI und Anthropic.
 * Version:     1.0.0
 * Author:      TextTune
 * Text Domain: texttune-ai
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package TextTune_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'TEXTTUNE_VERSION', '1.0.0' );
define( 'TEXTTUNE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TEXTTUNE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TEXTTUNE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Include classes.
require_once TEXTTUNE_PLUGIN_DIR . 'includes/class-texttune-encryption.php';
require_once TEXTTUNE_PLUGIN_DIR . 'includes/class-texttune-activator.php';
require_once TEXTTUNE_PLUGIN_DIR . 'includes/class-texttune-settings.php';
require_once TEXTTUNE_PLUGIN_DIR . 'includes/class-texttune-openai.php';
require_once TEXTTUNE_PLUGIN_DIR . 'includes/class-texttune-anthropic.php';
require_once TEXTTUNE_PLUGIN_DIR . 'includes/class-texttune-rest-api.php';

// Activation hook.
register_activation_hook( __FILE__, array( 'TextTune_Activator', 'activate' ) );

/**
 * Initialize the plugin.
 */
function texttune_ai_init() {
    // Load text domain.
    load_plugin_textdomain( 'texttune-ai', false, dirname( TEXTTUNE_PLUGIN_BASENAME ) . '/languages' );

    // Initialize settings page.
    new TextTune_Settings();

    // Initialize REST API.
    new TextTune_REST_API();
}
add_action( 'plugins_loaded', 'texttune_ai_init' );

/**
 * Enqueue block editor assets.
 */
function texttune_ai_enqueue_editor_assets() {
    $post_type = get_post_type();
    if ( ! $post_type ) {
        $post_type = 'post';
    }

    wp_enqueue_script(
        'texttune-editor',
        TEXTTUNE_PLUGIN_URL . 'assets/js/texttune-editor.js',
        array(
            'wp-plugins',
            'wp-edit-post',
            'wp-element',
            'wp-components',
            'wp-data',
            'wp-api-fetch',
            'wp-block-editor',
            'wp-blocks',
            'wp-i18n',
            'wp-hooks',
            'wp-compose',
        ),
        TEXTTUNE_VERSION,
        true
    );

    wp_localize_script(
        'texttune-editor',
        'texttuneData',
        array(
            'postType' => $post_type,
            'nonce'    => wp_create_nonce( 'wp_rest' ),
        )
    );
}
add_action( 'enqueue_block_editor_assets', 'texttune_ai_enqueue_editor_assets' );
