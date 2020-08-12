<?php
/*
Copyright (c) 2020 Kai Thoene

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

/**
 * Post type declaration file.
 *
 * @package WordPress Plugin Recursive Shortcode/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post type declaration class.
 */
class Recursive_Shortcode_Post_Type {

	/**
	 * The name for the custom post type.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $post_type;

	/**
	 * The plural name for the custom post type posts.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $plural;

	/**
	 * The singular name for the custom post type posts.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $single;

	/**
	 * The description of the custom post type.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $description;

	/**
	 * The options of the custom post type.
	 *
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $options;

	/**
	 * Constructor
	 *
	 * @param string $post_type Post type.
	 * @param string $plural Post type plural name.
	 * @param string $single Post type singular name.
	 * @param string $description Post type description.
	 * @param array  $options Post type options.
	 */
	public function __construct( $post_type = '', $plural = '', $single = '', $description = '', $options = array() ) {

		if ( ! $post_type || ! $plural || ! $single ) {
			return;
		}

		// Post type name and labels.
		$this->post_type   = $post_type;
		$this->plural      = $plural;
		$this->single      = $single;
		$this->description = $description;
		$this->options     = $options;

		// Regsiter post type.
		add_action( 'init', array( $this, 'register_post_type' ) );

		// Display custom update messages for posts edits.
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
		add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_updated_messages' ), 10, 2 );
	}

	/**
	 * Register new post type
	 *
	 * @return void
	 */
	public function register_post_type() {
		//phpcs:disable
		$labels = array(
			'name'               => $this->plural,
			'singular_name'      => $this->single,
			'name_admin_bar'     => $this->single,
			'add_new'            => _x( 'Add New', $this->post_type, 'recursive-shortcode' ),
			'add_new_item'       => sprintf( __( 'Add New %s', 'recursive-shortcode' ), $this->single ),
			'edit_item'          => sprintf( __( 'Edit %s', 'recursive-shortcode' ), $this->single ),
			'new_item'           => sprintf( __( 'New %s', 'recursive-shortcode' ), $this->single ),
			'all_items'          => sprintf( __( 'All %s', 'recursive-shortcode' ), $this->plural ),
			'view_item'          => sprintf( __( 'View %s', 'recursive-shortcode' ), $this->single ),
			'search_items'       => sprintf( __( 'Search %s', 'recursive-shortcode' ), $this->plural ),
			'not_found'          => sprintf( __( 'No %s Found', 'recursive-shortcode' ), $this->plural ),
			'not_found_in_trash' => sprintf( __( 'No %s Found In Trash', 'recursive-shortcode' ), $this->plural ),
			'parent_item_colon'  => sprintf( __( 'Parent %s' ), $this->single ),
			'menu_name'          => $this->plural,
		);
		//phpcs:enable

		$args = array(
			'labels'                => apply_filters( $this->post_type . '_labels', $labels ),
			'description'           => $this->description,
			'public'                => true,
			'publicly_queryable'    => true,
			'exclude_from_search'   => false,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => true,
			'query_var'             => true,
			'can_export'            => true,
			'rewrite'               => true,
			'capability_type'       => 'post',
			'has_archive'           => true,
			'hierarchical'          => true,
			'show_in_rest'          => true,
			'rest_base'             => $this->post_type,
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'supports'              => array( 'title', 'editor', 'excerpt', 'comments', 'thumbnail' ),
			'menu_position'         => 5,
			'menu_icon'             => 'dashicons-admin-post',
		);

		$args = array_merge( $args, $this->options );

		register_post_type( $this->post_type, apply_filters( $this->post_type . '_register_args', $args, $this->post_type ) );
	}

	/**
	 * Set up admin messages for post type
	 *
	 * @param  array $messages Default message.
	 * @return array           Modified messages.
	 */
	public function updated_messages( $messages = array() ) {
		global $post, $post_ID;
		//phpcs:disable
		$messages[ $this->post_type ] = array(
			0  => '',
			1  => sprintf( __( '%1$s updated. %2$sView %3$s%4$s.', 'recursive-shortcode' ), $this->single, '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', $this->single, '</a>' ),
			2  => __( 'Custom field updated.', 'recursive-shortcode' ),
			3  => __( 'Custom field deleted.', 'recursive-shortcode' ),
			4  => sprintf( __( '%1$s updated.', 'recursive-shortcode' ), $this->single ),
			5  => isset( $_GET['revision'] ) and sanitize_text_field(wp_unslash($_GET['revision'])) ? sprintf( __( '%1$s restored to revision from %2$s.', 'recursive-shortcode' ), $this->single, wp_post_revision_title( (int) sanitize_text_field(wp_unslash($_GET['revision'])), false ) ) : false,
			6  => sprintf( __( '%1$s published. %2$sView %3$s%4s.', 'recursive-shortcode' ), $this->single, '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', $this->single, '</a>' ),
			7  => sprintf( __( '%1$s saved.', 'recursive-shortcode' ), $this->single ),
			8  => sprintf( __( '%1$s submitted. %2$sPreview post%3$s%4$s.', 'recursive-shortcode' ), $this->single, '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', $this->single, '</a>' ),
			9  => sprintf( __( '%1$s scheduled for: %2$s. %3$sPreview %4$s%5$s.', 'recursive-shortcode' ), $this->single, '<strong>' . date_i18n( __( 'M j, Y @ G:i', 'recursive-shortcode' ), strtotime( $post->post_date ) ) . '</strong>', '<a target="_blank" href="' . esc_url( get_permalink( $post_ID ) ) . '">', $this->single, '</a>' ),
			10 => sprintf( __( '%1$s draft updated. %2$sPreview %3$s%4$s.', 'recursive-shortcode' ), $this->single, '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', $this->single, '</a>' ),
		);
		//phpcs:enable

		return $messages;
	}

	/**
	 * Set up bulk admin messages for post type
	 *
	 * @param  array $bulk_messages Default bulk messages.
	 * @param  array $bulk_counts   Counts of selected posts in each status.
	 * @return array                Modified messages.
	 */
	public function bulk_updated_messages( $bulk_messages = array(), $bulk_counts = array() ) {

		//phpcs:disable
		$bulk_messages[ $this->post_type ] = array(
			'updated'   => sprintf( _n( '%1$s %2$s updated.', '%1$s %3$s updated.', $bulk_counts['updated'], 'recursive-shortcode' ), $bulk_counts['updated'], $this->single, $this->plural ),
			'locked'    => sprintf( _n( '%1$s %2$s not updated, somebody is editing it.', '%1$s %3$s not updated, somebody is editing them.', $bulk_counts['locked'], 'recursive-shortcode' ), $bulk_counts['locked'], $this->single, $this->plural ),
			'deleted'   => sprintf( _n( '%1$s %2$s permanently deleted.', '%1$s %3$s permanently deleted.', $bulk_counts['deleted'], 'recursive-shortcode' ), $bulk_counts['deleted'], $this->single, $this->plural ),
			'trashed'   => sprintf( _n( '%1$s %2$s moved to the Trash.', '%1$s %3$s moved to the Trash.', $bulk_counts['trashed'], 'recursive-shortcode' ), $bulk_counts['trashed'], $this->single, $this->plural ),
			'untrashed' => sprintf( _n( '%1$s %2$s restored from the Trash.', '%1$s %3$s restored from the Trash.', $bulk_counts['untrashed'], 'recursive-shortcode' ), $bulk_counts['untrashed'], $this->single, $this->plural ),
		);
		//phpcs:enable

		return $bulk_messages;
	}

}
