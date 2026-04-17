<?php
/**
 * TextTune AI Image Analyzer
 *
 * Loads attachment bytes, optionally downscales, base64-encodes for the API,
 * and parses the provider's JSON response.
 *
 * @package TextTune_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TextTune_Image_Analyzer {

    const DEFAULT_MAX_EDGE  = 1568;
    const DEFAULT_MAX_BYTES = 5242880; // 5 MB.

    /**
     * Allowed MIME types for vision requests.
     *
     * @var array
     */
    private static $allowed_mimes = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );

    /**
     * Build the base64 image payload for an attachment.
     *
     * @param int $attachment_id Attachment post ID.
     * @param int $max_edge      Maximum edge length in pixels (fallback to default).
     * @return array|WP_Error    [ 'mime' => ..., 'base64' => ..., 'filename' => ... ] or WP_Error.
     */
    public static function get_image_payload( $attachment_id, $max_edge = 0 ) {
        $attachment_id = (int) $attachment_id;

        if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
            return new WP_Error(
                'texttune_not_an_image',
                __( 'Der Anhang wurde nicht gefunden.', 'texttune-ai' ),
                array( 'status' => 404 )
            );
        }

        if ( ! wp_attachment_is_image( $attachment_id ) ) {
            return new WP_Error(
                'texttune_not_an_image',
                __( 'Der Anhang ist kein Bild.', 'texttune-ai' ),
                array( 'status' => 400 )
            );
        }

        $mime = get_post_mime_type( $attachment_id );
        if ( ! in_array( $mime, self::$allowed_mimes, true ) ) {
            return new WP_Error(
                'texttune_unsupported_mime',
                sprintf(
                    /* translators: %s: MIME type */
                    __( 'Bildformat wird nicht unterstützt: %s', 'texttune-ai' ),
                    $mime
                ),
                array( 'status' => 415 )
            );
        }

        $file_path = get_attached_file( $attachment_id );
        $filename  = $file_path ? wp_basename( $file_path ) : (string) $attachment_id;
        $bytes     = '';

        if ( $file_path && file_exists( $file_path ) && is_readable( $file_path ) ) {
            $bytes = @file_get_contents( $file_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        }

        // Fallback for offloaded media (CDN / S3).
        if ( empty( $bytes ) ) {
            $url = wp_get_attachment_url( $attachment_id );
            if ( $url ) {
                $response = wp_remote_get(
                    $url,
                    array(
                        'timeout'  => 30,
                        'sslverify' => true,
                    )
                );
                if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
                    $bytes = wp_remote_retrieve_body( $response );
                }
            }
        }

        if ( empty( $bytes ) ) {
            return new WP_Error(
                'texttune_image_fetch_failed',
                __( 'Die Bilddatei konnte nicht geladen werden.', 'texttune-ai' ),
                array( 'status' => 500 )
            );
        }

        $max_edge = $max_edge > 0 ? (int) $max_edge : (int) apply_filters( 'texttune_ai_vision_max_edge', self::DEFAULT_MAX_EDGE );
        if ( $max_edge < 256 ) {
            $max_edge = self::DEFAULT_MAX_EDGE;
        }

        $downscaled = self::maybe_downscale( $file_path, $bytes, $mime, $max_edge );
        if ( is_array( $downscaled ) ) {
            $bytes = $downscaled['bytes'];
            $mime  = $downscaled['mime'];
        }

        $max_bytes = (int) apply_filters( 'texttune_ai_vision_max_bytes', self::DEFAULT_MAX_BYTES );
        if ( strlen( $bytes ) > $max_bytes ) {
            // Try a lower-quality JPEG re-encode as last resort.
            $recoded = self::encode_jpeg( $file_path ? $file_path : $bytes, $max_edge, 70 );
            if ( is_string( $recoded ) && strlen( $recoded ) <= $max_bytes ) {
                $bytes = $recoded;
                $mime  = 'image/jpeg';
            } else {
                return new WP_Error(
                    'texttune_vision_image_too_large',
                    __( 'Das Bild ist auch nach Verkleinerung zu groß für die API.', 'texttune-ai' ),
                    array( 'status' => 413 )
                );
            }
        }

        return array(
            'mime'     => $mime,
            'base64'   => base64_encode( $bytes ),
            'filename' => $filename,
        );
    }

    /**
     * Read current metadata fields from an attachment.
     *
     * @param int $attachment_id Attachment ID.
     * @return array
     */
    public static function get_current_fields( $attachment_id ) {
        $post = get_post( $attachment_id );
        if ( ! $post ) {
            return array(
                'alt'         => '',
                'title'       => '',
                'caption'     => '',
                'description' => '',
            );
        }

        return array(
            'alt'         => (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
            'title'       => (string) $post->post_title,
            'caption'     => (string) $post->post_excerpt,
            'description' => (string) $post->post_content,
        );
    }

    /**
     * Downscale the image if any edge exceeds the cap. Returns new bytes + mime,
     * or null if no change was necessary / possible.
     *
     * @param string|false $file_path Original file path (preferred for editor).
     * @param string       $bytes     Original bytes (used if file path absent).
     * @param string       $mime      Current MIME.
     * @param int          $max_edge  Maximum edge in pixels.
     * @return array|null
     */
    private static function maybe_downscale( $file_path, $bytes, $mime, $max_edge ) {
        $source = $file_path && file_exists( $file_path ) ? $file_path : $bytes;
        $editor = wp_get_image_editor( $source );
        if ( is_wp_error( $editor ) ) {
            return null;
        }

        $size = $editor->get_size();
        if ( ! is_array( $size ) || empty( $size['width'] ) || empty( $size['height'] ) ) {
            return null;
        }

        if ( $size['width'] <= $max_edge && $size['height'] <= $max_edge ) {
            return null;
        }

        $resized = $editor->resize( $max_edge, $max_edge, false );
        if ( is_wp_error( $resized ) ) {
            return null;
        }

        $tmp_path = wp_tempnam( 'texttune-vision' );
        if ( ! $tmp_path ) {
            return null;
        }

        $target_mime = ( 'image/png' === $mime || 'image/gif' === $mime ) ? $mime : 'image/jpeg';

        $saved = $editor->save( $tmp_path, $target_mime );
        if ( is_wp_error( $saved ) || empty( $saved['path'] ) || ! file_exists( $saved['path'] ) ) {
            @unlink( $tmp_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
            return null;
        }

        $new_bytes = @file_get_contents( $saved['path'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        @unlink( $saved['path'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        if ( $saved['path'] !== $tmp_path ) {
            @unlink( $tmp_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        }

        if ( empty( $new_bytes ) ) {
            return null;
        }

        return array(
            'bytes' => $new_bytes,
            'mime'  => isset( $saved['mime-type'] ) ? $saved['mime-type'] : $target_mime,
        );
    }

    /**
     * Encode an image to JPEG at the given quality for last-resort size reduction.
     *
     * @param string|string $source  File path or bytes.
     * @param int           $max_edge Maximum edge.
     * @param int           $quality  JPEG quality 1-100.
     * @return string|null  Encoded bytes or null on failure.
     */
    private static function encode_jpeg( $source, $max_edge, $quality ) {
        $editor = wp_get_image_editor( $source );
        if ( is_wp_error( $editor ) ) {
            return null;
        }
        $editor->resize( $max_edge, $max_edge, false );
        $editor->set_quality( $quality );

        $tmp_path = wp_tempnam( 'texttune-vision-jpg' );
        if ( ! $tmp_path ) {
            return null;
        }
        $saved = $editor->save( $tmp_path, 'image/jpeg' );
        if ( is_wp_error( $saved ) || empty( $saved['path'] ) || ! file_exists( $saved['path'] ) ) {
            @unlink( $tmp_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
            return null;
        }
        $bytes = @file_get_contents( $saved['path'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        @unlink( $saved['path'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        if ( $saved['path'] !== $tmp_path ) {
            @unlink( $tmp_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        }
        return $bytes ? $bytes : null;
    }

    /**
     * Parse the provider's raw response string into the four expected fields.
     *
     * @param string $raw Raw model output (possibly wrapped in markdown fences).
     * @return array|WP_Error [ 'alt', 'title', 'caption', 'description' ] strings, or WP_Error.
     */
    public static function parse_response( $raw ) {
        if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
            return new WP_Error(
                'texttune_vision_parse_failed',
                __( 'Leere Antwort vom KI-Dienst.', 'texttune-ai' ),
                array( 'status' => 502 )
            );
        }

        $candidate = trim( $raw );
        // Strip surrounding ```json ... ``` fences.
        $candidate = preg_replace( '/^```(?:json)?\s*/i', '', $candidate );
        $candidate = preg_replace( '/```\s*$/', '', $candidate );
        $candidate = trim( $candidate );

        $data = json_decode( $candidate, true );

        // Fallback: extract first balanced {...} block.
        if ( ! is_array( $data ) ) {
            $extracted = self::extract_json_object( $candidate );
            if ( $extracted ) {
                $data = json_decode( $extracted, true );
            }
        }

        if ( ! is_array( $data ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'TEXTTUNE-AI vision parse failed: ' . substr( $raw, 0, 200 ) );
            }
            return new WP_Error(
                'texttune_vision_parse_failed',
                __( 'Die Antwort des KI-Dienstes konnte nicht als JSON gelesen werden.', 'texttune-ai' ),
                array( 'status' => 502 )
            );
        }

        $alt         = isset( $data['alt'] ) ? (string) $data['alt'] : '';
        $title       = isset( $data['title'] ) ? (string) $data['title'] : '';
        $caption     = isset( $data['caption'] ) ? (string) $data['caption'] : '';
        $description = isset( $data['description'] ) ? (string) $data['description'] : '';

        $alt         = self::clip( sanitize_text_field( $alt ), 125 );
        $title       = self::clip( sanitize_text_field( $title ), 60 );
        $caption     = self::clip( sanitize_text_field( $caption ), 200 );
        $description = wp_kses_post( $description );

        return array(
            'alt'         => $alt,
            'title'       => $title,
            'caption'     => $caption,
            'description' => $description,
        );
    }

    /**
     * Extract the first balanced {...} substring from a string.
     *
     * @param string $text
     * @return string|null
     */
    private static function extract_json_object( $text ) {
        $len   = strlen( $text );
        $start = strpos( $text, '{' );
        if ( false === $start ) {
            return null;
        }
        $depth    = 0;
        $in_str   = false;
        $escape   = false;
        for ( $i = $start; $i < $len; $i++ ) {
            $ch = $text[ $i ];
            if ( $escape ) {
                $escape = false;
                continue;
            }
            if ( '\\' === $ch ) {
                $escape = true;
                continue;
            }
            if ( '"' === $ch ) {
                $in_str = ! $in_str;
                continue;
            }
            if ( $in_str ) {
                continue;
            }
            if ( '{' === $ch ) {
                $depth++;
            } elseif ( '}' === $ch ) {
                $depth--;
                if ( 0 === $depth ) {
                    return substr( $text, $start, $i - $start + 1 );
                }
            }
        }
        return null;
    }

    /**
     * Clip a string to a given character length without breaking UTF-8.
     *
     * @param string $text
     * @param int    $max
     * @return string
     */
    private static function clip( $text, $max ) {
        if ( function_exists( 'mb_substr' ) ) {
            $len = mb_strlen( $text );
            if ( $len <= $max ) {
                return $text;
            }
            return rtrim( mb_substr( $text, 0, $max ) );
        }
        if ( strlen( $text ) <= $max ) {
            return $text;
        }
        return rtrim( substr( $text, 0, $max ) );
    }

    /**
     * Map a WordPress locale to a human-readable language name for prompting.
     *
     * @param string $locale e.g. de_DE.
     * @return string
     */
    public static function locale_to_language( $locale ) {
        $map = array(
            'de_DE'        => 'Deutsch',
            'de_AT'        => 'Deutsch (Österreich)',
            'de_CH'        => 'Deutsch (Schweiz)',
            'en_US'        => 'English',
            'en_GB'        => 'English (British)',
            'fr_FR'        => 'Français',
            'es_ES'        => 'Español',
            'it_IT'        => 'Italiano',
            'nl_NL'        => 'Nederlands',
            'pt_BR'        => 'Português (Brasil)',
            'pt_PT'        => 'Português',
            'pl_PL'        => 'Polski',
            'sv_SE'        => 'Svenska',
            'da_DK'        => 'Dansk',
            'nb_NO'        => 'Norsk bokmål',
            'fi'           => 'Suomi',
        );
        $short = substr( (string) $locale, 0, 2 );
        if ( isset( $map[ $locale ] ) ) {
            return $map[ $locale ];
        }
        $short_map = array(
            'de' => 'Deutsch',
            'en' => 'English',
            'fr' => 'Français',
            'es' => 'Español',
            'it' => 'Italiano',
            'nl' => 'Nederlands',
            'pt' => 'Português',
            'pl' => 'Polski',
            'sv' => 'Svenska',
            'da' => 'Dansk',
        );
        return isset( $short_map[ $short ] ) ? $short_map[ $short ] : (string) $locale;
    }
}
