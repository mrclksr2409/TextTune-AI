# WP-Backend UI

A small, dependency-free design system that gives all of our WordPress plugins (AutoQuill, PixelDiet, Relaymint, TextTune AI) the same modern, minimal look in wp-admin.

It is a **library, not a plugin**: each plugin bundles a copy (like the Plugin Update Checker). When several plugins ship different copies, only the newest one is loaded, so every screen uses the same stylesheet.

![Styleguide](demo/screenshot.png)

## Features

- **Zero-markup restyling** – registered screens get a `wpb-admin` body class. Native WordPress markup (`.wrap`, `h1`, `.nav-tab-wrapper`, `.form-table`, `.notice`, `.button`, inputs, `WP_List_Table`) is restyled automatically. A page built with the Settings API looks modern without touching its HTML.
- **Components** – page header, cards, grid, stats, stacked fields, toggle switch, badges, inline alerts, key/value list, code block, empty state, spinner, busy overlay, progress bar, modal (`<dialog>`), toasts, toolbar, sticky save bar.
- **Centered layout** – page content sits in a centered column (`--wpb-content-width`, default 1200px) with even gutters on every screen size.
- **Design tokens** – every color, radius, shadow and spacing is a CSS custom property (`--wpb-*`). The accent follows the user's admin color scheme (`--wp-admin-theme-color`).
- **Behaviour without custom JS** – data attributes for client-side tabs, modals, password reveal, copy to clipboard, confirmations and dismissible alerts. Vanilla JS, no jQuery.
- **PHP helpers** – `header()`, `tabs()`, `card_start()/card_end()`, `badge()`, `toggle()`, `alert()`. All output is escaped.
- **Version negotiation** – the newest bundled copy wins, loaded exactly once.
- Accessible: visible focus rings, `aria-*` on tabs/toasts/alerts, `prefers-reduced-motion`.

## Requirements

- WordPress 5.5+
- PHP 7.2+

## Installation (per plugin)

1. Copy this repository (without `demo/` and `.git`) into the plugin, e.g. `lib/wp-backend-ui/`.
2. Load it from the plugin's main file **at top level** (not inside a hook):

   ```php
   require_once __DIR__ . '/lib/wp-backend-ui/wp-backend-ui.php';
   ```

3. Register the plugin's admin pages (by `?page=` slug and/or screen ID):

   ```php
   wpb_admin_ui_register(
   	array(
   		'pages'   => array( 'relaymint', 'relaymint-log', 'relaymint-tools' ),
   		'screens' => array(), // e.g. 'settings_page_pixel-diet'
   	)
   );
   ```

That's it — those screens now use the shared look. Use the components below to go further.

## Usage

### Page skeleton

```php
public function render_page() {
	?>
	<div class="wrap">
		<?php
		WPB_Admin_UI::header(
			array(
				'title'    => 'Relaymint',
				'subtitle' => __( 'SMTP delivery, smart routing and email log', 'relaymint' ),
				'icon'     => 'dashicons-email-alt', // Dashicon slug or image URL.
				'version'  => RELAYMINT_VERSION,
				'actions'  => array(
					array( 'label' => __( 'Send test email', 'relaymint' ), 'url' => $url, 'primary' => true ),
				),
			)
		);

		WPB_Admin_UI::tabs(
			array(
				'general' => __( 'General', 'relaymint' ),
				'routing' => __( 'Smart Routing', 'relaymint' ),
			),
			$active_tab
		);
		?>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'relaymint' );
			do_settings_sections( 'relaymint' ); // Renders as cards automatically.
			submit_button();
			?>
		</form>
	</div>
	<?php
}
```

`header()` prints `<hr class="wp-header-end">`, so WordPress places admin notices below the header.

### PHP helpers

| Method | Purpose |
| --- | --- |
| `WPB_Admin_UI::header( $args )` | Page header with icon, title, version badge, subtitle, action buttons. |
| `WPB_Admin_UI::tabs( $tabs, $active, $args )` | Server-side tabs (`?tab=key`). Args: `base_url`, `query_arg`, `label`. |
| `WPB_Admin_UI::card_start( $args )` / `card_end( $footer )` | Card with optional title, description, header actions, `flush` body. |
| `WPB_Admin_UI::badge( $label, $variant, $dot )` | Returns badge HTML. Variants: `neutral`, `success`, `warning`, `error`, `info`, `accent`. |
| `WPB_Admin_UI::toggle( $name, $checked, $label, $args )` | Returns a toggle switch (a real checkbox, works with the Settings API). |
| `WPB_Admin_UI::alert( $message, $variant, $title )` | Returns an inline alert that stays where it is printed. |
| `WPB_Admin_UI::enqueue_assets()` | Loads CSS/JS manually, e.g. for a meta box on the post editor. Components work there; the core restyling layer only applies on registered screens. |
| `WPB_Admin_UI::is_active_screen()` | Whether the current screen is registered. |

### CSS components

| Class | Notes |
| --- | --- |
| `.wpb-header`, `__brand`, `__icon`, `__title`, `__subtitle`, `__actions` | Page header. |
| `.wpb-card`, `__header`, `__title`, `__description`, `__body`, `__body--flush`, `__footer`; `--muted`, `--interactive` | Surface for grouped content. A `.form-table` inside a card loses its own frame. |
| `.wpb-grid`, `--2`, `--3`, `--4`, `--sidebar` | Responsive grid (auto-fill by default). |
| `.wpb-stack`, `.wpb-row`, `.wpb-row--between`, `.wpb-toolbar`, `__end` | Layout helpers. |
| `.wpb-field`, `__label`, `__control`, `__help`, `__suffix` | Stacked form field — alternative to `.form-table`. |
| `.wpb-toggle` + `input` + `.wpb-toggle__track` | Switch. |
| `.wpb-badge`, `--success`/`--warning`/`--error`/`--info`/`--accent`, `--dot` | Status labels (e.g. email log, post status). |
| `.wpb-alert`, `--success`/`--warning`/`--error`, `__body`, `__title`, `__dismiss` | Inline message. Use WP's `.notice` for page-level feedback after a save. |
| `.wpb-stat`, `__label`, `__value`, `__meta` | KPI tile. |
| `.wpb-kv` (`<dl>`) | Key/value list for details/status. |
| `.wpb-code` | Logs, raw output. |
| `.wpb-empty`, `__title` | Empty state. |
| `.wpb-spinner`, `--lg`; `.is-loading`; `.wpb-busy` | Loading states. `.button.is-loading` shows a spinner inside the button. |
| `.wpb-progress`, `__bar` | Progress bar. |
| `.wpb-modal` (`<dialog>`), `--wide`, `__header`, `__title`, `__close`, `__body`, `__footer` | Modal. |
| `.wpb-savebar` | Sticky bar at the bottom for long forms. |
| `.wpb-muted`, `.wpb-small`, `.wpb-mono`, `.wpb-text-success/-warning/-error`, `.wpb-visually-hidden` | Utilities. |
| `.button.wpb-button-danger`, `.wpb-link-danger` | Destructive actions. |

The full set is shown in `demo/index.html` (open it in a browser).

### JavaScript (data attributes)

| Attribute | Effect |
| --- | --- |
| `data-wpb-tabs` on `.nav-tab-wrapper` | Client-side tabs: links `href="#panel-id"` toggle `.wpb-tab-panel` elements, hash is kept in the URL. |
| `data-wpb-modal-open="dialog-id"` | Opens a `<dialog class="wpb-modal">`. Esc and backdrop click close it. |
| `data-wpb-modal-close` | Closes the surrounding dialog. |
| `data-wpb-reveal="input-id"` | Toggles a password field. |
| `data-wpb-copy="text"` / `data-wpb-copy-target="id"` | Copies to the clipboard and shows a toast. |
| `data-wpb-confirm="Message"` | Confirmation on click (links/buttons) or submit (forms). |
| `data-wpb-dismiss` | Removes the closest `.wpb-alert`. |

JS API on `window.wpbAdminUI`:

```js
wpbAdminUI.toast( 'Saved.', 'success' );        // success|warning|error|info, optional duration in ms (0 = sticky)
wpbAdminUI.openModal( 'my-dialog' );
wpbAdminUI.closeModal( 'my-dialog' );
wpbAdminUI.setLoading( buttonEl, true );          // spinner + disabled
wpbAdminUI.initTabs( navEl );                     // for tabs inserted after page load
```

Declare `wpb-admin-ui` as a dependency of a plugin script that uses the API:

```php
wp_enqueue_script( 'relaymint-admin', $url, array( 'wpb-admin-ui' ), RELAYMINT_VERSION, true );
```

### Design tokens

Override tokens in a plugin stylesheet only when there is a real reason — the point is consistency.

```css
body.wpb-admin {
	--wpb-content-width: 1400px;
}
```

| Token | Default |
| --- | --- |
| `--wpb-accent` / `--wpb-accent-hover` | Admin color scheme, fallback `#2271b1` / `#135e96` |
| `--wpb-bg`, `--wpb-surface`, `--wpb-surface-muted` | `#f5f6f8`, `#fff`, `#f9fafb` |
| `--wpb-border`, `--wpb-border-strong` | `#e4e7ec`, `#d0d5dd` |
| `--wpb-text`, `--wpb-text-muted`, `--wpb-text-subtle` | `#1d2327`, `#646970`, `#8c8f94` |
| `--wpb-success/-warning/-error/-info` (+ `-bg`, `-border`) | Status colors |
| `--wpb-radius-sm`, `--wpb-radius`, `--wpb-radius-lg` | `6px`, `10px`, `14px` |
| `--wpb-space-1` … `--wpb-space-6` | `4px` … `32px` |
| `--wpb-content-width` | `1200px` (max width of the centered content column) |

## Design principles

1. **Native first.** Use WordPress markup and classes (`.button`, `.notice`, `.form-table`, `WP_List_Table`). The framework restyles them; components fill the gaps.
2. **One accent, neutral surfaces.** Color is reserved for actions and status.
3. **White cards on a light gray page.** Group settings into cards, not loose tables.
4. **One primary button per view.**
5. **German UI texts are fine; class names, code and comments stay English.**
6. **No plugin-specific colors, radii or shadows.** Plugin CSS only handles layout that is truly unique to the plugin.

## Migrating an existing plugin

See [docs/migration.md](docs/migration.md) for a step-by-step guide and a per-plugin mapping of the current custom classes.

## Hooks & Filters

| Name | Type | Description |
| --- | --- | --- |
| `admin_body_class` | filter (core) | Used to add `wpb-admin` on registered screens. |
| `admin_enqueue_scripts` | action (core) | Used to enqueue `wpb-admin-ui` (CSS + JS) on registered screens. |

## Project structure

```
wp-backend-ui.php                 Loader + version negotiation (include this)
includes/class-wpb-admin-ui.php   Screen registration, assets, markup helpers
assets/css/wpb-admin.css          Tokens, core restyling layer, components
assets/js/wpb-admin.js            Data-attribute behaviours, toast/modal API
demo/index.html                   Styleguide with every component
examples/settings-page.php        Complete example page
docs/migration.md                 Migration guide for the existing plugins
```

## Updating the library in a plugin

1. Bump `VERSION` in `includes/class-wpb-admin-ui.php` **and** the candidate key in `wp-backend-ui.php` (they must match).
2. Copy the new version into the plugin's `lib/wp-backend-ui/` and release the plugin as usual (Plugin Update Checker / GitHub).

Because the newest copy wins, updating one plugin updates the look of all plugins on a site. Keep changes backward compatible within a major version.

## Changelog

### 1.0.2 – 2026-10-09
- Settings section headings get consistent spacing inside nested containers such as tab panels.
- Forms and settings tables inside cards no longer add extra space at the top and bottom of the card body.
- Header action buttons no longer overlap the "Screen Options" tab on list-table screens.

### 1.0.1 – 2026-10-09
- Center the page content column on registered screens, with symmetric gutters on all screen sizes.

### 1.0.0 – 2026-10-09
- Initial release: design tokens, core restyling layer, components, data-attribute JS, PHP helpers, version-negotiating loader, styleguide, migration guide.

## License

GPL-2.0-or-later
