<?php
/*
Plugin Name: NJSL Campaign posts
Plugin URI: http://www.njstatelib.org
Description: Marketing campaign management
Version: 1.0
Author: David Dean for NJSL
Author URI: http://www.njstatelib.org
*/

class NJSL_Campaign_CPT {

	var $cpt_slug    = 'campaign';
	var $cpt_options = array(
		'labels' => array(
			'name'          => 'Campaigns',
			'singular_name' => 'Campaign',
			'edit_item'     => 'Edit Campaign',
			'new_item'      => 'New Campaign',
			'search_items'  => 'Search Campaigns',
		),
		'public'      => true,
		'has_archive' => true,
		'rewrite'     => array(
			'slug'       => 'campaigns',
			'with_front' => false
		),
		'capability_type' => array( 'campaign', 'campaigns' ),
		'map_meta_cap'    => true,
		'taxonomies'      => array( 'category' ),
		'menu_icon'       => 'dashicons-megaphone',
		'menu_position'   => 9,
		'supports'        => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'revisions' ),
	);
	
	var $taxonomy = null;
	
	public function __construct() {
		
		/** Translate CPT labels */
		foreach( $this->cpt_options['labels'] as $key => $label ) {
			$this->cpt_options['labels'][ $key ] = __( $label, 'njsl-campaigns' );
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
		
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		
		do_action( $this->cpt_slug . '_loaded' );
		
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
	 * Run actions on plugin activation - create capabilities
	 */
	public function activate() {
		
		$role = get_role( 'administrator' );
		if ( $role ) {
			$role->add_cap( 'edit_campaign' );
			$role->add_cap( 'read_campaign' );
			$role->add_cap( 'delete_campaign' );
			$role->add_cap( 'delete_campaigns');
			$role->add_cap( 'edit_campaigns' );
			$role->add_cap( 'edit_others_campaigns' );
			$role->add_cap( 'delete_others_campaigns' );
			$role->add_cap( 'publish_campaigns' );
			$role->add_cap( 'edit_published_campaigns' );
			$role->add_cap( 'delete_published_campaigns' );
			$role->add_cap( 'delete_private_campaigns' );
			$role->add_cap( 'edit_private_campaigns' );
			$role->add_cap( 'read_private_campaigns' );
		}

		$editor = get_role( 'editor' );
		if ( $editor ) {
			$editor->add_cap( 'edit_campaign' );
			$editor->add_cap( 'read_campaign' );
			$editor->add_cap( 'delete_campaign' );
			$editor->add_cap( 'delete_campaigns');
			$editor->add_cap( 'edit_campaigns' );
			$editor->add_cap( 'edit_others_campaigns' );
			$editor->add_cap( 'delete_others_campaigns' );
			$editor->add_cap( 'publish_campaigns' );
			$editor->add_cap( 'edit_published_campaigns' );
			$editor->add_cap( 'delete_published_campaigns' );
			$editor->add_cap( 'delete_private_campaigns' );
			$editor->add_cap( 'edit_private_campaigns' );
			$editor->add_cap( 'read_private_campaigns' );
		}
		
		$author = get_role( 'author' );
		if ( $author ) {
			$author->add_cap( 'edit_campaign' );
			$author->add_cap( 'read_campaign' );
			$author->add_cap( 'delete_campaign' );
			$author->add_cap( 'delete_campaigns' );
			$author->add_cap( 'edit_campaigns' );
			$author->add_cap( 'publish_campaigns' );
			$author->add_cap( 'edit_published_campaigns' );
			$author->add_cap( 'delete_published_campaigns' );
		}
		
		$contributor = get_role( 'contributor' );
		if ( $contributor ) {
			$contributor->add_cap( 'edit_campaign' );
			$contributor->add_cap( 'read_campaign' );
			$contributor->add_cap( 'delete_campaign' );
			$contributor->add_cap( 'delete_campaigns' );
			$contributor->add_cap( 'edit_campaigns' );
		}
		
		$subscriber = get_role( 'subscriber' );
		if ( $subscriber ) {
			$subscriber->add_cap( 'read_campaign' );
		}
		
	}
}

$njsl_campaigns = new NJSL_Campaign_CPT;

?>