<?php
/**
 * TextTune AI Media Integration
 *
 * Wires the image-analysis feature into the WordPress media library:
 * - attachment detail button (modal + post.php)
 * - media library row action
 * - upload.php bulk action
 * - asset enqueue + localisation
 *
 * @package TextTune_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TextTune_Media_Integration {

    const BULK_ACTION = 'texttune_analyze';

    /**
     * Constructor.
     */
    public function __construct() {
        add_filter( 'attachment_fields_to_edit', array( $this, 'add_attachment_field' ), 10, 2 );
        add_filter( 'media_row_actions', array( $this, 'add_row_action' ), 10, 2 );
        add_filter( 'bulk_actions-upload', array( $this, 'add_bulk_action' ) );
        add_filter( 'handle_bulk_actions-upload', array( $this, 'handle_bulk_action' ), 10, 3 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_enqueue_media', array( $this, 'enqueue_for_modal' ) );
    }

    /**
     * Add the "Bild analysieren" pseudo-field to the attachment edit UI.
     *
     * @param array   $form_fields
     * @param WP_Post $post
     * @return array
     */
    public function add_attachment_field( $form_fields, $post ) {
        if ( ! $post || ! wp_attachment_is_image( $post->ID ) ) {
            return $form_fields;
        }
        if ( ! current_user_can( 'edit_post', $post->ID ) ) {
            return $form_fields;
        }

        $html  = '<button type="button" class="button texttune-analyze-btn" data-id="' . esc_attr( $post->ID ) . '">';
        $html .= esc_html__( 'Bild analysieren', 'texttune-ai' );
        $html .= '</button>';
        $html .= '<span class="texttune-analyze-status" aria-live="polite" style="margin-left:8px;"></span>';

        $form_fields['texttune_ai_analyze'] = array(
            'label' => __( 'TextTune AI', 'texttune-ai' ),
            'input' => 'html',
            'html'  => $html,
            'helps' => __( 'Generiert Alt-Text, Titel, Beschriftung und Beschreibung aus dem Bildinhalt.', 'texttune-ai' ),
        );

        return $form_fields;
    }

    /**
     * Add row action in the media library list view.
     *
     * @param array   $actions
     * @param WP_Post $post
     * @return array
     */
    public function add_row_action( $actions, $post ) {
        if ( ! $post || ! wp_attachment_is_image( $post->ID ) ) {
            return $actions;
        }
        if ( ! current_user_can( 'edit_post', $post->ID ) ) {
            return $actions;
        }
        $actions['texttune_analyze'] = sprintf(
            '<a href="#" class="texttune-analyze-row" data-id="%d">%s</a>',
            (int) $post->ID,
            esc_html__( 'Mit TextTune AI analysieren', 'texttune-ai' )
        );
        return $actions;
    }

    /**
     * Add bulk action to upload.php.
     *
     * @param array $bulk_actions
     * @return array
     */
    public function add_bulk_action( $bulk_actions ) {
        if ( ! current_user_can( 'upload_files' ) ) {
            return $bulk_actions;
        }
        $bulk_actions[ self::BULK_ACTION ] = __( 'Mit TextTune AI analysieren', 'texttune-ai' );
        return $bulk_actions;
    }

    /**
     * Handle the bulk action by redirecting with IDs so JS can perform the requests.
     *
     * @param string $redirect_to
     * @param string $doaction
     * @param array  $post_ids
     * @return string
     */
    public function handle_bulk_action( $redirect_to, $doaction, $post_ids ) {
        if ( self::BULK_ACTION !== $doaction ) {
            return $redirect_to;
        }
        $ids = array_map( 'absint', (array) $post_ids );
        $ids = array_values( array_filter( $ids ) );
        if ( empty( $ids ) ) {
            return $redirect_to;
        }
        return add_query_arg(
            array(
                'texttune_bulk' => implode( ',', $ids ),
            ),
            $redirect_to
        );
    }

    /**
     * Enqueue media-integration assets on admin screens that use the media UI.
     *
     * @param string $hook_suffix
     */
    public function enqueue_assets( $hook_suffix ) {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

        $targets = array( 'upload.php' );
        $is_target = in_array( $hook_suffix, $targets, true );

        if ( ! $is_target && $screen ) {
            if ( 'attachment' === $screen->id ) {
                $is_target = true;
            } elseif ( 'post' === $screen->base && 'attachment' === $screen->post_type ) {
                $is_target = true;
            }
        }

        // For any post edit screen, the media modal may be used — handled via enqueue_for_modal.
        if ( ! $is_target ) {
            return;
        }

        $this->do_enqueue();
    }

    /**
     * Enqueue from the wp_enqueue_media hook so the assets are available inside the modal
     * on any screen (post editor, widgets, etc.).
     */
    public function enqueue_for_modal() {
        $this->do_enqueue();
    }

    /**
     * Actual enqueue routine; guards against double-enqueue.
     */
    private function do_enqueue() {
        if ( wp_script_is( 'texttune-media', 'enqueued' ) ) {
            return;
        }

        wp_enqueue_style(
            'texttune-media',
            TEXTTUNE_PLUGIN_URL . 'assets/css/texttune-media.css',
            array(),
            TEXTTUNE_VERSION
        );

        wp_enqueue_script(
            'texttune-media',
            TEXTTUNE_PLUGIN_URL . 'assets/js/texttune-media.js',
            array( 'wp-api-fetch', 'wp-i18n' ),
            TEXTTUNE_VERSION,
            true
        );

        $settings        = get_option( 'texttune_ai_settings', array() );
        $vision_settings = isset( $settings['vision'] ) && is_array( $settings['vision'] ) ? $settings['vision'] : array();
        $enabled_fields  = isset( $vision_settings['enabled_fields'] ) && is_array( $vision_settings['enabled_fields'] )
            ? $vision_settings['enabled_fields']
            : array( 'alt', 'title', 'caption', 'description' );

        $bulk_ids_raw = isset( $_GET['texttune_bulk'] ) ? (string) wp_unslash( $_GET['texttune_bulk'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $bulk_ids     = array();
        if ( '' !== $bulk_ids_raw ) {
            foreach ( explode( ',', $bulk_ids_raw ) as $id ) {
                $id = absint( $id );
                if ( $id ) {
                    $bulk_ids[] = $id;
                }
            }
        }

        wp_localize_script(
            'texttune-media',
            'texttuneMediaData',
            array(
                'restUrl'       => esc_url_raw( rest_url( 'texttune/v1/analyze-image' ) ),
                'nonce'         => wp_create_nonce( 'wp_rest' ),
                'enabledFields' => array_values( $enabled_fields ),
                'bulkIds'       => $bulk_ids,
                'settingsUrl'   => admin_url( 'options-general.php?page=texttune-ai&tab=vision' ),
                'fieldLabels'   => array(
                    'alt'         => __( 'Alt-Text', 'texttune-ai' ),
                    'title'       => __( 'Titel', 'texttune-ai' ),
                    'caption'     => __( 'Beschriftung', 'texttune-ai' ),
                    'description' => __( 'Beschreibung', 'texttune-ai' ),
                ),
                'i18n'          => array(
                    'analyzing'          => __( 'Analysiere Bild…', 'texttune-ai' ),
                    'analyze'            => __( 'Bild analysieren', 'texttune-ai' ),
                    'dialogTitle'        => __( 'Vorgeschlagene Metadaten', 'texttune-ai' ),
                    'dialogIntroEmpty'   => __( 'Die Felder sind leer. Möchtest du die Vorschläge übernehmen?', 'texttune-ai' ),
                    'dialogIntroConflict'=> __( 'Einige Felder sind bereits befüllt. Bitte pro Feld entscheiden:', 'texttune-ai' ),
                    'colField'           => __( 'Feld', 'texttune-ai' ),
                    'colCurrent'         => __( 'Aktuell', 'texttune-ai' ),
                    'colGenerated'       => __( 'Vorschlag', 'texttune-ai' ),
                    'colAction'          => __( 'Aktion', 'texttune-ai' ),
                    'actionOverwrite'    => __( 'Überschreiben', 'texttune-ai' ),
                    'actionKeep'         => __( 'Behalten', 'texttune-ai' ),
                    'apply'              => __( 'Übernehmen', 'texttune-ai' ),
                    'cancel'             => __( 'Abbrechen', 'texttune-ai' ),
                    'close'              => __( 'Schließen', 'texttune-ai' ),
                    'allOverwrite'       => __( 'Alle überschreiben', 'texttune-ai' ),
                    'allKeep'            => __( 'Alle behalten', 'texttune-ai' ),
                    'empty'              => __( '(leer)', 'texttune-ai' ),
                    'saving'             => __( 'Speichere…', 'texttune-ai' ),
                    'saved'              => __( 'Gespeichert.', 'texttune-ai' ),
                    'error'              => __( 'Fehler', 'texttune-ai' ),
                    'noApiKey'           => __( 'Kein API-Schlüssel konfiguriert.', 'texttune-ai' ),
                    'openSettings'       => __( 'Einstellungen öffnen', 'texttune-ai' ),
                    'bulkTitle'          => __( 'Bilder werden analysiert', 'texttune-ai' ),
                    'bulkProgress'       => __( 'Bild %1$d von %2$d', 'texttune-ai' ),
                    'bulkStrategyQ'      => __( 'Wie sollen bereits befüllte Felder behandelt werden?', 'texttune-ai' ),
                    'bulkStrategyOvw'    => __( 'Alle bestehenden Werte überschreiben', 'texttune-ai' ),
                    'bulkStrategyKeep'   => __( 'Leere Felder befüllen, bestehende behalten', 'texttune-ai' ),
                    'bulkStrategyAsk'    => __( 'Pro Bild fragen', 'texttune-ai' ),
                    'bulkStart'          => __( 'Start', 'texttune-ai' ),
                    'bulkDone'           => __( 'Fertig. %1$d gespeichert, %2$d übersprungen.', 'texttune-ai' ),
                    'bulkSkippedNotImg'  => __( 'Kein Bild', 'texttune-ai' ),
                    'skip'               => __( 'Überspringen', 'texttune-ai' ),
                ),
            )
        );

        wp_set_script_translations( 'texttune-media', 'texttune-ai' );
    }
}
