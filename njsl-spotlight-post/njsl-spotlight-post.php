<?php
/*
Plugin Name: NJSL Spotlight posts
Plugin URI: http://www.njstatelib.org
Description: Spotlight item management
Version: 1.1
Author: David Dean for NJSL
Author URI: http://www.njstatelib.org
*/

class NJSL_Spotlight_CPT {

	var $cpt_slug    = 'spotlight';
	var $cpt_options = array(
		'labels' => array(
			'name'          => 'Spotlight Items',
			'singular_name' => 'Spotlight Item',
			'edit_item'     => 'Edit Spotlight Item',
			'new_item'      => 'New Item',
			'search_items'  => 'Search Spotlight',
		),
		'public'      => true,
		'has_archive' => true,
		'rewrite'     => array(
			'slug'       => 'spotlight',
			'with_front' => false
		),
		'capability_type' => array( 'spotlight', 'spotlights' ),
		'map_meta_cap'    => true,
		'menu_icon'     => 'dashicons-location',
		'menu_position' => 21,
		'supports'  => array( 'title', 'excerpt' ),
	);
	
	var $taxonomy = null;
	
	public function __construct() {
		
		/** Translate CPT labels */
		foreach( $this->cpt_options['labels'] as $key => $label ) {
			$this->cpt_options['labels'][ $key ] = __( $label, 'njsl-spotlight' );
		}

		/** Allow the rewrite path to be filtered */
		$this->cpt_options['rewrite']['slug'] = apply_filters( $this->cpt_slug . '_rewrite_slug', $this->cpt_options['rewrite']['slug'] );

		$tax_class = substr( get_class(), 0, -4 ) . '_Taxonomy';
		
		if( class_exists( $tax_class ) ) {
			$this->taxonomy = new $tax_class;
			$this->taxonomy->post_type = $this->cpt_slug;
		} else {
			$this->taxonomy = 'category';
		}
		
		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'manage_' . $this->cpt_slug . '_posts_columns', array( $this, 'define_columns' ) );
		add_filter( 'manage_edit-' . $this->cpt_slug . '_sortable_columns', array( $this, 'define_sortables' ) );
		add_action( 'manage_' . $this->cpt_slug . '_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'meta_boxes' ) );
		add_action( 'save_post', array( $this, 'handle_save_post') );
		
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
	}
	
	/**
	 * Register the custom post type
	 */
	public function register() {
		
		add_rewrite_rule(
			sprintf( '%s/category/([^/]+)/?', $this->cpt_slug ),
			sprintf( 'index.php?post_type=%s&news_category=$matches[1]', $this->cpt_slug ),
			'top'
		);
		
		return register_post_type(
			$this->cpt_slug,
			$this->cpt_options
		);
		
	}
	
	/**
	 * Define the columns displayed on the CPT admin screen
	 */
	public function define_columns( $cols ) {
		
		$cols = array(
			'cb'       => '<input type="checkbox" />',
			'title'    => __( 'Title' ),
			'author'   => __( 'Author' ),
			'sdate'    => __( 'Start Date', 'njsl-spotlight' ),
			'edate'    => __( 'End Date', 'njsl-spotlight' ),
			'date'     => __( 'Date Published', 'njsl-spotlight' ),
		);
		
		return $cols;
	}
	
	/**
	 * Define which columns are sortable
	 */
	public function define_sortables( $columns ) {
		
		$columns['sdate'] = 'sdate';
		$columns['edate'] = 'edate';
		
		return $columns;
	}
	
	/**
	 * Define the content of each custom column
	 */
	public function column_content( $col, $post_ID ) {
		
		switch( $col ) {
			
			case 'sdate':
				
				$sdate = get_post_meta( $post_ID, 'spotlight_start_date', true ); 
				echo ( ! empty( $sdate ) ? date('Y-m-d', $sdate ) : 'Not Set' );
				
				break;
			case 'edate':
				
				$edate = get_post_meta( $post_ID, 'spotlight_end_date', true ); 
				echo ( ! empty( $edate ) ? date('Y-m-d', $edate ) : 'Not Set' );
				
				break;
		}
		
	}
	
	/**
	 * Define metaboxes for this post type
	 */
	public function meta_boxes() {
		
		add_meta_box(
			sprintf( '%s_details', $this->cpt_slug ),
			__( 'Spotlight Details', 'njsl-spotlight' ),
			array( $this, 'metabox_spotlight_details' ),
			$this->cpt_slug
		);
		
	}
	
	public function metabox_spotlight_details( $post ) {
		
		// Add an nonce field so we can check for it later.
		wp_nonce_field( $this->cpt_slug . '_details', $this->cpt_slug . '_details_nonce' );
		
		$values = array();

		$values['StartDate'] = get_post_meta( $post->ID, 'spotlight_start_date', true );
		$values['EndDate']   = get_post_meta( $post->ID, 'spotlight_end_date', true );
		$values['URL']       = get_post_meta( $post->ID, 'spotlight_link_url', true );
		
		if( ! empty( $values['StartDate'] ) )
			$values['StartDate'] = date('Y-m-d', $values['StartDate'] );
		if( ! empty( $values['EndDate'] ) )
			$values['EndDate'] = date('Y-m-d', $values['EndDate'] );
		
		
		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<label for="spotlight_start_date">
						<?php _e( 'Start Date', 'njsl-spotlight' ); ?>
					</label>
				</th>
				<td>
					<input 
						type="date" 
						id="spotlight_start_date" 
						name="spotlight_start_date" 
						value="<?= esc_attr( $values['StartDate'] ) ?>" 
						size="80" 
					><br>
					<small><?php printf( __( 'Date item should start appearing &mdash; Enter date in %s format for best results.', 'njsl-spotlight' ), '<code>YYYY-MM-DD</code>' ) ?></small>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="spotlight_end_date">
						<?php _e( 'End Date', 'njsl-spotlight' ); ?>
					</label>
				</th>
				<td>
					<input 
						type="date" 
						id="spotlight_end_date" 
						name="spotlight_end_date" 
						value="<?= esc_attr( $values['EndDate'] ) ?>" 
						size="80" 
					><br>
					<small><?php printf( __( 'Date item should stop appearing &mdash; Enter date in %s format for best results.', 'njsl-spotlight' ), '<code>YYYY-MM-DD</code>' ) ?></small>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="spotlight_link_url">
						<?php _e( 'Link URL', 'njsl-spotlight' ); ?>
					</label>
				</th>
				<td>
					<input 
						type="url" 
						id="spotlight_link_url" 
						name="spotlight_link_url" 
						value="<?= esc_attr( $values['URL'] ) ?>" 
						size="80" 
					>
				</td>
			</tr>
		</table>
		
		<?php	
		
	}
	
	/**
	 * Define handlers for any custom metaboxes or other save processing
	 */
	public function handle_save_post( $post_ID ) {
		
		// Verify that our nonce is set.
		if ( ! isset( $_POST[ $this->cpt_slug . '_details_nonce'] ) )
			return $post_ID;
		
		$nonce = $_POST[ $this->cpt_slug . '_details_nonce'];
		
		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, $this->cpt_slug . '_details' ) )
			return $post_ID;
		
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_ID;
		
		
		$start_date = $_POST['spotlight_start_date']; 
		if( ! empty( $start_date ) ) {
			$start_date = strtotime( $start_date );
			if( 0 == $start_date )
				$start_date = '';
		}

		$end_date = $_POST['spotlight_end_date']; 
		if( ! empty( $end_date ) ) {
			$end_date = strtotime( $end_date );
			if( 0 == $end_date )
				$end_date = '';
		}
		
		$link_url = $_POST['spotlight_link_url'];
		if( ! empty( $link_url ) ) {
			$link_url = esc_url_raw( trim( $link_url ) );
		}
		
		update_post_meta( $post_ID, 'spotlight_start_date', $start_date );
		update_post_meta( $post_ID, 'spotlight_end_date', $end_date );
		update_post_meta( $post_ID, 'spotlight_link_url', $link_url );
		
	}
	
	/**
	 * Run actions on plugin activation - create capabilities
	 */
	public function activate() {
		
		$role = get_role( 'administrator' );
		if ( $role ) {
			$role->add_cap( 'edit_spotlight' );
			$role->add_cap( 'read_spotlight' );
			$role->add_cap( 'delete_spotlight' );
			$role->add_cap( 'delete_spotlights');
			$role->add_cap( 'edit_spotlights' );
			$role->add_cap( 'edit_others_spotlights' );
			$role->add_cap( 'delete_others_spotlights' );
			$role->add_cap( 'publish_spotlights' );
			$role->add_cap( 'edit_published_spotlights' );
			$role->add_cap( 'delete_published_spotlights' );
			$role->add_cap( 'delete_private_spotlights' );
			$role->add_cap( 'edit_private_spotlights' );
			$role->add_cap( 'read_private_spotlights' );
		}

		$editor = get_role( 'editor' );
		if ( $editor ) {
			$editor->add_cap( 'edit_spotlight' );
			$editor->add_cap( 'read_spotlight' );
			$editor->add_cap( 'delete_spotlight' );
			$editor->add_cap( 'delete_spotlights');
			$editor->add_cap( 'edit_spotlights' );
			$editor->add_cap( 'edit_others_spotlights' );
			$editor->add_cap( 'delete_others_spotlights' );
			$editor->add_cap( 'publish_spotlights' );
			$editor->add_cap( 'edit_published_spotlights' );
			$editor->add_cap( 'delete_published_spotlights' );
			$editor->add_cap( 'delete_private_spotlights' );
			$editor->add_cap( 'edit_private_spotlights' );
			$editor->add_cap( 'read_private_spotlights' );
		}
		
		$author = get_role( 'author' );
		if ( $author ) {
			$author->add_cap( 'edit_spotlight' );
			$author->add_cap( 'read_spotlight' );
			$author->add_cap( 'delete_spotlight' );
			$author->add_cap( 'delete_spotlights' );
			$author->add_cap( 'edit_spotlights' );
			$author->add_cap( 'publish_spotlights' );
			$author->add_cap( 'edit_published_spotlights' );
			$author->add_cap( 'delete_published_spotlights' );
		}
		
		$contributor = get_role( 'contributor' );
		if ( $contributor ) {
			$contributor->add_cap( 'edit_spotlight' );
			$contributor->add_cap( 'read_spotlight' );
			$contributor->add_cap( 'delete_spotlight' );
			$contributor->add_cap( 'delete_spotlights' );
			$contributor->add_cap( 'edit_spotlights' );
		}
		
		$subscriber = get_role( 'subscriber' );
		if ( $subscriber ) {
			$subscriber->add_cap( 'read_spotlight' );
		}
		
	}
	
}

$njsl_spotlight = new NJSL_Spotlight_CPT;

?>