<?php
/**
 * Restrict form to all Visitors of site and does some checks
 *
 * Retriction functions for visitors
 *
 * @author  awesome.ug, Author <support@awesome.ug>
 * @package Questions/Restrictions
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

if( !defined( 'ABSPATH' ) ){
	exit;
}

class Questions_Restriction_AllVisitors extends Questions_Restriction
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->title = __( 'All Visitors', 'wcsc-locale' );
		$this->slug = 'allvisitors';
		$this->option_name = __( 'All Visitors of site', 'wcsc-locale' );

		add_action( 'init', array( $this, 'enqueue_fingerprint_scipts' ) );
		add_action( 'questions_save_form', array( $this, 'save_settings' ), 10, 1 );

		add_action( 'questions_form_end', array( $this, 'add_fingerprint_input' ) );

		add_action( 'questions_save_response', array( $this, 'set_cookie' ), 10 );
		add_action( 'questions_save_response', array( $this, 'save_ip' ), 10 );
		add_action( 'questions_save_response', array( $this, 'save_fingerprint' ), 10 );
		add_action( 'wp_ajax_questions_check_fngrprnt', array( __CLASS__, 'ajax_check_fingerprint' ) );
	}

	/**
	 * Adds content to the option
	 */
	public function option_content()
	{
		global $post;

		$form_id = $post->ID;

		$html = '<h3>' . esc_attr( 'Restrict Visitors', 'questions-locale' ) . '</h3>';

		/**
		 * Check IP
		 */
		$restrictions_check_ip = get_post_meta( $form_id, 'questions_restrictions_check_ip', TRUE );
		$checked = 'yes' == $restrictions_check_ip ? ' checked' : '';

		$html .= '<div class="questions-restrictions-allvisitors-userfilter">';
		$html .= '<input type="checkbox" name="questions_restrictions_check_ip" value="yes" ' . $checked . '/>';
		$html .= '<label for="questions_restrictions_check_ip">' . esc_attr( 'Prevent multiple entries from same IP', 'questions-locale' ) . '</label>';
		$html .= '</div>';

		/**
		 * Check Cookie
		 */
		$restrictions_check_cookie = get_post_meta( $form_id, 'questions_restrictions_check_cookie', TRUE );
		$checked = 'yes' == $restrictions_check_cookie ? ' checked' : '';

		$html .= '<div class="questions-restrictions-allvisitors-userfilter">';
		$html .= '<input type="checkbox" name="questions_restrictions_check_cookie" value="yes" ' . $checked . '/>';
		$html .= '<label for="questions_restrictions_check_cookie">' . esc_attr( 'Prevent multiple entries by checking cookie', 'questions-locale' ) . '</label>';
		$html .= '</div>';

		/**
		 * Check browser fingerprint
		 */
		$restrictions_check_fingerprint = get_post_meta( $form_id, 'questions_restrictions_check_fingerprint', TRUE );
		$checked = 'yes' == $restrictions_check_fingerprint ? ' checked' : '';

		$html .= '<div class="questions-restrictions-allvisitors-userfilter">';
		$html .= '<input type="checkbox" name="questions_restrictions_check_fingerprint" value="yes" ' . $checked . '/>';
		$html .= '<label for="questions_restrictions_check_fingerprint">' . esc_attr( 'Prevent multiple entries by checking browser fingerprint', 'questions-locale' ) . '</label>';
		$html .= '</div>';

		ob_start();
		do_action( 'questions_restrictions_allvisitors_userfilters' );
		$html .= ob_get_clean();

		return $html;
	}



	/**
	 * Checks if the user can pass
	 */
	public function check()
	{
		global $questions_form_id, $questions_skip_fingerrint_check;

		$restrictions_check_ip = get_post_meta( $questions_form_id, 'questions_restrictions_check_ip', TRUE );

		if( 'yes' == $restrictions_check_ip && $this->ip_has_participated() ){
			$this->add_message( 'error', esc_attr( 'You have already entered your data.', 'wcsc-locale' ) );

			return FALSE;
		}

		$restrictions_check_cookie = get_post_meta( $questions_form_id, 'questions_restrictions_check_cookie', TRUE );

		if( 'yes' == $restrictions_check_cookie && isset( $_COOKIE[ 'questions_has_participated_form_' . $questions_form_id ] )  ){

			if( $_COOKIE[ 'questions_has_participated_form_' . $questions_form_id ] == 'yes' ){
				$this->add_message( 'error', esc_attr( 'You have already entered your data.', 'wcsc-locale' ) );
			}

			return FALSE;
		}

		$restrictions_check_fingerprint = get_post_meta( $questions_form_id, 'questions_restrictions_check_fingerprint', TRUE );

		if( 'yes' == $restrictions_check_fingerprint && $questions_skip_fingerrint_check != TRUE ){

			$html = '<script language="JavaScript">
					    (function ($) {
							"use strict";
							$( function () {
					            new Fingerprint2().get(function(fngrprnt){

								    var data = {
										action: \'questions_check_fngrprnt\',
										questions_form_id: ' . $questions_form_id . ',
										action_url: \'' . $_SERVER[ 'REQUEST_URI' ] . '\',
										fngrprnt: fngrprnt
								    };

								    var ajaxurl = "' . admin_url( 'admin-ajax.php' ) . '";

								    $.post( ajaxurl, data, function( response ) {
								        $( \'#questions-ajax-form\' ).html( response );
								        $( \'#questions-fngrprnt\' ).val( fngrprnt );
								    });
						        });
							});
						}(jQuery))
					  </script><div id="questions-ajax-form"></div>';

			$this->add_message( 'check', $html );

			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Checking browser fingerprint by ajax
	 */
	public static function ajax_check_fingerprint()
	{
		global $wpdb, $questions_global, $questions_form_id, $questions_skip_fingerrint_check;

		$content = '';
		$restrict = FALSE;

		if( !isset( $_POST[ 'questions_form_id' ]) ){
			$content.= esc_attr( 'Question form ID is missing.'. 'questions-locale' );
			$restrict = TRUE;
		}

		if( !isset( $_POST[ 'fngrprnt' ]) ){
			$content.= esc_attr( 'Error on processing form'. 'questions-locale' );
			$restrict = TRUE;
		}

		if( FALSE == $restrict ){
			$questions_form_id = $_POST[ 'questions_form_id' ];
			$fingerprint = $_POST[ 'fngrprnt' ];

			$sql = $wpdb->prepare( "SELECT COUNT(*) FROM {$questions_global->tables->responds} WHERE questions_id=%d AND cookie_key=%s", $questions_form_id, $fingerprint );
			$count = $wpdb->get_var( $sql );

			if( 0 == $count ){
				$questions_skip_fingerrint_check = TRUE;

				$questions_process = new Questions_FormProcess( $questions_form_id, $_POST[ 'action_url' ] );
				$content .= $questions_process->show_form();
			}else{
				$content .= '<div class="questions-message error">' . esc_attr( 'You have already entered your data.', 'questions-locale' ) . '</div>';
			}
		}

		echo $content;
		die();
	}

	/**
	 * Setting Cookie for one year
	 */
	public function set_cookie(){
		global $questions_form_id;
		setcookie( 'questions_has_participated_form_' . $questions_form_id, 'yes', time() + 60 * 60 * 24 * 365 );
	}

	/**
	 * Setting Cookie for one year
	 */
	public function save_ip( $response_id ){
		global $wpdb, $questions_global, $questions_form_id;

		$restrictions_check_ip = get_post_meta( $questions_form_id, 'questions_restrictions_check_ip', TRUE );
		if( '' == $restrictions_check_ip )
			return;

		// Adding IP to response
		$wpdb->update(
			$questions_global->tables->responds,
			array(
				'remote_addr' => $_SERVER[ 'REMOTE_ADDR' ],	// string
			),
			array(
				'id' => $response_id,
			)
		);
	}

	/**
	 * Setting Cookie for one year
	 */
	public function save_fingerprint( $response_id ){
		global $wpdb, $questions_global, $questions_form_id;

		$restrictions_check_fingerprint = get_post_meta( $questions_form_id, 'questions_restrictions_check_fingerprint', TRUE );
		if( '' == $restrictions_check_fingerprint )
			return;

		$wpdb->update(
			$questions_global->tables->responds,
			array(
				'cookie_key' => $_POST[ 'questions_fngrprnt' ],	// string
			),
			array(
				'id' => $response_id,
			)
		);
	}

	/**
	 * Adding fingerprint post field
	 */
	public function add_fingerprint_input(){
		global $questions_form_id;

		$restrictions_check_fingerprint = get_post_meta( $questions_form_id, 'questions_restrictions_check_fingerprint', TRUE );
		if( '' == $restrictions_check_fingerprint )
			return;

		echo '<input type="hidden" id="questions-fngrprnt" name="questions_fngrprnt" />';
	}

	/**
	 * Has IP already participated
	 *
	 * @param $questions_id
	 * @return bool $has_participated
	 * @since 1.0.0
	 *
	 */
	public function ip_has_participated()
	{
		global $wpdb, $questions_global, $questions_form_id;

		$remote_ip = $_SERVER[ 'REMOTE_ADDR' ];

		$sql = $wpdb->prepare( "SELECT COUNT(*) FROM {$questions_global->tables->responds} WHERE questions_id=%d AND remote_addr=%s", $questions_form_id, $remote_ip );
		$count = $wpdb->get_var( $sql );

		if( 0 == $count ){
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Saving data
	 *
	 * @param int $form_id
	 * @since 1.0.0
	 */
	public static function save_settings( $form_id )
	{
		/**
		 * Check IP
		 */
		if( array_key_exists( 'questions_restrictions_check_ip', $_POST ) ){
			$restrictions_check_ip = $_POST[ 'questions_restrictions_check_ip' ];
			update_post_meta( $form_id, 'questions_restrictions_check_ip', $restrictions_check_ip );
		}else{
			update_post_meta( $form_id, 'questions_restrictions_check_ip', '' );
		}

		/**
		 * Check Cookie
		 */
		if( array_key_exists( 'questions_restrictions_check_cookie', $_POST ) ){
			$restrictions_check_cookie = $_POST[ 'questions_restrictions_check_cookie' ];
			update_post_meta( $form_id, 'questions_restrictions_check_cookie', $restrictions_check_cookie );
		}else{
			update_post_meta( $form_id, 'questions_restrictions_check_cookie', '' );
		}

		/**
		 * Check browser fingerprint
		 */
		if( array_key_exists( 'questions_restrictions_check_fingerprint', $_POST ) ){
			$restrictions_check_fingerprint = $_POST[ 'questions_restrictions_check_fingerprint' ];
			update_post_meta( $form_id, 'questions_restrictions_check_fingerprint', $restrictions_check_fingerprint );
		}else{
			update_post_meta( $form_id, 'questions_restrictions_check_fingerprint', '' );
		}
	}

	/**
	 * Enqueueing fingerprint scripts
	 */
	public static function enqueue_fingerprint_scipts(){
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'load_fingerprint_scripts' ) );
	}

	/**
	 * Loading fingerprint scripts
	 */
	public static function load_fingerprint_scripts(){
		wp_enqueue_script( 'admin-questions-restrictions-fingerprint-script', QUESTIONS_URLPATH . '/components/restrictions/base-restrictions/includes/js/detection.min.js' );
	}

}

qu_register_restriction( 'Questions_Restriction_AllVisitors' );
