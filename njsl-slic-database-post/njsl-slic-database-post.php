<?php
/*
Plugin Name: NJSL Electronic Resource posts
Plugin URI: http://www.njstatelib.org
Description: Electronic resource database management
Version: 1.1
Author: David Dean for NJSL
Author URI: http://www.njstatelib.org
*/

class NJSL_Database_CPT {

	var $cpt_slug    = 'njsl_database';
	var $cpt_options = array(
		'labels' => array(
			'name'          => 'Electronic Resources',
			'singular_name' => 'Electronic Resource'
		),
		'public'      => true,
		'has_archive' => true,
		'rewrite'     => array(
			'slug'       => 'electronic_resources/databases',
			'with_front' => false
		),
		'capability_type' => array( 'database', 'databases' ),
		'map_meta_cap'    => true,
		'menu_icon'     => 'dashicons-welcome-learn-more',
		'menu_position' => 9,
	);
	
	var $taxonomy = null;
	
	public function __construct() {
		

		/** Translate CPT labels */
		foreach( $this->cpt_options['labels'] as $key => $label ) {
			$this->cpt_options['labels'][ $key ] = __( $label, 'njsl-databases' );
		}

		/** Allow the rewrite path to be filtered */
		$this->cpt_options['rewrite']['slug'] = apply_filters( $this->cpt_slug . '_rewrite_slug', $this->cpt_options['rewrite']['slug'] );

		$tax_class = substr( get_class(), 0, -4 ) . '_Taxonomy';
		
		if( class_exists( $tax_class ) ) {
			$this->taxonomy = new $tax_class;
			$this->taxonomy->post_type = $this->cpt_slug;
		}
		
		add_action( 'init',             array( $this, 'register' ) );
		add_filter( 'manage_' . $this->cpt_slug . '_posts_columns', array( $this, 'define_columns' ) );
		add_action( 'manage_posts_custom_column',                   array( $this, 'column_content' ), 10, 2 );
		add_action( 'add_meta_boxes',   array( $this, 'meta_boxes' ) );
		add_action( 'save_post',        array( $this, 'handle_save_post') );
		add_action( 'pre_get_posts',    array( $this, 'sort_databases' ) );
		add_filter( 'posts_where',      array( $this, 'filter_posts_query'), 10, 2 );
		
		add_filter( 'archive_template', array( $this, 'maybe_override_archive_template' ) );
		add_filter( 'single_template',  array( $this, 'maybe_override_single_template' ) );
		
		add_filter( 'breadcrumb_trail_items', array( $this, 'filter_breadcrumb_items' ), 10, 2 );
		
		do_action( $this->cpt_slug . '_loaded' );
		
		register_activation_hook( __FILE__, array( $this, 'flush_rewrite_rules' ) );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		
	}
	
	/**
	 * Register the custom post type, including custom rewrite rules and tags
	 */
	public function register() {
		
		/** Add subject search tag */
		add_rewrite_tag(
			'%' . $this->taxonomy->tax_slug .  '%',
			'([^/]+)'
		);

		/** Add first letter search tag */
		add_rewrite_tag(
			'%name__like%',
			'([^/]+)'
		);
		
		/** Add subject rewrite rules */
		add_rewrite_rule(
			$this->cpt_options['rewrite']['slug'] . '/by_subject/([^/]+)/page/([\d]+)/?',
			sprintf(
				'index.php?post_type=%s&%s=$matches[1]&paged=$matches[2]',
				$this->cpt_slug,
				$this->taxonomy->tax_slug
			),
			'top'
		);
		
		add_rewrite_rule(
			$this->cpt_options['rewrite']['slug'] . '/by_subject/([^/]+)/?',
			sprintf(
				'index.php?post_type=%s&%s=$matches[1]',
				$this->cpt_slug,
				$this->taxonomy->tax_slug
			),
			'top'
		);

		/** Add first letter rewrite rules */
		add_rewrite_rule(
			$this->cpt_options['rewrite']['slug'] . '/by_title/([^/+])/page/([\d]+)/?',
			sprintf(
				'index.php?post_type=%s&name__like=$matches[1]&paged=$matches[2]',
				$this->cpt_slug
			),
			'top'
		);
		
		add_rewrite_rule(
			$this->cpt_options['rewrite']['slug'] . '/by_title/([^/+])/?',
			sprintf(
				'index.php?post_type=%s&name__like=$matches[1]',
				$this->cpt_slug
			),
			'top'
		);
		
		return register_post_type(
			$this->cpt_slug,
			$this->cpt_options
		);
		
		
	}
	
	/**
	 * Flush rewrite rules on activation
	 */
	public function flush_rewrite_rules() {
		flush_rewrite_rules();
	}
	
	/**
	 * Filter queries that contain the 'name__like' parameter
	 */
	public function filter_posts_query( $where, &$wp_query ) {
		
		global $wpdb;
		
		if( $name_stem = $wp_query->get( 'name__like' ) ) {
			$where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'' . esc_sql( $wpdb->esc_like( $name_stem ) ) . '%\'';
		}
		
		return $where;
	}
	
	/**
	 * Fix breadcrumbs for database listings by title and subject
	 * Works with Breadcrumb Trail plugin -- https://wordpress.org/plugins/breadcrumb-trail/
	 */
	public function filter_breadcrumb_items( $items, $args ) {
		
		if( $this->cpt_slug == get_post_type() ) {
			
			global $wp_query;
			
			if( $name_stem = $wp_query->get( 'name__like' ) ) {

				$database_item = array_pop( $items );
				$database_item = '<a href="' . esc_url( home_url( $this->cpt_options['rewrite']['slug'] ) ) . '">' . ucfirst( $database_item ) . '</a>';
				$items[] = sprintf( __('By Title - %s', 'njsl-databases' ), $database_item );

				$items[] = strtoupper( $name_stem );
			} else if( $subject = $wp_query->get( $this->taxonomy->tax_slug ) ) {

				$database_item = array_pop( $items );
				$database_item = '<a href="' . esc_url( home_url( $this->cpt_options['rewrite']['slug'] ) ) . '">' . ucfirst( $database_item ) . '</a>';
				$items[] = $database_item;

				$items[] = sprintf( __( 'By Subject - %s', 'njsl-databases' ), $subject );
			}
			
		}
		
		return $items;
		
	}
	
	/**
	 * Define the columns displayed on the CPT admin screen
	 */
	public function define_columns( $cols ) {
		
		$cols = array(
			'cb'       => '<input type="checkbox" />',
			'title'    => __( 'Title',    'njsl-databases' ),
			'url'      => __( 'URL',      'njsl-databases' ),
			'access'   => __( 'Access',   'njsl-databases' ),
			'category' => __( 'Subjects', 'njsl-databases' ),
			'vendor'   => __( 'Vendor',   'njsl-databases' ),
			'notes'    => __( 'Notes',    'njsl-databases' )
		);
		
		return $cols;
	}
	
	/**
	 * Define which columns are sortable -- none currently
	 */
	public function define_sortables() {}
	
	/**
	 * Define the content of each custom column
	 */
	public function column_content( $col, $post_ID ) {
		
		switch( $col ) {
			
			case 'url':
				echo get_post_meta( $post_ID, 'database_url', true );
				break;
			case 'access':
				printf( __('Remote: %s', 'njsl-databases' ), get_post_meta( $post_ID, 'database_remote_access', true ) );
				echo '<br>';
				printf( __('On-site: %s', 'njsl-databases' ), get_post_meta( $post_ID, 'database_onsite_access', true ) );
				break;
			case 'category':
				
				$terms = wp_get_object_terms( $post_ID, $this->taxonomy->tax_slug );
				
				foreach( $terms as $term ) {
					echo '<a href=" ' . esc_url( add_query_arg( array( $this->taxonomy->tax_slug => $term->slug ) ) ) . '">' . $term->name . '</a>' . "<br>\n";
				}
				
				break;
			case 'vendor':
				echo get_post_meta( $post_ID, 'database_vendor', true );
				break;
			case 'notes':
				echo get_post_meta( $post_ID, 'database_notes', true );
				break;
		}
		
	}
	
	/**
	 * Define metaboxes for this post type
	 */
	public function meta_boxes() {
		
		add_meta_box(
			sprintf( '%s_details', $this->cpt_slug ),
			__( 'Electronic Resource Details', 'njsl-databases' ),
			array( $this, 'metabox_database_details' ),
			$this->cpt_slug
		);
		
	}

	public function metabox_database_details( $post ) {
		
		// Add an nonce field so we can check for it later.
		wp_nonce_field( $this->cpt_slug . '_details', $this->cpt_slug . '_details_nonce' );
		
		$values = array();

		$values['URL']    = get_post_meta( $post->ID, 'database_url', true );
		$values['Remote'] = get_post_meta( $post->ID, 'database_remote_access', true );
		$values['Onsite'] = get_post_meta( $post->ID, 'database_onsite_access', true );
		$values['Vendor'] = get_post_meta( $post->ID, 'database_vendor', true );
		$values['Notes']  = get_post_meta( $post->ID, 'database_notes', true );

		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<label for="database_url">
						<?php _e( 'Database URL', 'njsl-databases' ); ?>
					</label>
				</th>
				<td>
					<input 
						type="url" 
						id="database_url" 
						name="database_url" 
						value="<?= esc_attr( $values['URL'] ) ?>" 
						size="80" 
					>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="database_remote_access">
						<?php _e( 'Remote Access', 'njsl-databases' ); ?>
					</label>
				</th>
				<td>
					<input 
						type="text" 
						id="database_remote_access" 
						name="database_remote_access" 
						value="<?= esc_attr( $values['Remote'] ) ?>" 
						size="40" 
					>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="database_onsite_access">
						<?php _e( 'On-site Access', 'njsl-databases' ); ?>
					</label>
				</th>
				<td>
					<input 
						type="text" 
						id="database_onsite_access" 
						name="database_onsite_access" 
						value="<?= esc_attr( $values['Onsite'] ) ?>" 
						size="40" 
					>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="database_vendor">
						<?php _e( 'Database Vendor', 'njsl-databases' ); ?>
					</label>
				</th>
				<td>
					<input 
						type="text" 
						id="database_vendor" 
						name="database_vendor" 
						value="<?= esc_attr( $values['Vendor'] ) ?>" 
						size="40" 
					>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="database_notes">
						<?php _e( 'Database Notes', 'njsl-databases' ); ?>
					</label>
				</th>
				<td>
					<textarea 
						id="database_notes" 
						name="database_notes" 
						cols="40"
					><?= esc_attr( $values['Notes'] ) ?></textarea>
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
		
		update_post_meta( $post_ID, 'database_url',           esc_url_raw( $_POST['database_url'] ) );
		update_post_meta( $post_ID, 'database_remote_access', sanitize_text_field( $_POST['database_remote_access'] ) );
		update_post_meta( $post_ID, 'database_onsite_access', sanitize_text_field( $_POST['database_onsite_access'] ) );
		update_post_meta( $post_ID, 'database_vendor',        sanitize_text_field( $_POST['database_vendor'] ) );
		update_post_meta( $post_ID, 'database_notes',         wp_kses_post( $_POST['database_notes'] ) );
		
	}
	
	/**
	 * Ensure database results are sorted by title
	 */
	public function sort_databases( &$query ) {
		
		if(
			 ! is_admin() && 
			 $query->is_archive && 
			 isset( $query->query['post_type'] ) &&
			$this->cpt_slug == $query->query['post_type'] 
		) {
			
			$query->set( 'order',         'asc' );
			$query->set( 'orderby',       'title' );
			$query->set( 'posts_per_page', 25 );
			
		}
	}
	
	/**
	 * Use the plugin's archive file unless one has been created in the theme directory
	 */
	public function maybe_override_archive_template( $template ) {
		
		global $wp_query;
		
		// Only modify queries for electronic resources
		if( empty( $wp_query->query['post_type'] ) || $wp_query->query['post_type'] != $this->cpt_slug )
			return $template;
		
		// If the template name contains the post type, the archive was implemented in theme
		if( false !== stripos( $template, $this->cpt_slug ) )
			return $template;
		
		if( 
			file_exists( plugin_dir_path( __FILE__ ) . 'templates/archive.php' ) &&
			apply_filters( $this->cpt_slug . '_override_archive_template', true )
		)
			return plugin_dir_path( __FILE__ ) . 'templates/archive.php';
		
		return $template;
		
	}
	
	/**
	 * Use the plugin's single page file unless one has been created in the theme directory
	 */
	public function maybe_override_single_template( $template ) {
		
		global $wp_query;
		
		// Only modify templates for electronic resources pages
		if( empty( $wp_query->query['post_type'] ) || $wp_query->query['post_type'] != $this->cpt_slug )
			return $template;
		
		// If the template name contains the post type, the single page was implemented in theme
		if( false !== stripos( $template, $this->cpt_slug ) )
			return $template;
		
		if( 
			file_exists( plugin_dir_path( __FILE__ ) . 'templates/single.php' ) &&
			apply_filters( $this->cpt_slug . '_override_single_template', true )
		)
			return plugin_dir_path( __FILE__ ) . 'templates/single.php';
		
		return $template;
		
	}
	
	/**
	 * Run actions on plugin activation - create capabilities
	 */
	public function activate() {
		
		$role = get_role( 'administrator' );
		if ( $role ) {
			$role->add_cap( 'edit_database' );
			$role->add_cap( 'read_database' );
			$role->add_cap( 'delete_database' );
			$role->add_cap( 'delete_databases');
			$role->add_cap( 'edit_databases' );
			$role->add_cap( 'edit_others_databases' );
			$role->add_cap( 'delete_others_databases' );
			$role->add_cap( 'publish_databases' );
			$role->add_cap( 'edit_published_databases' );
			$role->add_cap( 'delete_published_databases' );
			$role->add_cap( 'delete_private_databases' );
			$role->add_cap( 'edit_private_databases' );
			$role->add_cap( 'read_private_databases' );
		}

		$editor = get_role( 'editor' );
		if ( $editor ) {
			$editor->add_cap( 'edit_database' );
			$editor->add_cap( 'read_database' );
			$editor->add_cap( 'delete_database' );
			$editor->add_cap( 'delete_databases');
			$editor->add_cap( 'edit_databases' );
			$editor->add_cap( 'edit_others_databases' );
			$editor->add_cap( 'delete_others_databases' );
			$editor->add_cap( 'publish_databases' );
			$editor->add_cap( 'edit_published_databases' );
			$editor->add_cap( 'delete_published_databases' );
			$editor->add_cap( 'delete_private_databases' );
			$editor->add_cap( 'edit_private_databases' );
			$editor->add_cap( 'read_private_databases' );
		}
		
		$author = get_role( 'author' );
		if ( $author ) {
			$author->add_cap( 'edit_database' );
			$author->add_cap( 'read_database' );
			$author->add_cap( 'delete_database' );
			$author->add_cap( 'delete_databases' );
			$author->add_cap( 'edit_databases' );
			$author->add_cap( 'publish_databases' );
			$author->add_cap( 'edit_published_databases' );
			$author->add_cap( 'delete_published_databases' );
		}
		
		$contributor = get_role( 'contributor' );
		if ( $contributor ) {
			$contributor->add_cap( 'edit_database' );
			$contributor->add_cap( 'read_database' );
			$contributor->add_cap( 'delete_database' );
			$contributor->add_cap( 'delete_databases' );
			$contributor->add_cap( 'edit_databases' );
		}
		
		$subscriber = get_role( 'subscriber' );
		if ( $subscriber ) {
			$subscriber->add_cap( 'read_database' );
		}
		
	}
	
}

class NJSL_Database_Taxonomy {
	
	var $tax_slug    = 'database_category';
	var $tax_options = array(
		'labels'        => array(
			'name'              => 'Electronic Resource Subjects',
			'singular_name'     => 'Electronic Resource Subject',
			'search_items'      => 'Search Electronic Resource Subjects',
			'all_items'         => 'All Electronic Resource Subjects',
			'parent_item'       => 'Parent Electronic Resource Subject',
			'parent_item_colon' => 'Parent Electronic Resource Subject:',
			'edit_item'         => 'Edit Electronic Resource Subject', 
			'update_item'       => 'Update Electronic Resource Subject',
			'add_new_item'      => 'Add New Electronic Resource Subject',
			'new_item_name'     => 'New Electronic Resource Subject',
			'menu_name'         => 'Subjects',
		),
		'hierarchical' => true
	);
	var $post_type   = null;
	
	public function __construct() {
		
		// Translate taxonomoy labels
		foreach( $this->tax_options['labels'] as $key => $label ) {
			$this->tax_options['labels'][ $key ] = __( $label, 'njsl-databases' );
		}
		
		add_action( 'init', array( $this, 'register' ), 0 );
		
	}
	
	public function register() {
		
		return register_taxonomy(
			$this->tax_slug,
			$this->post_type,
			$this->tax_options
		);
		
	}
	
}

$njsl_databases = new NJSL_Database_CPT;

?>