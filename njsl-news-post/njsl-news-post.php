<?php
/*
Plugin Name: NJSL News posts
Plugin URI: http://www.njstatelib.org
Description: News item management
Version: 1.0
Author: David Dean for NJSL
Author URI: http://www.njstatelib.org
*/

class NJSL_News_CPT {

	var $cpt_slug    = 'news';
	var $cpt_options = array(
		'labels' => array(
			'name'          => 'News Items',
			'singular_name' => 'News Item',
			'edit_item'     => 'Edit News Item',
			'new_item'      => 'New Item',
			'search_items'  => 'Search News',
		),
		'public'      => true,
		'has_archive' => true,
		'rewrite'     => array(
			'slug'       => 'news',
			'with_front' => false
		),
		'capability_type' => array( 'news_item', 'news_items' ),
		'map_meta_cap'    => true,
		'menu_icon'     => 'dashicons-welcome-widgets-menus',
		'menu_position' => 4,
		'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
	);
	
	var $taxonomy = null;
	
	public function __construct() {
		
		/** Translate CPT labels */
		foreach( $this->cpt_options['labels'] as $key => $label ) {
			$this->cpt_options['labels'][ $key ] = __( $label, 'njsl-news' );
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
		add_action( 'manage_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
		add_filter( 'term_link', array( $this, 'filter_term_link' ) );

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
			'title'    => __( 'Title',      'njsl-news' ),
			'author'   => __( 'Author',     'njsl-news' ),
			'category' => __( 'Categories', 'njsl-news' ),
			'tags'     => __( 'Tags',       'njsl-news' ),
			'date'     => __( 'Date',       'njsl-news' ),
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
			
			case 'category':
				
				$terms = wp_get_object_terms( $post_ID, $this->taxonomy->tax_slug );
				
				foreach( $terms as $term ) {
					echo '<a href=" ' . esc_url( add_query_arg( array( $this->taxonomy->tax_slug => $term->slug ) ) ) . '">' . $term->name . '</a>' . "<br>\n";
				}
				
				break;
		}
		
	}
	
	/**
	 * Change /news_category/* links to /news/category/*
	 */
	public function filter_term_link( $term_link ) {
		
		if( false !== strpos( $term_link, 'news_category' ) ) {
			return str_replace( '/blog/news_', '/news/', $term_link );
		}
		
		return $term_link;
	}

	/**
	 * Run actions on plugin activation - create capabilities
	 */
	public function activate() {
		
		$role = get_role( 'administrator' );
		if ( $role ) {
			$role->add_cap( 'edit_news_item' );
			$role->add_cap( 'read_news_item' );
			$role->add_cap( 'delete_news_item' );
			$role->add_cap( 'delete_news_items');
			$role->add_cap( 'edit_news_items' );
			$role->add_cap( 'edit_others_news_items' );
			$role->add_cap( 'delete_others_news_items' );
			$role->add_cap( 'publish_news_items' );
			$role->add_cap( 'edit_published_news_items' );
			$role->add_cap( 'delete_published_news_items' );
			$role->add_cap( 'delete_private_news_items' );
			$role->add_cap( 'edit_private_news_items' );
			$role->add_cap( 'read_private_news_items' );
		}

		$editor = get_role( 'editor' );
		if ( $editor ) {
			$editor->add_cap( 'edit_news_item' );
			$editor->add_cap( 'read_news_item' );
			$editor->add_cap( 'delete_news_item' );
			$editor->add_cap( 'delete_news_items');
			$editor->add_cap( 'edit_news_items' );
			$editor->add_cap( 'edit_others_news_items' );
			$editor->add_cap( 'delete_others_news_items' );
			$editor->add_cap( 'publish_news_items' );
			$editor->add_cap( 'edit_published_news_items' );
			$editor->add_cap( 'delete_published_news_items' );
			$editor->add_cap( 'delete_private_news_items' );
			$editor->add_cap( 'edit_private_news_items' );
			$editor->add_cap( 'read_private_news_items' );
		}
		
		$author = get_role( 'author' );
		if ( $author ) {
			$author->add_cap( 'edit_news_item' );
			$author->add_cap( 'read_news_item' );
			$author->add_cap( 'delete_news_item' );
			$author->add_cap( 'delete_news_items' );
			$author->add_cap( 'edit_news_items' );
			$author->add_cap( 'publish_news_items' );
			$author->add_cap( 'edit_published_news_items' );
			$author->add_cap( 'delete_published_news_items' );
		}
		
		$contributor = get_role( 'contributor' );
		if ( $contributor ) {
			$contributor->add_cap( 'edit_news_item' );
			$contributor->add_cap( 'read_news_item' );
			$contributor->add_cap( 'delete_news_item' );
			$contributor->add_cap( 'delete_news_items' );
			$contributor->add_cap( 'edit_news_items' );
		}
		
		$subscriber = get_role( 'subscriber' );
		if ( $subscriber ) {
			$subscriber->add_cap( 'read_news_item' );
		}
		
	}

}

class NJSL_News_Taxonomy {
	
	var $tax_slug    = 'news_category';
	var $tax_options = array(
		'labels'        => array(
			'name'              => 'News Categories',
			'singular_name'     => 'News Category',
			'search_items'      => 'Search News Categories',
			'all_items'         => 'All News Categories',
			'parent_item'       => 'Parent News Category',
			'parent_item_colon' => 'Parent News Category:',
			'edit_item'         => 'Edit News Category', 
			'update_item'       => 'Update News Category',
			'add_new_item'      => 'Add News Category',
			'new_item_name'     => 'New News Category',
			'menu_name'         => 'Categories',
		),
		'hierarchical' => true
	);
	var $post_type   = null;
	
	public function __construct() {
		
		/** Translate taxonomoy labels */
		foreach( $this->tax_options['labels'] as $key => $label ) {
			$this->tax_options['labels'][ $key ] = __( $label, 'njsl-news' );
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

$njsl_news = new NJSL_News_CPT;

?>