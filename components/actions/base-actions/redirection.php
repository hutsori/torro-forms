<?php
/**
 * Email notifications Action
 *
 * Adds Email notifications for forms
 *
 * @author  awesome.ug, Author <support@awesome.ug>
 * @package TorroForms/Restrictions
 * @version 1.0.0
 * @since   1.0.0
 * @license GPL 2
 *
 * Copyright 2015 awesome.ug (support@awesome.ug)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Torro_Redirection_Action extends Torro_Action {
	private static $instance = null;

	/**
	 * Initializing.
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {
		parent::__construct();
	}

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Adds the text after submitting
	 *
	 * @param $response_id
	 * @param $response
	 */
	public function notification( $form_id, $response_id ) {
		$notification = get_post_meta( $form_id, 'redirect_text', true );

		return $notification;
	}

	public function option_content() {
		global $post;

		$redirect_type = get_post_meta( $post->ID, 'redirect_type', true );
		$redirect_text     = get_post_meta( $post->ID, 'redirect_text', true );

		if ( '' == $redirect_text ) {
			$redirect_text = esc_html__( 'Thank you for submitting!', 'torro-forms' );
		}

		$html = '<div id="form-redirections">';

		$html .= '<div class="actions">';
		$html .= '<p class="intro-text">' . esc_attr__( 'This notification will be shown after successfull submitting', 'torro-forms' ) . '</p>';
		$html .= '<select name="redirect_type">';

		$selected = $redirect_type == 'redirect_url' ? ' selected="selected"' : '';
		$html .= '<option value="redirect_url"' . $selected . '>' . esc_attr__( 'URL Redirection', 'torro-forms' ) . '</option>';

		$selected = $redirect_type == 'redirect_page' ? ' selected="selected"' : '';
		$html .= '<option value="redirect_page"' . $selected . '>' . esc_attr__( 'Page Redirection', 'torro-forms' ) . '</option>';

		$selected = $redirect_type == 'redirect_text' ? ' selected="selected"' : '';
		$html .= '<option value="redirect_text"' . $selected . '>' . esc_attr__( 'Text Message', 'torro-forms' ) . '</option>';

		$html .= '</select>';

		$html .= '</div>';

		$display = $redirect_type == 'redirect_url' ? ' style="display:block;"' : ' style="display:none;"';

		$html .= '<div class="redirect-content redirect-url"' . $display . '>';
		$html .= '<label for="redirect_url">' . esc_attr__( 'Url: ' ) . '</label><input name="redirect_url" type="text" value="" />';
		$html .= '</div>';

		$display = $redirect_type == 'redirect_page' ? ' style="display:block;"' : ' style="display:none;"';

		$html .= '<div class="redirect-content redirect-page"' . $display . '>';
		$html .= '<label for="redirect_page">' . esc_attr__( 'Page: ' ) . '</label><input name="redirect_page" type="text" value="" />';
		$html .= '</div>';

		$display = $redirect_type == 'redirect_text' ? ' style="display:block;"' : ' style="display:none;"';
		$html .= '<div class="redirect-content redirect-text"' . $display . '>';

		$settings = array( 'textarea_rows', 25 );

		ob_start();
		wp_editor( $redirect_text, 'redirect_text', $settings );
		$html .= ob_get_clean();

		$html .= '</div>';
		$html .= '</div>';

		$html .= '<div class="clear"></div>';

		return $html;
	}

	/**
	 * Saving option content
	 */
	public function save_option_content() {
		global $post;

		$redirect_type = $_POST[ 'redirect_type' ];
		update_post_meta( $post->ID, 'redirect_type', $redirect_type );

		$redirect_text = $_POST[ 'redirect_text' ];
		update_post_meta( $post->ID, 'redirect_text', $redirect_text );
	}

	protected function init() {
		$this->title = __( 'Redirections', 'torro-forms' );
		$this->name  = 'redirections';

		add_action( 'torro_formbuilder_save', array( $this, 'save_option_content' ) );
	}
}

torro()->actions()->add( 'Torro_Redirection_Action' );