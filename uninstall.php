<?php
/**
 * TextTune AI Uninstall
 *
 * Wird ausgeführt wenn das Plugin deinstalliert wird.
 * Entfernt alle Plugin-Optionen aus der Datenbank.
 *
 * @package TextTune_AI
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'texttune_ai_settings' );
