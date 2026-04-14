<?php
/**
 * TextTune AI Settings
 *
 * Handles the admin settings page using WordPress Settings API.
 *
 * @package TextTune_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TextTune_Settings {

    /**
     * Available AI providers and their models.
     *
     * @var array
     */
    private $providers = array(
        'openai'    => array(
            'label'  => 'OpenAI',
            'models' => array(
                'gpt-4o'       => 'GPT-4o',
                'gpt-4o-mini'  => 'GPT-4o Mini',
                'gpt-4-turbo'  => 'GPT-4 Turbo',
            ),
        ),
        'anthropic' => array(
            'label'  => 'Anthropic',
            'models' => array(
                'claude-sonnet-4-20250514'    => 'Claude Sonnet 4',
                'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5',
            ),
        ),
    );

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_options_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
    }

    /**
     * Add the options page under Settings menu.
     */
    public function add_options_page() {
        add_options_page(
            __( 'TextTune AI Einstellungen', 'texttune-ai' ),
            __( 'TextTune AI', 'texttune-ai' ),
            'manage_options',
            'texttune-ai',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register all settings.
     */
    public function register_settings() {
        register_setting(
            'texttune_ai_options',
            'texttune_ai_settings',
            array(
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
            )
        );

        // Provider section (Settings tab).
        add_settings_section(
            'texttune_provider_section',
            __( 'KI-Provider', 'texttune-ai' ),
            array( $this, 'render_provider_section' ),
            'texttune-ai-settings'
        );

        add_settings_field(
            'texttune_provider',
            __( 'Provider', 'texttune-ai' ),
            array( $this, 'render_provider_field' ),
            'texttune-ai-settings',
            'texttune_provider_section'
        );

        add_settings_field(
            'texttune_api_key',
            __( 'API-Schlüssel', 'texttune-ai' ),
            array( $this, 'render_api_key_field' ),
            'texttune-ai-settings',
            'texttune_provider_section'
        );

        add_settings_field(
            'texttune_model',
            __( 'Modell', 'texttune-ai' ),
            array( $this, 'render_model_field' ),
            'texttune-ai-settings',
            'texttune_provider_section'
        );

        // Prompts section (Prompts tab).
        add_settings_section(
            'texttune_prompts_section',
            __( 'Prompts pro Inhaltstyp', 'texttune-ai' ),
            array( $this, 'render_prompts_section' ),
            'texttune-ai-prompts'
        );

        $post_types = get_post_types( array( 'public' => true ), 'objects' );
        foreach ( $post_types as $post_type ) {
            if ( 'attachment' === $post_type->name ) {
                continue;
            }
            add_settings_field(
                'texttune_prompt_' . $post_type->name,
                sprintf( __( 'Prompt für „%s"', 'texttune-ai' ), $post_type->label ),
                array( $this, 'render_prompt_field' ),
                'texttune-ai-prompts',
                'texttune_prompts_section',
                array( 'post_type' => $post_type->name )
            );
        }
    }

    /**
     * Sanitize settings before saving.
     *
     * @param array $input The submitted settings.
     * @return array The sanitized settings.
     */
    public function sanitize_settings( $input ) {
        if ( ! is_array( $input ) ) {
            $input = array();
        }

        $sanitized = array();

        // Provider.
        $valid_providers        = array_keys( $this->providers );
        $sanitized['provider']  = isset( $input['provider'] ) && in_array( $input['provider'], $valid_providers, true )
            ? $input['provider']
            : 'openai';

        // API Key — encrypt before saving.
        $current_settings = get_option( 'texttune_ai_settings', array() );
        if ( ! empty( $input['api_key'] ) ) {
            $sanitized['api_key'] = TextTune_Encryption::encrypt( $input['api_key'] );
        } else {
            // Keep existing key if field was left empty.
            $sanitized['api_key'] = isset( $current_settings['api_key'] ) ? $current_settings['api_key'] : '';
        }

        // Model — validate against allowed models for the selected provider.
        $valid_models        = array_keys( $this->providers[ $sanitized['provider'] ]['models'] );
        $sanitized['model']  = isset( $input['model'] ) && in_array( $input['model'], $valid_models, true )
            ? $input['model']
            : $valid_models[0];

        // Prompts per post type.
        $sanitized['prompts'] = array();
        if ( isset( $input['prompts'] ) && is_array( $input['prompts'] ) ) {
            foreach ( $input['prompts'] as $post_type => $prompt ) {
                $sanitized['prompts'][ sanitize_key( $post_type ) ] = sanitize_textarea_field( $prompt );
            }
        }

        return $sanitized;
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $allowed_tabs = array( 'settings', 'prompts' );
        // Force scalar handling — if $_GET['tab'] arrives as an array, PHP 8 would
        // throw a TypeError inside wp_unslash()/sanitize_key(). Cast to string first.
        $raw_tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( (string) $_GET['tab'] ) ) : '';
        $active_tab = in_array( $raw_tab, $allowed_tabs, true ) ? $raw_tab : 'settings';

        $settings_url = admin_url( 'options-general.php?page=texttune-ai&tab=settings' );
        $prompts_url  = admin_url( 'options-general.php?page=texttune-ai&tab=prompts' );
        $referer_url  = admin_url( 'options-general.php?page=texttune-ai&tab=' . $active_tab );
        ?>
        <div class="wrap texttune-settings">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'TextTune AI Tabs', 'texttune-ai' ); ?>">
                <a href="<?php echo esc_url( $settings_url ); ?>"
                   class="nav-tab<?php echo ( 'settings' === $active_tab ) ? ' nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Einstellungen', 'texttune-ai' ); ?>
                </a>
                <a href="<?php echo esc_url( $prompts_url ); ?>"
                   class="nav-tab<?php echo ( 'prompts' === $active_tab ) ? ' nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Prompts', 'texttune-ai' ); ?>
                </a>
            </nav>

            <form action="options.php" method="post">
                <?php settings_fields( 'texttune_ai_options' ); ?>
                <input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr( $referer_url ); ?>" />

                <div class="texttune-tab-panel" id="texttune-tab-settings"<?php echo ( 'settings' === $active_tab ) ? '' : ' hidden'; ?>>
                    <?php do_settings_sections( 'texttune-ai-settings' ); ?>
                </div>

                <div class="texttune-tab-panel" id="texttune-tab-prompts"<?php echo ( 'prompts' === $active_tab ) ? '' : ' hidden'; ?>>
                    <?php do_settings_sections( 'texttune-ai-prompts' ); ?>
                </div>

                <?php submit_button( __( 'Einstellungen speichern', 'texttune-ai' ) ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render provider section description.
     */
    public function render_provider_section() {
        echo '<p>' . esc_html__( 'Wähle den KI-Provider und gib deinen API-Schlüssel ein.', 'texttune-ai' ) . '</p>';
    }

    /**
     * Render prompts section description.
     */
    public function render_prompts_section() {
        echo '<p>' . esc_html__( 'Definiere für jeden Inhaltstyp einen eigenen Prompt. Dieser wird als Anweisung an die KI gesendet.', 'texttune-ai' ) . '</p>';
    }

    /**
     * Render the provider radio buttons.
     */
    public function render_provider_field() {
        $settings = get_option( 'texttune_ai_settings', array() );
        $current  = isset( $settings['provider'] ) ? $settings['provider'] : 'openai';

        foreach ( $this->providers as $key => $provider ) {
            ?>
            <label style="margin-right: 20px;">
                <input
                    type="radio"
                    name="texttune_ai_settings[provider]"
                    value="<?php echo esc_attr( $key ); ?>"
                    <?php checked( $current, $key ); ?>
                    class="texttune-provider-radio"
                />
                <?php echo esc_html( $provider['label'] ); ?>
            </label>
            <?php
        }
    }

    /**
     * Render the API key field.
     */
    public function render_api_key_field() {
        $settings   = get_option( 'texttune_ai_settings', array() );
        $has_key    = ! empty( $settings['api_key'] );
        $placeholder = $has_key
            ? '••••••••••••••••'
            : __( 'API-Schlüssel eingeben', 'texttune-ai' );

        ?>
        <input
            type="password"
            id="texttune-api-key"
            name="texttune_ai_settings[api_key]"
            value=""
            placeholder="<?php echo esc_attr( $placeholder ); ?>"
            class="regular-text"
            autocomplete="off"
        />
        <button type="button" id="texttune-toggle-key" class="button button-secondary">
            <?php esc_html_e( 'Anzeigen', 'texttune-ai' ); ?>
        </button>
        <?php if ( $has_key ) : ?>
            <p class="description">
                <?php esc_html_e( 'Leer lassen, um den bestehenden Schlüssel beizubehalten.', 'texttune-ai' ); ?>
            </p>
        <?php endif; ?>
        <?php if ( ! TextTune_Encryption::is_available() ) : ?>
            <p class="description" style="color: #d63638;">
                <?php esc_html_e( 'Warnung: OpenSSL ist nicht verfügbar. Der API-Schlüssel wird nur Base64-kodiert gespeichert.', 'texttune-ai' ); ?>
            </p>
        <?php endif; ?>
        <?php
    }

    /**
     * Render the model dropdown.
     */
    public function render_model_field() {
        $settings = get_option( 'texttune_ai_settings', array() );
        $current  = isset( $settings['model'] ) ? $settings['model'] : 'gpt-4o';

        foreach ( $this->providers as $provider_key => $provider ) {
            ?>
            <select
                name="texttune_ai_settings[model]"
                class="texttune-model-select"
                data-provider="<?php echo esc_attr( $provider_key ); ?>"
            >
                <?php foreach ( $provider['models'] as $model_key => $model_label ) : ?>
                    <option
                        value="<?php echo esc_attr( $model_key ); ?>"
                        <?php selected( $current, $model_key ); ?>
                    >
                        <?php echo esc_html( $model_label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
        }
        ?>
        <p class="description">
            <?php esc_html_e( 'Wähle das KI-Modell für die Textoptimierung.', 'texttune-ai' ); ?>
        </p>
        <?php
    }

    /**
     * Render a prompt textarea for a specific post type.
     *
     * @param array $args Field arguments containing 'post_type'.
     */
    public function render_prompt_field( $args ) {
        $post_type = $args['post_type'];
        $settings  = get_option( 'texttune_ai_settings', array() );
        $prompts   = isset( $settings['prompts'] ) ? $settings['prompts'] : array();
        $value     = isset( $prompts[ $post_type ] ) ? $prompts[ $post_type ] : TextTune_Activator::DEFAULT_PROMPT;

        ?>
        <textarea
            name="texttune_ai_settings[prompts][<?php echo esc_attr( $post_type ); ?>]"
            rows="4"
            class="large-text"
            placeholder="<?php echo esc_attr( TextTune_Activator::DEFAULT_PROMPT ); ?>"
        ><?php echo esc_textarea( $value ); ?></textarea>
        <?php
    }

    /**
     * Enqueue admin assets only on the settings page.
     *
     * @param string $hook_suffix The admin page hook suffix.
     */
    public function enqueue_admin_assets( $hook_suffix ) {
        if ( 'settings_page_texttune-ai' !== $hook_suffix ) {
            return;
        }

        wp_enqueue_style(
            'texttune-admin',
            TEXTTUNE_PLUGIN_URL . 'assets/css/texttune-admin.css',
            array(),
            TEXTTUNE_VERSION
        );

        wp_enqueue_script(
            'texttune-admin',
            TEXTTUNE_PLUGIN_URL . 'assets/js/texttune-admin.js',
            array(),
            TEXTTUNE_VERSION,
            true
        );
    }

    /**
     * Show admin notices when API key is missing.
     */
    public function show_admin_notices() {
        $settings = get_option( 'texttune_ai_settings', array() );

        if ( empty( $settings['api_key'] ) ) {
            $settings_url = admin_url( 'options-general.php?page=texttune-ai' );
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <?php
                    printf(
                        /* translators: %s: URL to settings page */
                        esc_html__( 'TextTune AI: Bitte konfiguriere deinen API-Schlüssel in den %sEinstellungen%s.', 'texttune-ai' ),
                        '<a href="' . esc_url( $settings_url ) . '">',
                        '</a>'
                    );
                    ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Get the providers list (used by REST API for validation).
     *
     * @return array
     */
    public function get_providers() {
        return $this->providers;
    }
}
