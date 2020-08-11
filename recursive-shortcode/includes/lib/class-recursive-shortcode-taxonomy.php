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
 * Taxonomy functions file.
 *
 * @package WordPress Plugin Recursive Shortcode/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomy functions class.
 */
class Recursive_Shortcode_Taxonomy {

	/**
	 * The name for the taxonomy.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $taxonomy;

	/**
	 * The plural name for the taxonomy terms.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $plural;

	/**
	 * The singular name for the taxonomy terms.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $single;

	/**
	 * The array of post types to which this taxonomy applies.
	 *
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $post_types;

	/**
	 * The array of taxonomy arguments
	 *
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $taxonomy_args;

	/**
	 * Taxonomy constructor.
	 *
	 * @param string $taxonomy Taxonomy variable nnam.
	 * @param string $plural Taxonomy plural name.
	 * @param string $single Taxonomy singular name.
	 * @param array  $post_types Affected post types.
	 * @param array  $tax_args Taxonomy additional args.
	 */
	public function __construct( $taxonomy = '', $plural = '', $single = '', $post_types = array(), $tax_args = array() ) {

		if ( ! $taxonomy || ! $plural || ! $single ) {
			return;
		}

		// Post type name and labels.
		$this->taxonomy = $taxonomy;
		$this->plural   = $plural;
		$this->single   = $single;
		if ( ! is_array( $post_types ) ) {
			$post_types = array( $post_types );
		}
		$this->post_types    = $post_types;
		$this->taxonomy_args = $tax_args;

		// Register taxonomy.
		add_action( 'init', array( $this, 'register_taxonomy' ) );
	}

	/**
	 * Register new taxonomy
	 *
	 * @return void
	 */
	public function register_taxonomy() {
		//phpcs:disable
		$labels = array(
			'name'                       => $this->plural,
			'singular_name'              => $this->single,
			'menu_name'                  => $this->plural,
			'all_items'                  => sprintf( __( 'All %s', 'recursive-shortcode' ), $this->plural ),
			'edit_item'                  => sprintf( __( 'Edit %s', 'recursive-shortcode' ), $this->single ),
			'view_item'                  => sprintf( __( 'View %s', 'recursive-shortcode' ), $this->single ),
			'update_item'                => sprintf( __( 'Update %s', 'recursive-shortcode' ), $this->single ),
			'add_new_item'               => sprintf( __( 'Add New %s', 'recursive-shortcode' ), $this->single ),
			'new_item_name'              => sprintf( __( 'New %s Name', 'recursive-shortcode' ), $this->single ),
			'parent_item'                => sprintf( __( 'Parent %s', 'recursive-shortcode' ), $this->single ),
			'parent_item_colon'          => sprintf( __( 'Parent %s:', 'recursive-shortcode' ), $this->single ),
			'search_items'               => sprintf( __( 'Search %s', 'recursive-shortcode' ), $this->plural ),
			'popular_items'              => sprintf( __( 'Popular %s', 'recursive-shortcode' ), $this->plural ),
			'separate_items_with_commas' => sprintf( __( 'Separate %s with commas', 'recursive-shortcode' ), $this->plural ),
			'add_or_remove_items'        => sprintf( __( 'Add or remove %s', 'recursive-shortcode' ), $this->plural ),
			'choose_from_most_used'      => sprintf( __( 'Choose from the most used %s', 'recursive-shortcode' ), $this->plural ),
			'not_found'                  => sprintf( __( 'No %s found', 'recursive-shortcode' ), $this->plural ),
		);
		//phpcs:enable
		$args = array(
			'label'                 => $this->plural,
			'labels'                => apply_filters( $this->taxonomy . '_labels', $labels ),
			'hierarchical'          => true,
			'public'                => true,
			'show_ui'               => true,
			'show_in_nav_menus'     => true,
			'show_tagcloud'         => true,
			'meta_box_cb'           => null,
			'show_admin_column'     => true,
			'show_in_quick_edit'    => true,
			'update_count_callback' => '',
			'show_in_rest'          => true,
			'rest_base'             => $this->taxonomy,
			'rest_controller_class' => 'WP_REST_Terms_Controller',
			'query_var'             => $this->taxonomy,
			'rewrite'               => true,
			'sort'                  => '',
		);

		$args = array_merge( $args, $this->taxonomy_args );

		register_taxonomy( $this->taxonomy, $this->post_types, apply_filters( $this->taxonomy . '_register_args', $args, $this->taxonomy, $this->post_types ) );
	}

}
