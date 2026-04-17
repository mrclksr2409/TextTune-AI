<?php
/**
 * Plugin Name: TextTune AI
 * Plugin URI:  https://github.com/mrclksr2409/TextTune-AI
 * Description: KI-gestützte Textoptimierung direkt im WordPress Block-Editor und Classic Editor. Unterstützt OpenAI und Anthropic.
 * Version:     1.0.1
 * Author:      Marcel Kaiser
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

define( 'TEXTTUNE_VERSION', '1.0.1' );
define( 'TEXTTUNE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TEXTTUNE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TEXTTUNE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load Plugin Update Checker (bundled in lib/).
$texttune_puc_path = TEXTTUNE_PLUGIN_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';
if ( file_exists( $texttune_puc_path ) ) {
    require_once $texttune_puc_path;
}
unset( $texttune_puc_path );

// Include classes — wrapped so a missing/broken include surfaces in the error log
// instead of a bare fatal without context.
try {
    require_once TEXTTUNE_PLUGIN_DIR . 'includes/class-texttune-encryption.php';
    require_once TEXTTUNE_PLUGIN_DIR . 'includes/class-texttune-activator.php';
    require_once TEXTTUNE_PLUGIN_DIR . 'includes/class-texttune-settings.php';
    require_once TEXTTUNE_PLUGIN_DIR . 'includes/class-texttune-openai.php';
    require_once TEXTTUNE_PLUGIN_DIR . 'includes/class-texttune-anthropic.php';
    require_once TEXTTUNE_PLUGIN_DIR . 'includes/class-texttune-rest-api.php';
    require_once TEXTTUNE_PLUGIN_DIR . 'includes/class-texttune-image-analyzer.php';
    require_once TEXTTUNE_PLUGIN_DIR . 'includes/class-texttune-rest-api-vision.php';
    require_once TEXTTUNE_PLUGIN_DIR . 'includes/class-texttune-media-integration.php';
} catch ( \Throwable $e ) {
    error_log(
        sprintf(
            'TEXTTUNE-AI LOAD ERROR: %s in %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );
    // Re-throw so WordPress still shows the activation-failed screen.
    throw $e;
}

// Activation hook.
register_activation_hook( __FILE__, array( 'TextTune_Activator', 'activate' ) );

/**
 * Initialize the plugin.
 */
function texttune_ai_init() {
    try {
        // Load text domain.
        load_plugin_textdomain( 'texttune-ai', false, dirname( TEXTTUNE_PLUGIN_BASENAME ) . '/languages' );

        // Initialize settings page.
        new TextTune_Settings();

        // Initialize REST API.
        new TextTune_REST_API();

        // Initialize Vision REST API + media library integration.
        new TextTune_REST_API_Vision();
        new TextTune_Media_Integration();

        // Initialize Plugin Update Checker for GitHub-based updates.
        if ( class_exists( \YahnisElsts\PluginUpdateChecker\v5\PucFactory::class ) ) {
            $update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
                'https://github.com/mrclksr2409/TextTune-AI/',
                __FILE__,
                'texttune-ai'
            );
            // Prefer attached release asset zip over auto-generated source zip.
            $source = $update_checker->getVcsSource();
            if ( $source ) {
                $source->enableReleaseAssets();
            }
        }
    } catch ( \Throwable $e ) {
        error_log(
            sprintf(
                'TEXTTUNE-AI INIT ERROR: %s in %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            )
        );
        // During activation we want WordPress to detect the fatal, so re-throw.
        // On normal requests re-throwing would take down the whole site, so only
        // re-throw while the plugin is being activated.
        if ( defined( 'WP_SANDBOX_SCRAPING' ) && WP_SANDBOX_SCRAPING ) {
            throw $e;
        }
    }
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

/**
 * Register TinyMCE plugin for Classic Editor.
 *
 * @param array $plugins Registered TinyMCE plugins.
 * @return array Modified plugins array.
 */
function texttune_ai_register_tinymce_plugin( $plugins ) {
    $plugins['texttune_ai'] = TEXTTUNE_PLUGIN_URL . 'assets/js/texttune-classic-editor.js';
    return $plugins;
}

/**
 * Add TextTune AI button to TinyMCE toolbar.
 *
 * @param array $buttons TinyMCE toolbar buttons.
 * @return array Modified buttons array.
 */
function texttune_ai_add_tinymce_button( $buttons ) {
    $buttons[] = 'texttune_ai_menu';
    return $buttons;
}

/**
 * Initialize Classic Editor integration.
 */
function texttune_ai_classic_editor_init() {
    // Only for users who can edit posts.
    if ( ! current_user_can( 'edit_posts' ) ) {
        return;
    }

    // Only if rich editing is enabled.
    if ( 'true' !== get_user_option( 'rich_editing' ) ) {
        return;
    }

    add_filter( 'mce_external_plugins', 'texttune_ai_register_tinymce_plugin' );
    add_filter( 'mce_buttons_2', 'texttune_ai_add_tinymce_button' );
}
add_action( 'admin_init', 'texttune_ai_classic_editor_init' );

/**
 * Enqueue data script for the Classic Editor.
 *
 * @param string $hook_suffix The admin page hook suffix.
 */
function texttune_ai_classic_editor_enqueue( $hook_suffix ) {
    if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
        return;
    }

    // Only enqueue if we're using the Classic Editor (block editor loads its own assets).
    if ( function_exists( 'use_block_editor_for_post' ) ) {
        global $post;
        if ( $post && use_block_editor_for_post( $post ) ) {
            return;
        }
    }

    $post_type = get_post_type();
    if ( ! $post_type ) {
        $post_type = 'post';
    }

    // Inline script to pass data to the TinyMCE plugin.
    wp_add_inline_script(
        'editor',
        'var texttuneClassicData = ' . wp_json_encode(
            array(
                'postType' => $post_type,
                'restUrl'  => esc_url_raw( rest_url() ),
                'nonce'    => wp_create_nonce( 'wp_rest' ),
            )
        ) . ';',
        'before'
    );
}
add_action( 'admin_enqueue_scripts', 'texttune_ai_classic_editor_enqueue' );
