<?php
/**
 * Front-end form handlers wired to native WordPress.
 *
 * The approved Wix forms were JS-driven: their markup carried no action/method
 * (and the message textareas had no name), so nothing submitted. We add ONLY
 * hidden plumbing to the markup — a form action/method, a WP nonce, a form-type
 * marker, field names and an off-screen honeypot — so the visible, client-
 * approved design is byte-identical, while submissions are processed
 * server-side through admin-post.php.
 *
 * Three forms share one handler (`ndb_form`), distinguished by a hidden
 * ndb_form_type marker:
 *   contact   — Contact page  (email, subject, message)
 *   area      — Delivery Areas page enquiry (suburb, post code, email, message)
 *   subscribe — shared footer newsletter (email)
 *
 * Template tokens (filled via the `ndb_page_content_html` / `ndb_shared_html`
 * filters, so render.php stays generic):
 *   %%FORM_ACTION%%       → admin-post.php endpoint (form action)
 *   %%FORM_NONCE%%        → wp_nonce_field() hidden inputs
 *   %%CONTACT_NOTICE%%    → contact post-submission banner   (reads ?ndb_contact)
 *   %%AREA_NOTICE%%       → area enquiry banner               (reads ?ndb_area)
 *   %%SUBSCRIBE_NOTICE%%  → footer subscribe banner           (reads ?ndb_subscribe)
 * Every notice is empty on a normal page load, so the default render stays
 * byte-identical to the approved design.
 *
 * @package NangDelivery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** admin-post.php action slug shared by every front-end form. */
const NDB_FORM_ACTION = 'ndb_form';

/** Whitelisted form types → the redirect status param each uses. */
function ndb_form_status_params() {
	return array(
		'contact'   => 'ndb_contact',
		'area'      => 'ndb_area',
		'subscribe' => 'ndb_subscribe',
	);
}

/**
 * Private CPT that stores every submission, so a message is never lost when the
 * host's outbound mail fails (common on fresh installs without SMTP). Visible to
 * the client in wp-admin (read-only — new entries come only from the forms).
 */
add_action(
	'init',
	function () {
		register_post_type(
			'ndb_message',
			array(
				'labels'              => array(
					'name'          => __( 'Form Messages', 'nangdelivery' ),
					'singular_name' => __( 'Form Message', 'nangdelivery' ),
					'menu_name'     => __( 'Form Messages', 'nangdelivery' ),
					'all_items'     => __( 'All Messages', 'nangdelivery' ),
					'search_items'  => __( 'Search Messages', 'nangdelivery' ),
					'not_found'     => __( 'No messages yet.', 'nangdelivery' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_position'       => 26,
				'menu_icon'           => 'dashicons-email-alt',
				'capability_type'     => 'post',
				'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'        => true,
				'supports'            => array( 'title', 'editor' ),
				'exclude_from_search' => true,
				'has_archive'         => false,
				'rewrite'             => false,
				'show_in_rest'        => false,
			)
		);
	}
);

/**
 * Resolve the redirect target + the per-type status param, then bail.
 *
 * @param string $type   Form type.
 * @param string $status 'sent' | 'error'.
 * @param string $back   URL to return to.
 */
function ndb_form_redirect( $type, $status, $back ) {
	$params = ndb_form_status_params();
	$key    = isset( $params[ $type ] ) ? $params[ $type ] : 'ndb_contact';
	wp_safe_redirect( add_query_arg( $key, $status, $back ) );
	exit;
}

/**
 * Handle any front-end form POST: verify nonce + honeypot, validate per type,
 * store the submission, email the site admin, then redirect back with a flag.
 */
function ndb_handle_form() {
	$back = wp_get_referer();
	if ( ! $back ) {
		$back = home_url( '/' );
	}

	$type   = isset( $_POST['ndb_form_type'] ) ? sanitize_key( wp_unslash( $_POST['ndb_form_type'] ) ) : '';
	$params = ndb_form_status_params();
	if ( ! isset( $params[ $type ] ) ) {
		$type = 'contact';
	}

	// Nonce — a failed/forged submission gets a generic error.
	if ( ! isset( $_POST['ndb_form_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ndb_form_nonce'] ) ), NDB_FORM_ACTION )
	) {
		ndb_form_redirect( $type, 'error', $back );
	}

	// Honeypot — a filled hidden field means a bot. Pretend success so it can't
	// probe which field gave it away; store/send nothing.
	if ( ! empty( $_POST['ndb_hp'] ) ) {
		ndb_form_redirect( $type, 'sent', $back );
	}

	$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	// Per-type validation, record title and email body.
	$title      = '';
	$body       = '';
	$lines      = array();
	$extra_meta = array();

	if ( 'contact' === $type ) {
		$subject = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
		if ( ! is_email( $email ) || '' === trim( $message ) ) {
			ndb_form_redirect( $type, 'error', $back );
		}
		if ( '' === $subject ) {
			$subject = __( '(no subject)', 'nangdelivery' );
		}
		$title              = sprintf( '%s — %s', $subject, $email );
		$lines[]            = sprintf( __( 'Subject: %s', 'nangdelivery' ), $subject );
		$lines[]            = sprintf( __( 'From: %s', 'nangdelivery' ), $email );
		$lines[]            = '';
		$lines[]            = __( 'Message:', 'nangdelivery' );
		$lines[]            = $message;
		$body               = $message;
		$extra_meta['_ndb_subject'] = $subject;

	} elseif ( 'area' === $type ) {
		$suburb   = isset( $_POST['suburb-name'] ) ? sanitize_text_field( wp_unslash( $_POST['suburb-name'] ) ) : '';
		$postcode = isset( $_POST['post-code'] ) ? sanitize_text_field( wp_unslash( $_POST['post-code'] ) ) : '';
		if ( ! is_email( $email ) || '' === trim( $suburb ) ) {
			ndb_form_redirect( $type, 'error', $back );
		}
		$title   = sprintf(
			/* translators: 1: suburb, 2: post code */
			__( 'Area enquiry: %1$s %2$s', 'nangdelivery' ),
			$suburb,
			$postcode
		);
		$lines[] = sprintf( __( 'Suburb: %s', 'nangdelivery' ), $suburb );
		$lines[] = sprintf( __( 'Post code: %s', 'nangdelivery' ), $postcode );
		$lines[] = sprintf( __( 'Email: %s', 'nangdelivery' ), $email );
		if ( '' !== trim( $message ) ) {
			$lines[] = '';
			$lines[] = __( 'Message:', 'nangdelivery' );
			$lines[] = $message;
		}
		$body = trim( $message );

	} elseif ( 'subscribe' === $type ) {
		if ( ! is_email( $email ) ) {
			ndb_form_redirect( $type, 'error', $back );
		}
		$title   = sprintf( __( 'Newsletter subscribe: %s', 'nangdelivery' ), $email );
		$lines[] = sprintf( __( 'Email: %s', 'nangdelivery' ), $email );
	}

	// Persist first (reliable), then attempt delivery.
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'ndb_message',
			'post_status'  => 'private',
			'post_title'   => $title,
			'post_content' => $body,
		),
		true
	);
	if ( ! is_wp_error( $post_id ) && $post_id ) {
		update_post_meta( $post_id, '_ndb_source', $type );
		if ( '' !== $email ) {
			update_post_meta( $post_id, '_ndb_from_email', $email );
		}
		foreach ( $extra_meta as $mk => $mv ) {
			update_post_meta( $post_id, $mk, $mv );
		}
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			update_post_meta( $post_id, '_ndb_ip', sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) );
		}
	}

	// Notify the site admin (recipient filterable for handover).
	$to       = apply_filters( 'ndb_form_recipient', get_option( 'admin_email' ), $type );
	$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	$subjects = array(
		'contact'   => __( 'Contact form', 'nangdelivery' ),
		'area'      => __( 'Delivery area enquiry', 'nangdelivery' ),
		'subscribe' => __( 'Newsletter subscribe', 'nangdelivery' ),
	);
	$mail_subject = sprintf( '[%s] %s', $blogname, $subjects[ $type ] );
	$mail_body    = implode( "\n", $lines ) . "\n";
	$headers      = array( 'Content-Type: text/plain; charset=UTF-8' );
	if ( '' !== $email ) {
		$headers[] = sprintf( 'Reply-To: %s', $email );
	}
	wp_mail( $to, $mail_subject, $mail_body, $headers );

	ndb_form_redirect( $type, 'sent', $back );
}
add_action( 'admin_post_nopriv_' . NDB_FORM_ACTION, 'ndb_handle_form' );
add_action( 'admin_post_' . NDB_FORM_ACTION, 'ndb_handle_form' );

/**
 * Render a post-submission banner for the given status query param. Empty when
 * the param is absent, so the default render stays byte-identical. Styled inline
 * to read as native chrome without touching the global stylesheet.
 *
 * @param string $param    Query-string key (e.g. ndb_contact).
 * @param string $sent_msg Success copy.
 * @return string
 */
function ndb_form_notice_html( $param, $sent_msg ) {
	if ( empty( $_GET[ $param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return '';
	}
	$status = sanitize_key( wp_unslash( $_GET[ $param ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 'sent' === $status ) {
		$msg = $sent_msg;
		$bg  = '#1a7f37';
	} elseif ( 'error' === $status ) {
		$msg = __( 'Sorry, that couldn’t be sent. Please check your details and try again.', 'nangdelivery' );
		$bg  = '#b3261e';
	} else {
		return '';
	}
	return '<div class="ndb-form-notice ndb-form-notice--' . esc_attr( $status )
		. '" role="status" aria-live="polite" style="margin:0 0 16px;padding:12px 16px;border-radius:6px;'
		. 'font:500 15px/1.5 inherit;color:#fff;background:' . $bg . ';">'
		. esc_html( $msg ) . '</div>';
}

/**
 * Fill forms-specific tokens in processed page content and shared partials.
 * Registered for every render path; the strpos guards make it a no-op where no
 * form is present. Tokens always resolve, so they can never leak to the page.
 *
 * @param string $html HTML to process.
 * @return string
 */
function ndb_fill_form_tokens( $html ) {
	if ( false === strpos( $html, '%%' ) ) {
		return $html;
	}
	if ( false !== strpos( $html, '%%FORM_ACTION%%' ) ) {
		$html = str_replace( '%%FORM_ACTION%%', esc_url( admin_url( 'admin-post.php' ) ), $html );
	}
	if ( false !== strpos( $html, '%%FORM_NONCE%%' ) ) {
		$html = str_replace(
			'%%FORM_NONCE%%',
			wp_nonce_field( NDB_FORM_ACTION, 'ndb_form_nonce', true, false ),
			$html
		);
	}
	if ( false !== strpos( $html, '%%CONTACT_NOTICE%%' ) ) {
		$html = str_replace(
			'%%CONTACT_NOTICE%%',
			ndb_form_notice_html( 'ndb_contact', __( 'Thanks — your message has been sent. We’ll be in touch soon.', 'nangdelivery' ) ),
			$html
		);
	}
	if ( false !== strpos( $html, '%%AREA_NOTICE%%' ) ) {
		$html = str_replace(
			'%%AREA_NOTICE%%',
			ndb_form_notice_html( 'ndb_area', __( 'Thanks — we’ve received your enquiry and will confirm delivery to your area shortly.', 'nangdelivery' ) ),
			$html
		);
	}
	if ( false !== strpos( $html, '%%SUBSCRIBE_NOTICE%%' ) ) {
		$html = str_replace(
			'%%SUBSCRIBE_NOTICE%%',
			ndb_form_notice_html( 'ndb_subscribe', __( 'Thanks for subscribing!', 'nangdelivery' ) ),
			$html
		);
	}
	return $html;
}
add_filter( 'ndb_page_content_html', 'ndb_fill_form_tokens', 10, 1 );
add_filter( 'ndb_shared_html', 'ndb_fill_form_tokens', 10, 1 );
