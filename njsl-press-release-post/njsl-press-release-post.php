<?php
/*
Plugin Name: NJSL Press Release posts
Plugin URI: http://www.njstatelib.org
Description: Press release management
Version: 1.0
Author: David Dean for NJSL
Author URI: http://www.njstatelib.org
*/

class NJSL_Press_Release_CPT {

	var $cpt_slug    = 'press_release';
	var $cpt_options = array(
		'labels' => array(
			'name'          => 'Press Releases',
			'singular_name' => 'Press Release',
			'edit_item'     => 'Edit Press Release',
			'new_item'      => 'New Press Release',
			'search_items'  => 'Search Press Releases',
		),
		'public'      => true,
		'has_archive' => true,
		'rewrite'     => array(
			'slug'       => 'media/press-releases',
			'with_front' => false
		),
		'capability_type' => array( 'press_release', 'press_releases' ),
		'map_meta_cap'    => true,
		'taxonomies'      => array( 'news_category' ),
		'menu_icon'       => 'dashicons-format-aside',
		'menu_position'   => 7,
		'supports'  => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
	);
	
	var $taxonomy = 'news_category';
	
	public function __construct() {
		
		/** Translate CPT labels */
		foreach( $this->cpt_options['labels'] as $key => $label ) {
			$this->cpt_options['labels'][ $key ] = __( $label, 'njsl-pressreleases' );
		}

		/** Allow the rewrite path to be filtered */
		$this->cpt_options['rewrite']['slug'] = apply_filters( $this->cpt_slug . '_rewrite_slug', $this->cpt_options['rewrite']['slug'] );

		$tax_class = substr( get_class(), 0, -4 ) . '_Taxonomy';
		
		if( class_exists( $tax_class ) ) {
			$this->taxonomy = new $tax_class;
			$this->taxonomy->post_type = $this->cpt_slug;
		} else if( ! is_null( $this->taxonomy ) ) {
			$this->taxonomy = (object)array(
				'tax_slug' => $this->taxonomy
			);
		} else {
			$this->taxonomy = 'category';
		}
		
		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'manage_' . $this->cpt_slug . '_posts_columns', array( $this, 'define_columns' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'meta_boxes' ) );
		add_action( 'save_post', array( $this, 'handle_save_post') );
		
//		add_filter( 'breadcrumb_trail_items', array( $this, 'filter_breadcrumb_items' ), 10, 2 );
		
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		
	}
	
	/**
	 * Register the custom post type
	 */
	public function register() {
		
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
			'cb'         => '<input type="checkbox" />',
			'title'      => __( 'Title' ),
			'has_header' => __( 'Has Header', 'njsl-pressreleases' ),
			'location'   => __( 'Location', 'njsl-pressreleases' ),
			'category'   => __( 'Categories' ),
			'date'       => __( 'Date' ),
		);
		
		return $cols;
	}
	
	/**
	 * Define which columns are sortable
	 */
	public function define_sortables() {}
	
	/**
	 * Define the content of each custom column
	 */
	public function column_content( $col, $post_ID ) {
		
		switch( $col ) {
			
			case 'has_header':
				echo ( get_post_meta( $post_ID, 'press_release_header', true ) ? 'Yes' : 'No' );
				break;
			case 'location':
				echo get_post_meta( $post_ID, 'press_release_location', true );
				break;
		}
		
	}
	
	/**
	 * Define metaboxes for this post type
	 */
	public function meta_boxes() {
		
		add_meta_box(
			sprintf( '%s_details', $this->cpt_slug ),
			__( 'Press Release Details', 'njsl-pressreleases' ),
			array( $this, 'metabox_press_release_details' ),
			$this->cpt_slug
		);
		
	}

	public function metabox_press_release_details( $post ) {
		
		// Add an nonce field so we can check for it later.
		wp_nonce_field( $this->cpt_slug . '_details', $this->cpt_slug . '_details_nonce' );
		
		$values = array();

		$values['Header']   = get_post_meta( $post->ID, 'press_release_header', true );
		$values['Location'] = get_post_meta( $post->ID, 'press_release_location', true );

		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<label for="press_release_locaion">
						<?php _e( 'Location', 'njsl-pressreleases' ); ?>
					</label>
				</th>
				<td>
					<input 
						type="text" 
						id="press_release_location" 
						name="press_release_location" 
						value="<?= esc_attr( $values['Location'] ) ?>" 
						size="80" 
					>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="press_release_header">
						<?php _e( 'Header', 'njsl-pressreleases' ); ?>
					</label>
				</th>
				<td>
					<textarea 
						id="press_release_header" 
						name="press_release_header"
						cols="80"
						rows="8"
					><?= esc_attr( $values['Header'] ) ?></textarea>
				</td>
			</tr>
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
		
		update_post_meta( $post_ID, 'press_release_header', sanitize_text_field( $_POST['press_release_header'] ) );
		update_post_meta( $post_ID, 'press_release_location', sanitize_text_field( $_POST['press_release_location'] ) );
		
	}
	
	/**
	 * Fix breadcrumbs for press releases to account for multilayer slug
	 */
	public function filter_breadcrumb_items( $items, $args ) {
		
		if( $this->cpt_slug == get_post_type() ) {
			
			$slug = explode( '/', $this->cpt_options['rewrite']['slug'] );
			
			if( 1 < count( $slug ) ) {
			
				$home = array_shift( $items );
				
				if( $page = get_page_by_path( $slug[0] ) ) {
					// If this is a real page, use the title
					array_unshift(
						$items,
						'<a href="' . get_permalink( $page->ID )  . '">' . get_the_title( $page->ID ) . '</a>'
					);
					
				} else {

					$item = ucwords( strtr( $slug[0], '_-', '  ' ) );
					
					// Otherwise, fake it
					array_unshift(
						$items,
						'<a href="' . home_url( $slug[0]) . '">' . $item . '</a>'
					);
				}
				
				array_unshift( $items, $home );
				
			}
			
			
		}
		
		return $items;
		
	}
	
	/**
	 * Run actions on plugin activation - create capabilities
	 */
	public function activate() {
		
		$role = get_role( 'administrator' );
		if ( $role ) {
			$role->add_cap( 'edit_press_release' );
			$role->add_cap( 'read_press_release' );
			$role->add_cap( 'delete_press_release' );
			$role->add_cap( 'delete_press_releases');
			$role->add_cap( 'edit_press_releases' );
			$role->add_cap( 'edit_others_press_releases' );
			$role->add_cap( 'delete_others_press_releases' );
			$role->add_cap( 'publish_press_releases' );
			$role->add_cap( 'edit_published_press_releases' );
			$role->add_cap( 'delete_published_press_releases' );
			$role->add_cap( 'delete_private_press_releases' );
			$role->add_cap( 'edit_private_press_releases' );
			$role->add_cap( 'read_private_press_releases' );
		}

		$editor = get_role( 'editor' );
		if ( $editor ) {
			$editor->add_cap( 'edit_press_release' );
			$editor->add_cap( 'read_press_release' );
			$editor->add_cap( 'delete_press_release' );
			$editor->add_cap( 'delete_press_releases');
			$editor->add_cap( 'edit_press_releases' );
			$editor->add_cap( 'edit_others_press_releases' );
			$editor->add_cap( 'delete_others_press_releases' );
			$editor->add_cap( 'publish_press_releases' );
			$editor->add_cap( 'edit_published_press_releases' );
			$editor->add_cap( 'delete_published_press_releases' );
			$editor->add_cap( 'delete_private_press_releases' );
			$editor->add_cap( 'edit_private_press_releases' );
			$editor->add_cap( 'read_private_press_releases' );
		}
		
		$author = get_role( 'author' );
		if ( $author ) {
			$author->add_cap( 'edit_press_release' );
			$author->add_cap( 'read_press_release' );
			$author->add_cap( 'delete_press_release' );
			$author->add_cap( 'delete_press_releases' );
			$author->add_cap( 'edit_press_releases' );
			$author->add_cap( 'publish_press_releases' );
			$author->add_cap( 'edit_published_press_releases' );
			$author->add_cap( 'delete_published_press_releases' );
		}
		
		$contributor = get_role( 'contributor' );
		if ( $contributor ) {
			$contributor->add_cap( 'edit_press_release' );
			$contributor->add_cap( 'read_press_release' );
			$contributor->add_cap( 'delete_press_release' );
			$contributor->add_cap( 'delete_press_releases' );
			$contributor->add_cap( 'edit_press_releases' );
		}
		
		$subscriber = get_role( 'subscriber' );
		if ( $subscriber ) {
			$subscriber->add_cap( 'read_press_release' );
		}
		
	}
}

$njsl_press_releases = new NJSL_Press_Release_CPT;

?>