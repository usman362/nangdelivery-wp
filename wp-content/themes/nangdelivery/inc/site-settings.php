<?php
/**
 * Site-wide editable settings + client handover guide.
 *
 * The Wix design baked a few site-wide strings into the page markup — most
 * notably the contact phone number (repeated on all 62 delivery-area pages) and
 * the footer copyright line (on every page). Re-editing those by hand would mean
 * touching dozens of files, so this exposes them as ordinary WordPress options
 * the client can change from one screen (Nang Delivery → Site Settings).
 *
 * IMPORTANT — zero-UI-change guarantee: the renderer only swaps a value when the
 * client has actually changed it from the approved default. Until then every
 * helper returns the exact original string and ndb_apply_site_settings() is a
 * pure no-op, so the default render stays byte-for-byte identical to the Wix
 * design. The swap is a plain str_replace of the original (unique) string for the
 * new one — no page-part file is modified.
 *
 * Native WP options (not ACF) are used here because this ACF build is the free
 * edition, which has no options-page support; output also stays ACF-independent.
 *
 * @package NangDelivery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* The approved Wix defaults. These are the literal strings present in the
   processed page parts; the swap below uses them as the search needles. */
const NDB_DEFAULT_PHONE     = '0488885201';                        // display number
const NDB_DEFAULT_PHONE_TEL = '+6148885201';                       // original tel: href value
const NDB_DEFAULT_COPYRIGHT = '&copy;2022 by Nang Delivery Brisbane'; // raw footer HTML

/* ---- Read helpers (ACF-independent; fall back to the approved default) ---- */

function ndb_site_phone() {
	$v = trim( (string) get_option( 'ndb_phone', '' ) );
	return '' !== $v ? $v : NDB_DEFAULT_PHONE;
}

/** tel: href digits for the configured phone. Preserves the original value
 *  exactly while unchanged; derives a clean +61 form once the client edits it. */
function ndb_site_phone_tel() {
	$phone = ndb_site_phone();
	if ( NDB_DEFAULT_PHONE === $phone ) {
		return NDB_DEFAULT_PHONE_TEL;
	}
	$digits = preg_replace( '~\D~', '', $phone );
	return '+61' . ltrim( $digits, '0' );
}

function ndb_site_copyright() {
	$v = trim( (string) get_option( 'ndb_footer_copyright', '' ) );
	return '' !== $v ? $v : NDB_DEFAULT_COPYRIGHT;
}

/**
 * Swap site-wide strings in rendered HTML — only where the client has changed a
 * value, so the untouched default output is byte-identical. Wired on the generic
 * content/shared filters and called explicitly from the area/post renderers.
 *
 * @param string $html Rendered HTML.
 * @return string
 */
function ndb_apply_site_settings( $html ) {
	if ( ! is_string( $html ) || '' === $html ) {
		return $html;
	}

	$phone = ndb_site_phone();
	if ( NDB_DEFAULT_PHONE !== $phone ) {
		$html = str_replace( NDB_DEFAULT_PHONE, esc_html( $phone ), $html );
		$html = str_replace( 'tel:' . NDB_DEFAULT_PHONE_TEL, 'tel:' . ndb_site_phone_tel(), $html );
	}

	$copyright = ndb_site_copyright();
	if ( NDB_DEFAULT_COPYRIGHT !== $copyright ) {
		// The original needle is raw HTML (&copy;…); escape the replacement so a
		// client typing "<", "&", etc. can never inject markup.
		$html = str_replace( NDB_DEFAULT_COPYRIGHT, esc_html( html_entity_decode( $copyright, ENT_QUOTES, 'UTF-8' ) ), $html );
	}

	return $html;
}
add_filter( 'ndb_page_content_html', 'ndb_apply_site_settings', 20, 1 );
add_filter( 'ndb_shared_html', 'ndb_apply_site_settings', 20, 1 );

/* --------------------------- Admin: register settings --------------------- */

add_action(
	'admin_init',
	function () {
		register_setting(
			'ndb_site_settings_group',
			'ndb_phone',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
		register_setting(
			'ndb_site_settings_group',
			'ndb_footer_copyright',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
	}
);

/* ----------------------------- Admin: menu + pages ------------------------ */

add_action(
	'admin_menu',
	function () {
		add_menu_page(
			__( 'Nang Delivery', 'nangdelivery' ),
			__( 'Nang Delivery', 'nangdelivery' ),
			'manage_options',
			'ndb-settings',
			'ndb_render_settings_page',
			'dashicons-store',
			59
		);
		add_submenu_page(
			'ndb-settings',
			__( 'Site Settings', 'nangdelivery' ),
			__( 'Site Settings', 'nangdelivery' ),
			'manage_options',
			'ndb-settings',
			'ndb_render_settings_page'
		);
		add_submenu_page(
			'ndb-settings',
			__( 'Editing Guide', 'nangdelivery' ),
			__( 'Editing Guide', 'nangdelivery' ),
			'edit_pages',
			'ndb-guide',
			'ndb_render_guide_page'
		);
	}
);

function ndb_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Site Settings', 'nangdelivery' ); ?></h1>
		<p><?php esc_html_e( 'These values appear across the whole site. Leave a field blank to keep the original approved text.', 'nangdelivery' ); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'ndb_site_settings_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ndb_phone"><?php esc_html_e( 'Contact phone number', 'nangdelivery' ); ?></label></th>
					<td>
						<input name="ndb_phone" id="ndb_phone" type="text" class="regular-text"
							value="<?php echo esc_attr( get_option( 'ndb_phone', '' ) ); ?>"
							placeholder="<?php echo esc_attr( NDB_DEFAULT_PHONE ); ?>" />
						<p class="description">
							<?php
							/* translators: %s: the default phone number */
							printf( esc_html__( 'Shown on every delivery-area page. Default: %s', 'nangdelivery' ), '<code>' . esc_html( NDB_DEFAULT_PHONE ) . '</code>' );
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ndb_footer_copyright"><?php esc_html_e( 'Footer copyright line', 'nangdelivery' ); ?></label></th>
					<td>
						<input name="ndb_footer_copyright" id="ndb_footer_copyright" type="text" class="regular-text"
							value="<?php echo esc_attr( get_option( 'ndb_footer_copyright', '' ) ); ?>"
							placeholder="<?php echo esc_attr( html_entity_decode( NDB_DEFAULT_COPYRIGHT, ENT_QUOTES, 'UTF-8' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Appears in the footer on every page.', 'nangdelivery' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * In-admin client handover guide (Nang Delivery → Editing Guide).
 *
 * Plain how-to for the people who will own the site day-to-day, plus a short
 * "for your developer" section at the end. Pure static content — no input is
 * processed here — but every dynamic value is still escaped on output.
 */
function ndb_render_guide_page() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Editing Guide', 'nangdelivery' ); ?></h1>
		<p style="max-width:46em;"><?php esc_html_e( 'This site was hand-built to match your approved design exactly. You can freely change the words, prices, products, and blog posts from the normal WordPress screens. The layout, colours, fonts, and spacing are locked to the approved look, so editing text never changes the design.', 'nangdelivery' ); ?></p>

		<h2><?php esc_html_e( '1. Editing the text on a page', 'nangdelivery' ); ?></h2>
		<p style="max-width:46em;"><?php esc_html_e( 'Open Pages and pick the page you want to change. Each editable block of text appears as its own field. Type your new wording and press Update — the new text drops straight into the approved design in the same spot, with the same styling.', 'nangdelivery' ); ?></p>
		<p style="max-width:46em;"><strong><?php esc_html_e( 'Good to know:', 'nangdelivery' ); ?></strong> <?php esc_html_e( 'The editing box shows simple formatting buttons (bold, links, lists). The site ignores font, size, and colour changes made there on purpose — that is what keeps every page on-brand. To change wording, just retype it; to change a link, use the link button.', 'nangdelivery' ); ?></p>

		<h2><?php esc_html_e( '2. Site Settings — phone number & footer', 'nangdelivery' ); ?></h2>
		<p style="max-width:46em;">
			<?php
			printf(
				/* translators: %s: the Site Settings menu label */
				esc_html__( 'Two pieces of text appear on every page, so they have their own screen under %s. Change them once there and they update everywhere automatically:', 'nangdelivery' ),
				'<strong>' . esc_html__( 'Nang Delivery → Site Settings', 'nangdelivery' ) . '</strong>'
			);
			?>
		</p>
		<ul style="list-style:disc;margin-left:2em;max-width:46em;">
			<li><strong><?php esc_html_e( 'Contact phone number', 'nangdelivery' ); ?></strong> — <?php esc_html_e( 'shown on every delivery-area page and wired into the click-to-call link.', 'nangdelivery' ); ?></li>
			<li><strong><?php esc_html_e( 'Footer copyright line', 'nangdelivery' ); ?></strong> — <?php esc_html_e( 'the small line at the very bottom of every page.', 'nangdelivery' ); ?></li>
		</ul>
		<p style="max-width:46em;"><?php esc_html_e( 'Leave a field blank to keep the original approved text. A value only replaces the original once you actually type something and save.', 'nangdelivery' ); ?></p>

		<h2><?php esc_html_e( '3. Products, blog posts & delivery areas', 'nangdelivery' ); ?></h2>
		<ul style="list-style:disc;margin-left:2em;max-width:46em;">
			<li><strong><?php esc_html_e( 'Products', 'nangdelivery' ); ?></strong> — <?php esc_html_e( 'edited under Products (WooCommerce). Prices, names, and stock are changed there.', 'nangdelivery' ); ?></li>
			<li><strong><?php esc_html_e( 'Blog posts', 'nangdelivery' ); ?></strong> — <?php esc_html_e( 'edited under Posts. Add a new post the normal way; it appears in the blog automatically.', 'nangdelivery' ); ?></li>
			<li><strong><?php esc_html_e( 'Delivery areas (suburbs)', 'nangdelivery' ); ?></strong> — <?php esc_html_e( 'edited under Delivery Areas. Each suburb has three text fields (intro, main description, closing note) you can rewrite.', 'nangdelivery' ); ?></li>
		</ul>

		<h2><?php esc_html_e( '4. Enquiry & contact form messages', 'nangdelivery' ); ?></h2>
		<p style="max-width:46em;">
			<?php
			printf(
				/* translators: %s: the Form Messages menu label */
				esc_html__( 'Every submission from the site forms is saved on the site under %s, so nothing is lost even if an email fails to arrive.', 'nangdelivery' ),
				'<strong>' . esc_html__( 'Form Messages', 'nangdelivery' ) . '</strong>'
			);
			?>
		</p>
		<p style="max-width:46em;"><strong><?php esc_html_e( 'Email delivery:', 'nangdelivery' ); ?></strong> <?php esc_html_e( 'WordPress sends a copy of each message to the site admin email by default. Shared hosting often sends these unreliably (they can land in spam). For dependable delivery, ask your developer to connect an SMTP service (for example an SMTP plugin or a transactional email provider). The on-site Form Messages list is always the source of truth.', 'nangdelivery' ); ?></p>

		<hr style="margin:2em 0;max-width:46em;" />

		<h2><?php esc_html_e( 'For your developer', 'nangdelivery' ); ?></h2>
		<ul style="list-style:disc;margin-left:2em;max-width:46em;">
			<li><?php esc_html_e( 'Editable rich-text blocks are ACF WYSIWYG fields whose field name is the meta key the renderer reads; output stays ACF-independent (raw meta is read with the approved default as fallback), so deactivating ACF degrades the editing UI but never the front end.', 'nangdelivery' ); ?></li>
			<li><?php esc_html_e( 'Site-wide phone/copyright use a no-op-by-default swap: the renderer only str_replaces the original string once the option differs from the approved default, so an untouched install renders byte-identical to the approved design.', 'nangdelivery' ); ?></li>
			<li>
				<?php
				printf(
					/* translators: %s: the filter name, shown in code style */
					esc_html__( 'Change the form recipient address with the %s filter rather than hard-coding it.', 'nangdelivery' ),
					'<code>ndb_form_recipient</code>'
				);
				?>
			</li>
			<li><?php esc_html_e( 'Asset URLs and the search action use root-relative paths (e.g. /?s=…, /wp-content/themes/…); this assumes WordPress is installed at the domain root. If the site moves into a subdirectory, those paths must be re-based.', 'nangdelivery' ); ?></li>
		</ul>
	</div>
	<?php
}
