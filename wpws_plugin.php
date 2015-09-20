<?php
/*
Plugin Name: WordPress Writing Services
Plugin URI: http://www.wpwritingservices.com
Description: Provides a way to manage client article requests
Version: 1.0
Author: John Phil
Author URI: http://www.wpwritingservices.com
License: GPLv2

*/
require plugin_dir_path( __FILE__ ) . '/includes/wpws.models.php'; //for database function calls (i.e. getting Product ID based on term_id_type, term_id_length, vice versa)
require plugin_dir_path( __FILE__ ) . '/includes/wpws.menus.php'; //for display menus/options/meta boxes
require plugin_dir_path( __FILE__ ) . '/includes/wpws.ajax.php'; //for AJAX processing
require plugin_dir_path( __FILE__ ) . '/includes/wpws.pager.class.php'; //for pagination
		
		
class WPWS //CONTROLLER
{
	//global variables
	private $is_login;
	private $role;
	public $wpws_model;
	public $wpws_menu;
	public $wpws_ajax;
	public $wpws_pager;
	
	protected $page_limit = 5;
	
    private $taxonomiesA = array('Type', 'Length');
	private $typeA = array('SEO','Article', 'Blog', 'Press', 'Review');
	private $lengthA = array('Short', 'Medium', 'Long');

	
	public function __construct()
	{
		//register an activation hook for the plugin
        register_activation_hook( __FILE__, array( $this, 'install' ) );
		
		//deactivation hook for the plugin
        register_deactivation_hook( __FILE__, array( $this, '__deconstuct' ) );
		
		//database object model
		$this->wpws_model = new WPWS_Model;
		
		//menu object
		$this->wpws_menu = new WPWS_Menu;
		
		//ajax object
		$this->wpws_ajax = new WPWS_AJAX;
		
		//pagination object
		$this->wpws_pager = new WPWS_Pager( $this->page_limit );
		
		$this->init();
		

		

	}
	
	public function __deconstruct()
	{
	
	}
	
	
	public function install() //for initially adding tables, options, and roles to the DB
	{
	
	
		//create options
		//initial version
		$version = '1.0'; 
		
		update_option( 'wpws_version', $version );
		
		//initial default product ID = 1
		update_option( 'wpws_default_product', 1 );
		
		
	    //create database tables
		$this->wpws_create_database();
		
			
		
	
		// do not generate any output here
        flush_rewrite_rules();	
	}
	
	public function uninstall() 
	{
		global $wpdb;	
		
		
		//delete options
		$optionA = array(
		'wpws_article_category_children',
		'wpws_article_length_children',
		'wpws_article_name',
		'wpws_article_type_children',
		'wpws_autotest',
		'wpws_categories',
		'wpws_default_product',
		'wpws_settings',
		'wpws_settings_options',
		'wpws_settings_pages',
		'wpws_settings_payemail',
		'wpws_version'
		);
		
			foreach( $optionA as $option_name ){
				
				delete_option( $option_name );	
			}
		
		
		//remove database tables	
		$wpdb->query( $wpdb->prepare( 'DROP TABLE ' . $wpdb->prefix . 'wpws_product' ) );
		$wpdb->query( $wpdb->prepare( 'DROP TABLE ' . $wpdb->prefix . 'wpws_product_order' ) );
		$wpdb->query( $wpdb->prepare( 'DROP TABLE ' . $wpdb->prefix . 'wpws_writer_task' ) );
		$wpdb->query( $wpdb->prepare( 'DROP TABLE ' . $wpdb->prefix . 'wpws_taxonomy' ) );
		$wpdb->query( $wpdb->prepare( 'DROP TABLE ' . $wpdb->prefix . 'wpws_taxonomy_term' ) );
		
		//remove capabilites and roles
		remove_role( 'wpws_client' );
		remove_role( 'wpws_writer' );
		
		//delete_post_meta_by_key
		$arguments = array (
			'post_type'		=>	'wpws_article',
			'post_status'	=>	'publish',
			'numberposts'	=>	-1
		);
		$posts_query = new WP_Query( $arguments );

		// If any posts are found
		if( $posts_query->have_posts() ) {

			while( $posts_query->have_posts() ) 
			{

				$posts_query->the_post();

				foreach( get_post_meta( get_the_ID() ) as $meta_key => $meta_value ) {
						

					if( false != strstr( $meta_key, 'wpws_meta' ) ) {
						delete_post_meta( get_the_ID(), $meta_key );;
						
					} // end if

				} // end foreach

			} // end while

		} // end if

		// Reset the post query
		wp_reset_postdata();
		
		
		// do not generate any output here
        flush_rewrite_rules();	
	}
	
	
	
	//BEGIN PULUGIN
	public function init()
	{
		
		//global variables
		add_action( 'init', array( $this, 'wpws_variables' ) ); 
		
		//add javascripts
		add_action('init', array( $this, 'wpws_scripts') );
		
		
		//cusotm posts, taxonomies, and terms  - must use the 'init' action hook
		add_action( 'init', array( $this, 'wpws_posts') ); 
		
		//meta boxes
		add_action( 'add_meta_boxes', array( $this, 'wpws_meta_box') );
		
		//Saving the meta boxes
		add_action( 'save_post', array( 'WPWS_Menu', 'wpws_save_article_meta') );
		
		
		//add roles
		$this->wpws_roles();
				
		
		//hide menus based on roles
		add_action( 'admin_menu', array( $this, 'wpws_hide_menus' ) );
		
		//Create top menu to set options
		add_action( 'admin_menu', function() { 
			

				//Client/s - label to display Client or Clients, depending on whether it's an Admin or client, respectively
				if( current_user_can( 'request_articles' ) && current_user_can( 'assign_writers' ) ){ //admin power
					
					$clientLabel = 'Clients';
				} else {
					$clientLabel = 'Client';
				}
 
				add_menu_page( 'WPWS Instruction','[WPWS]', 'assign_writers','wpws', array( 'WPWS_Menu', 'wpws_menu_instruction' ), plugins_url('/images/pen.gif', __FILE__), 50);
				
				//add submenu
			   add_submenu_page('wpws', '[WPWS] Settings', 'Settings', 'assign_writers', 'wpws-settings', array( 'WPWS_Menu', 'wpws_menu_settings' ) );
			   
			   //add submenu
			   add_submenu_page('wpws', '[WPWS] Writers', 'Writers', 'assign_writers', 'wpws-writers', array( 'WPWS_Menu', 'wpws_menu_writers' ) );
			   
			   //add submenu
			   add_submenu_page('wpws', '[WPWS] Orders', 'Orders', 'publish_articles', 'wpws-orders', array( 'WPWS_Menu', 'wpws_menu_orders' ) );
			   
			   //add submenu
			   add_submenu_page('wpws', '[WPWS] Tasks', 'Tasks', 'publish_articles', 'wpws-tasks', array( 'WPWS_Menu', 'wpws_menu_tasks' ) );
			   
			   //add submenu
			   add_submenu_page('wpws', '[WPWS] Client', $clientLabel, 'request_articles', 'wpws-client', array( 'WPWS_Menu', 'wpws_menu_client' ) );
			   
			    //add submenu
			   add_submenu_page('wpws', '[WPWS] Reports', 'Reports', 'view_overall_stats', 'wpws-reports', array( 'WPWS_Menu', 'wpws_menu_reports' ) );

		});
				
		//short codes
		add_shortcode( 'wpws-form', array( $this, 'wpws_submit_form_display' ) );
		
		//article form submission
		add_action( 'template_redirect', array( $this, 'wpws_submit_form_process') );
		
		
		//CSS
		add_action('admin_head', array($this, 'hide_new_menu') );
		
		//Hide dashboard
		add_action('wp_dashboard_setup', array( $this, 'redirect_from_dashboard' ) );
		
		//Columns - modification just for the custom post type (wpws_article)
		add_filter( 'manage_edit-wpws_article_columns', array($this, 'wpws_custom_edit_columns' ));
		add_action( 'manage_wpws_article_posts_custom_column' , array($this,'wpws_custom_column'), 10, 2 );
		
		

	}
	
	public function wpws_scripts()
	{
			
			wp_enqueue_script('jquery');
			
			
			//date picker 
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'jquery-ui-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.1/themes/smoothness/jquery-ui.css', true);
			
			//accordion
			wp_enqueue_script('jquery-ui-accordion');
			wp_enqueue_script(
			'custom-accordion',
			get_stylesheet_directory_uri() . '/js/accordion.js',
			array('jquery')
			);
			
			//custom javascript
			wp_register_script('wpws_js', plugins_url('js/wpws.js', __FILE__), array("jquery") );
  			wp_enqueue_script('wpws_js');
			
			//custom styles
			 wp_register_style( 'wpws_style', plugins_url('css/wpws.css', __FILE__) );
			 wp_enqueue_style( 'wpws_style' );
			
			 //Localize variables for AJAX
		    $ajax_params = array(
            'ajaxurl' => admin_url( 'admin-ajax.php' )
        	);

    		wp_localize_script('wpws_js', 'myAjax', $ajax_params); //send PHP values to Javascript
			
			//AJAX ACTIONS
			//For updating a Task status
			add_action( 'wp_ajax_order_status', array( 'WPWS_AJAX', 'order_status') );
	   	 	add_action( 'wp_ajax_nopriv_order_status', array( 'WPWS_AJAX', 'order_status') ); //not logged in
			
			//Metabox - when selecting a specific Order ID
			add_action( 'wp_ajax_metabox_orderID', array( 'WPWS_AJAX', 'metabox_orderID') );
	   	 	add_action( 'wp_ajax_nopriv_metabox_orderID', array( 'WPWS_AJAX', 'metabox_orderID') ); //not logged in
			
			//Sent Invoice - when selecting a specific Order ID
			add_action( 'wp_ajax_sentInvoice', array( 'WPWS_AJAX', 'sentInvoice') );
	   	 	add_action( 'wp_ajax_nopriv_sentInvoice', array( 'WPWS_AJAX', 'sentInvoice') ); //not logged in
			
			//Is OrderID paid? - when selecting a specific Order ID
			add_action( 'wp_ajax_isPaid', array( 'WPWS_AJAX', 'isPaid') );
	   	 	add_action( 'wp_ajax_nopriv_isPaid', array( 'WPWS_AJAX', 'isPaid') ); //not logged in
		
	}
	
	
	public function wpws_custom_edit_columns($columns) 
	{
		//unset( $columns['author'] );
		$columns['author']  = 'Author';
		$columns['orderID'] = 'Order ID';
		$columns['type'] = 'Type';
		$columns['length'] = 'Length';
		$columns['status'] = 'Status';
		$columns['due_date'] = 'Due Date';
	
		return $columns;
	}

	public function wpws_custom_column( $column, $post_id ) 
	{
	
	$statusA = array(
		'awaiting' => 'Awaiting',
		'inprogress' => 'In Progress',
		'completed' => 'Completed',
		'cancelled' => 'Cancelled'
	);	
	
	$wpws_length = $wpws_type = "";
		
		//Data should come from Post Meta
		$wpws_meta_orderID = get_post_meta( $post_id, 'wpws_meta_orderID', true );

		
		if( $column  == 'orderID'){
			
            	 echo $wpws_meta_orderID;
		}
		
		if( $column  == 'status'){
			$wpws_meta_status = get_post_meta( $post_id, 'wpws_meta_status', true );	
			
			$wpws_meta_status = !empty( $wpws_meta_status ) ? $statusA[ $wpws_meta_status ] : NULL;
			
			echo $wpws_meta_status;
		}
		
		if( $column  == 'due_date'){
			$wpws_meta_duedate = get_post_meta( $post_id, 'wpws_meta_duedate', true );	
			
			echo $wpws_meta_duedate;
		}
		
		if( $column  == 'type'){
		
		
			$wpwsO = WPWS_Model::getDataOrderID( $wpws_meta_orderID );
			
			if( isset( $wpwsO ) ){
			$term_id_type = $wpwsO->term_id_type;
			$wpws_type = WPWS_Model::getTermByTermID( $term_id_type );
			}

			
			
			echo "Type: " . $wpws_type;
		}
		
		if( $column  == 'length'){
			
			$wpwsO = WPWS_Model::getDataOrderID( $wpws_meta_orderID );
			
			if( isset( $wpwsO ) ){
			$term_id_length = $wpwsO->term_id_length;
			$wpws_length = WPWS_Model::getTermByTermID( $term_id_length );
			}

			
			echo "Length: " . $wpws_length;
		}
	
			
	}
	 
	
	public function redirect_from_dashboard() {
		if ( current_user_can('publish_articles') && !current_user_can('manage_options') ) {
			wp_redirect(admin_url('admin.php?page=wpws-orders')); exit;
		} else if(  current_user_can('request_articles') ){
			wp_redirect(admin_url('admin.php?page=wpws-client')); exit;
		}
	}
	
	
	public function hide_new_menu() {

	 //Only hide for Writer
	 if( current_user_can( 'publish_articles' ) && !current_user_can( 'edit_user' ) ){	
	  echo '<style type="text/css">
		#wp-admin-bar-new-content {display: none;}
		#wp-admin-bar-comments {display: none;}
		</style>';
	 }
	 
	}
	
	
	public function wpws_variables()
	{
		$this->is_login = is_user_logged_in();
		
	}
	
		
	//create database tables
	//singular naming
	
	public function wpws_create_database()
	{
	global $wpdb;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );


	//wpws_product
	$table_name = $wpdb->prefix.'wpws_product';
	if ( $wpdb->get_var("SHOW TABLES LIKE '" . $table_name . "'") != $table_name ):
		$creation_query = "
					CREATE TABLE `{$table_name}` (
					`productID`  int NOT NULL AUTO_INCREMENT,
					`term_id_type`  bigint(20) NULL ,
					`term_id_length`  bigint(20) NULL ,
					`price`  float(6,2) NULL ,
					`is_default`  bit(1) NULL ,
					PRIMARY KEY (`productID`)
					);
	
		";
		
		dbDelta( $creation_query );
	endif;
	
	//wpws_product_order
	$table_name = $wpdb->prefix.'wpws_product_order';
	if ( $wpdb->get_var("SHOW TABLES LIKE '" . $table_name . "'") != $table_name ):
		$creation_query = "
					CREATE TABLE `{$table_name}` (
					`orderID`  int NOT NULL AUTO_INCREMENT,
					`productID`  int(20) NULL ,
					`term_id_category`  bigint(20) NULL ,
					`quantity`  smallint(10) NULL ,
					`date`  datetime NULL ,
					`notes`  varchar(1024) NULL ,
					`userID`  bigint(20) NULL ,
					PRIMARY KEY (`orderID`)
					);
	
	
		";
		dbDelta( $creation_query );
	endif;
	
	//wpws_writer_task
	$table_name = $wpdb->prefix.'wpws_writer_task';
	if ( $wpdb->get_var("SHOW TABLES LIKE '" . $table_name . "'") != $table_name ):
		$creation_query = "
					CREATE TABLE `{$table_name}` (
					`taskID`  bigint(20) NOT NULL AUTO_INCREMENT ,
					`userID`  bigint(20) NULL ,
					`orderID`  int(11) NULL ,
					`status`  varchar(128) NULL,
					PRIMARY KEY (`taskID`)
					);
		";
		
		dbDelta( $creation_query );	
	endif;

	//wpws_taxonomy
	$table_name = $wpdb->prefix.'wpws_taxonomy';
	if ( $wpdb->get_var("SHOW TABLES LIKE '" . $table_name . "'") != $table_name ):
		$creation_query = "
					CREATE TABLE `{$table_name}` (
					`taxonomy_id`  bigint(20) NOT NULL AUTO_INCREMENT ,
					`taxonomy`  varchar(200) NULL ,
					PRIMARY KEY (`taxonomy_id`)
					)
					;
		";
		
		dbDelta( $creation_query );	
		
		//add taxonomies to custom database table
		$this->wpws_insert_taxonomy();
	endif;
	
	//wpws_taxonomy_term
	$table_name = $wpdb->prefix.'wpws_taxonomy_term';
	if ( $wpdb->get_var("SHOW TABLES LIKE '" . $table_name . "'") != $table_name ):
			$creation_query = "
					CREATE TABLE `{$table_name}` (
					`term_id`  bigint(20) NOT NULL AUTO_INCREMENT ,
					`taxonomy_id`  bigint(20) NOT NULL ,
					`term`  varchar(200) NULL ,
					PRIMARY KEY (`term_id`)
					)
					;
	
		";
		
		dbDelta( $creation_query );	
		
		//add terms to custom database table
		$this->wpws_insert_terms();
	endif;
	
	}
	
	public function wpws_hide_menus( )
	{
	global $menu;

		//if not admin but Writer, then hide other menus, except for the 'article'
		if ( current_user_can( 'publish_articles' ) && !current_user_can( 'edit_user' ) ){


					 foreach ( $menu as $mkey => $m ) {
						if( 
						!array_search( 'edit.php?post_type=wpws_article', $m )&& //display custom post type only
						!array_search( 'profile.php', $m )  //display profile
						
						){
							
							remove_menu_page($m[2]); 
						}
	
					}
					
					//Hide dashboard for client and writer
					if(current_user_can( 'publish_articles' ) || current_user_can( 'request_articles' ) ) remove_menu_page('index.php');	
					
		}


	}
	
	
	public function wpws_roles()
	{
		//Note: Everyone - Admin, Writer, Client can 'read_article'
		$role = get_role( 'administrator' );
		
		//new roles for admin only
		$role->add_cap( 'assign_writers' );
		$role->add_cap( 'view_overall_stats' );
		
			
		//Writer
		$writer = add_role( 'wpws_writer', 'Writer', array(
		
		//core-capabilities
		'read' => true,
		
		//custom capabilities
		'view_submitted_articles' => true,
		 'read_article' => true,
         'publish_articles' => true,
		 'edit_posts' => true,
         'edit_articles' => true,
         'edit_others_articles' => true,
		  'edit_published_articles' => true,
          'delete_articles' => true,
          'delete_others_articles' => true,
		 'delete_published_articles' => true,
          'read_private_articles' => true,
          'edit_article' => true,
          'delete_article' => true,
		  'view_overall_stats' => true
		  
		));
		
		
		//assign roles to admin as well
		if($writer !== null){
			$role->add_cap( 'view_submitted_articles' );
			$role->add_cap( 'read_article' );
			$role->add_cap( 'publish_articles' );
			$role->add_cap( 'edit_articles' );
			$role->add_cap( 'edit_others_articles' );
			$role->add_cap( 'edit_published_articles' );
			$role->add_cap( 'delete_others_articles' );
			$role->add_cap( 'delete_published_articles' );
			$role->add_cap( 'read_private_articles' );
			$role->add_cap( 'edit_article' );
			$role->add_cap( 'delete_article' );

		}
		
		//Client
		$client = add_role( 'wpws_client', 'Client', array(
		
		//core-capabilities
		'read' => true,
		
		//custom capabilities
		'request_articles' => true,
		'view_overall_stats' => true
		  
		));
		
		//assign roles to admin as well
		if($client !== null){
			$role->add_cap( 'request_articles' );
			
		}
		
	}
	
	public function wpws_posts()
	{
		$this->wpws_register_post_type();
		//$this->wpws_register_taxonomy();
	}
	

	public function wpws_register_post_type()
	{
	    $labels = array(

        'menu_name' => _x('[WPWS] Articles', 'wpws_article'),
		'name' 				 => 'Articles',
	    'singular_name'      => 'Article',
    	'add_new'            => 'Add New Article',
    	'add_new_item'       => 'Add New Article',
    	'edit_item'          => 'Edit Article',
		'new_item'           => 'New Article',
		'all_items'          => 'All Articles',
		'view_item'          => 'View Article',
		'search_items' => 'Search Articles'
    	);

    $args = array(

       'labels' => $labels,
	   

       'hierarchical' => true,

       'description' => 'Articles',

       'supports' => array('title', 'editor'),
	   'taxonomies' => array( 'category' ),

       'public' => true,

       'show_ui' => true,

       'show_in_menu' => true,

       'show_in_nav_menus' => true,

       'publicly_queryable' => true,

       'exclude_from_search' => true,

       'has_archive' => true,

       'query_var' => 'wpws_article',
        'rewrite' => array(
            'slug' => 'wpws_article',
            'with_front' => true,
        ),

       'can_export' => true,

       'capability_type' => 'wpws_article',
	   
	       'capabilities' => array(
            'read_post' => 'read_article',
            'publish_posts' => 'publish_articles',
            'edit_posts' => 'edit_articles',
            'edit_others_posts' => 'edit_others_articles',
			 'edit_published_posts' => 'edit_published_articles',
            'delete_posts' => 'delete_articles',
			'delete_published_posts' => 'delete_published_articles',
            'delete_others_posts' => 'delete_others_articles',
            'read_private_posts' => 'read_private_articles',
            'edit_post' => 'edit_article',
            'delete_post' => 'delete_article',

        ),
    	'map_meta_cap' => true,
	   
	   'menu_icon' => plugins_url('/images/pen.gif', __FILE__)

    );

    register_post_type('wpws_article', $args);	
		
	}
	
	//Taxonmy of articles
	public function wpws_insert_taxonomy()
	{
			
		$this->wpws_model->addTaxonomy( $this->taxonomiesA );

		
	}
	
	//Rewritten of ways to insert the terms into the wpws_taxonomy_term database table
	public function wpws_insert_terms()
	{
		//Type
		$taxonomy_id = $this->wpws_model->getTaxonomyID( 'Type' );
		foreach( $this->typeA as $term){
			$this->wpws_model->addTerm( $taxonomy_id, $term );	
		}
		
		//Length
		$taxonomy_id = $this->wpws_model->getTaxonomyID( 'Length' );
		foreach( $this->lengthA as $term){
			$this->wpws_model->addTerm( $taxonomy_id, $term );	
		}
		
	}
	
	//FUNCTION IS CANCELLED
	//Taxonmy of articles
	//Categories: Travel, Food, Business ,etc - hierachal
	/*
	public function wpws_register_taxonomy()
	{
		
    $args_type = array(
        'hierarchical' => true, 
        'query_var' => 'wpws_article_type', 
        'show_tagcloud' => true,
        'rewrite' => array(
            'slug' => 'article/type',
            'with_front' => false
        ),
		'show_ui' => true,
        'labels' => array(
				'name' => 'Articles Type',
				'add_new_item' => 'Add New Articles Type',
				'new_item_name' => "New Articles Type Name"
			),
    );
	
	 $args_length = array(
        'hierarchical' => true, 
        'query_var' => 'wpws_article_length', 
        'show_tagcloud' => true,
        'rewrite' => array(
            'slug' => 'article/length',
            'with_front' => false
        ),
		'show_ui' => true,
        'labels' => array(
				'name' => 'Articles Length',
				'add_new_item' => 'Add New Articles Type',
				'new_item_name' => "New Articles Type Name"
			),
    );

	
	//Set up the  taxonomy arguments. 
    $args_category = array(
        'hierarchical' => true, 
        'query_var' => 'wpws_article_category', 
        'show_tagcloud' => true,
        'rewrite' => array(
            'slug' => 'article/category',
            'with_front' => false
        ),
		'show_ui' => true,
        'labels' => array(
				'name' => 'Articles Category',
				'add_new_item' => 'Add New Articles Category',
				'new_item_name' => "New Articles Type Category"
			),
    );
	
		
	    // Register the taxonomies.
    register_taxonomy( 'wpws_article_type',  'wpws_article' , $args_type );
	register_taxonomy( 'wpws_article_length',  'wpws_article' , $args_length );
	register_taxonomy( 'wpws_article_category',  'wpws_article' , $args_category);

	}
	*/
	
	
	//FUNCTION IS CANCELLED
	//insert the terms into the taxonomy (wpws_taxonomy_term )
	/*
	public function wpws_insert_terms()
	{	
		
		//Type
		$taxonomy = 'wpws_article_type';
		foreach( $this->typeA as $term){
			
			wp_insert_term( $term, $taxonomy );	
		}
		
		//Length
		$taxonomy = 'wpws_article_length';
		foreach( $this->lengthA as $term){
			
			wp_insert_term( $term, $taxonomy );	
		}
		
		//Category
		$taxonomy = 'wpws_article_category';
		foreach( $this->categoryA as $term){
			
			wp_insert_term( $term, $taxonomy );	
		}

	}
	*/
	
	//create the meta box
	public function wpws_meta_box()
	{
	global $post;
			
			 	add_meta_box( 'wpws_article_meta_orderID', 'Order Information', array('WPWS_Menu', 'wpws_meta_box_orderID'),'wpws_article', 'side', 'low' );
				add_meta_box( 'wpws_article_meta_status', 'Status', array('WPWS_Menu', 'wpws_meta_box_status'),'wpws_article', 'side', 'low' );
				add_meta_box( 'wpws_article_meta_duedate', 'Due Date', array('WPWS_Menu', 'wpws_meta_box_duedate'),'wpws_article', 'side', 'low' );
				add_meta_box( 'wpws_article_meta_notes', 'My Notes', array('WPWS_Menu', 'wpws_meta_box_notes'),'wpws_article', 'normal', 'low' );
	}
	
	
	
	//Display form submission to submit article project
	public function wpws_submit_form_display()
	{
		$payemail = get_option( 'wpws_settings_payemail' );
		$wpws_settings_pages = get_option( 'wpws_settings_pages' );
		$productPage = $wpws_settings_pages['wpws_product_page'];
		$payURL = get_page_link( $productPage );
		
		//retrieve product ID, if exists
		if ( isset($_GET['productID'])) {
			
			$productID = intval( $_GET['productID'] );
			
			//Retrieve term IDs based on product ID
			$terms = $this->wpws_model->getTermsByProductID( $productID );

			$_POST['term_id_type'] = $terms['term_id_type']; 
			$_POST['term_id_length'] = $terms['term_id_length']; 
			

		}else{ //if no product ID is set, then use the one set in the Settings menu
			$productID = get_option( 'wpws_default_product' );
			
			//Retrieve term IDs based on product ID
			$terms = $this->wpws_model->getTermsByProductID( $productID );

			$_POST['term_id_type'] = $terms['term_id_type']; 
			$_POST['term_id_length'] = $terms['term_id_length']; 
		}
			
		$term_id_type =  !empty( $_POST['term_id_type'] ) ? intval($_POST['term_id_type']) : 0;
		$term_id_length =  !empty( $_POST['term_id_length'] ) ? intval($_POST['term_id_length']) : 0;
		$term_id_category =  !empty( $_POST['term_id_category'] ) ? intval($_POST['term_id_category']) : 0; //from actual wp_terms table
		$wpws_quantity =  !empty( $_POST['wpws_quantity'] ) ? intval($_POST['wpws_quantity']) : 0;
		$wpws_notes =  !empty( $_POST['wpws_notes'] ) ? $_POST['wpws_notes'] : NULL;
		
		//Login
		$user_login =  !empty( $_POST['user_login'] ) ? $_POST['user_login'] : NULL;

		
	?>
    		<!-- Display confirmation message to users who submit a book review -->
		<?php if ( isset ( $_GET['orderID'] ) &&  isset($_SERVER['HTTP_REFERER']) ) { 
		
			$orderID = intval( $_GET['orderID'] );
			
			//Get object data of Order ID
			$orderData = WPWS_Model::getDataOrderID( $orderID );
			
			//retrieve the product Type/Length name
			$item_type = WPWS_Model::getTermByTermID( $orderData->term_id_type );
			$item_type = !empty( $item_type ) ? $item_type : NULL;
			$item_length = WPWS_Model::getTermByTermID( $orderData->term_id_length );
			$item_length = !empty( $item_length ) ? $item_length : NULL;
			$item_name =  ($item_type && $item_length ) ? $item_type .'/'.$item_length : $item_type;
		
		?>
		<div style="background:#0C6;">
			Thank you for your request - please complete payment to continue. 
            
                <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
                    <input type="hidden" name="cmd" value="_xclick">
                    <input type="hidden" name="business" value="<?php echo $payemail;?>">
                    <input type="hidden" name="item_name" value="<?php echo $item_name;?>">
                    <input type="hidden" name="item_number" value="<?php echo $orderData->productID;?>">
                    <input type="hidden" name="quantity" value="<?php echo $orderData->quantity;?>">
                    <input type="hidden" name="currency_code" value="USD">
                    <input type="hidden" name="amount" value="<?php echo number_format($orderData->price,2); ?>">
                    <input type="hidden" name="return" value="<?php echo $payURL; ?>">
                    <input type="image" src="http://www.paypal.com/en_US/i/btn/x-click-but01.gif" name="submit" alt="Make payments with PayPal - it's fast, free and secure!">
            </form>

		</div>
		<?php 

		exit;} ?>
        

	<form method="post" id="submit-article" action="">
    
		<!-- Nonce fields to verify form submission -->
		<?php wp_nonce_field( 'articlesubmit_form', 'wpws_submit_form' ); ?>


	    <!-- Post variable to indicate user submitted  -->
		<input type="hidden" name="wpws_article_submit" value="1" />
        
        <!-- Hidden variable for ProductID -->
        <input type="hidden" name="productID" value="<?php echo $productID;?>" />
        

		<table>

			<tr>
				<td>Type</td>
				<td>
					<?php

					// Retrieve array of all types in system
					//$types = get_terms( 'wpws_article_type', array( 'orderby' => 'name', 'hide_empty' => 0 ) );
					$taxonomy_id = $this->wpws_model->getTaxonomyID( 'Type' );
					$types = $this->wpws_model->getTerms( $taxonomy_id );

					// Check if types were found
					if ( !is_wp_error( $types ) && !empty( $types ) ):

						echo '<select name="term_id_type">';
						echo '<option value="">---</option>';
						// Display all types
						foreach ( $types as $type ) {
						?>
							<option value="<?php echo $type->term_id;?>" <?php echo selected($term_id_type, $type->term_id);?>><?php echo $type->term;?></option>
						
                        <?php
                        }
						echo '</select>';
					endif;
					 ?>
				</td>
			</tr>
            <tr>
				<td>Length</td>
				<td>
					<?php

					// Retrieve array of all book types in system
					//$lengths = get_terms( 'wpws_article_length', array( 'orderby' => 'name', 'hide_empty' => 0 ) );
					$taxonomy_id = $this->wpws_model->getTaxonomyID( 'Length' );
					$lengths = $this->wpws_model->getTerms( $taxonomy_id );

					// Check if types were found
					if ( !is_wp_error( $lengths ) && !empty( $lengths ) ):

						echo '<select name="term_id_length">';
						echo '<option value="">---</option>';
						// Display all types
						foreach ( $lengths as $length ) {
						?>
							<option value="<?php echo $length->term_id;?>" <?php echo selected($term_id_length, $length->term_id);?>><?php echo $length->term;?></option>
						
                        <?php
                        }
						echo '</select>';
					endif;
					 ?>
				</td>
			</tr>
             <tr>
				<td>Category</td>
				<td>
					<?php
					
					$mycategories = get_option( 'wpws_categories' );
					
					if(!empty( $mycategories ) ){
					$mycategories = implode(",", $mycategories);
					}else{
						$mycategories = '';
					}
					
					$args = array(
						'type'                     => 'post',
						'child_of'                 => 0,
						'parent'                   => '',
						'orderby'                  => 'id',
						'order'                    => 'ASC',
						'hide_empty'               => 0, //include category including ones not part of a post yet
						'hierarchical'             => 1,
						'exclude'                  => '',
						'include'                  => $mycategories,
						'number'                   => '',
						'taxonomy'                 => 'category',
						'pad_counts'               => false 
					
					); 
					
					$categories = get_categories( $args );
					
					

					// Check if types were found
					if ( !is_wp_error( $categories ) && !empty( $categories) ):

						echo '<select name="term_id_category">';
						echo '<option value="">- Select -</option>';
						// Display all types
						foreach ( $categories as $category ) {
						?>
							<option value="<?php echo $category->term_id;?>" <?php echo selected($term_id_category, $category->term_id);?>><?php echo $category->name;?></option>
						
                        <?php
                        }
						echo '</select>';
					endif;
					 ?>
				</td>
			</tr>
            <tr>
				<td>Quantity</td>
				<td>
					<input type="text" name="wpws_quantity" size="3" value="<?php echo $wpws_quantity;?>" />
				</td>
			</tr>
            
            <tr>
				<td>Notes</td>
				<td><textarea name="wpws_notes"><?php echo $wpws_notes;?></textarea></td>
			</tr>
            
            <?php 
			if( !$this->is_login ):
			?>
            <tr>
            	<td colspan="3">Please Login/Register.</td>
               
            </tr>
            </tr>
            <tr>
				<td>Email:</td>
				<td>
					<input type="text" name="user_login" size="25" value="<?php echo $user_login;?>" />
				</td>
			</tr>
            </tr>
            <tr>
				<td>Password:</td>
				<td>
					<input type="password" name="user_password" size="25" value="" />
				</td>
			</tr>
           <?php
		   endif;
		   ?>
		</table>
        
        

		<input type="submit" name="submit" value="Submit" />
	</form>
    
<?php
		
	}
	
	public function wpws_submit_form_process( $template )
	{
	global $current_user; //for retrieving the user ID, if logged in
		
		if ( !empty( $_POST['wpws_article_submit'] ) ) {
				
			//Ensure all data are submitted first
			if ( wp_verify_nonce( $_POST['wpws_submit_form'], 'articlesubmit_form' ) && 
			!empty( $_POST['term_id_type'] ) && 
			!empty( $_POST['term_id_category'] ) && 
			!empty( $_POST['wpws_quantity'] ) && 
			!empty( $_POST['wpws_notes'] ) ){
			
	
			
			/*******PROCESS SUBMISSION FORM *********/
					//Register or Login user
					if( !$this->is_login ){ //If user is not logged in
						
						//Did user enter password?
						//////////
						if( empty($_POST['user_login'])  || empty($_POST['user_password']) ){
							 
							 $error_message = "Please enter email/password.";
				
							 wp_die( $error_message );
							exit;
							
						}
						///////////
						
						$user_login = esc_attr( $_POST['user_login']  );
						$user_password = $_POST['user_password'];
						
						/*
						check if username exists. if it does, then check to see if credential are correct.
						If username does not exist, then register user if they have a password.
						*/
						
								$user = get_user_by( 'email', $user_login );
								if($user){ //verify and log the user
								
									$credentials = array();
									$credentials['user_login'] = $user->user_login;
									$credentials['user_password'] = $user_password;
									$credentials['remember'] = true;

									$userID = $this->_loginUser( $credentials );
									if( !$userID ){ //call utility function to logged in

									 $error_message = "Wrong Email/Password combination";
				
									 wp_die( $error_message );
									 exit;	
									}
									
								} else{ //register the user
									
									//Both username/email will be the same
									$userA = array(
										'user_login' => $user_login, 
										'user_pass' => $user_password,
										'user_email' => $user_login,
										'role' => 'wpws_client'
									);
									$userID = wp_insert_user( $userA );
									
									
									//Log user in
									$credentials['user_login'] = $user_login;
									$credentials['user_password'] = $user_password;
									$credentials['remember'] = true;
									$this->_loginUser( $credentials );

								}
						
						
					} else{ //User already logged in, so use current_user global variable
						
						$userID = $current_user->ID;
					}
					
			
			/*****************************************/
			//Save data to the system
			$term_id_type = intval($_POST['term_id_type']);
			$term_id_length = intval($_POST['term_id_length']);
			$term_id_category = intval($_POST['term_id_category']);
			$quantity = intval($_POST['wpws_quantity']);
			$notes = esc_textarea( $_POST['wpws_notes'] );

			
			$productID = !empty( $_POST['productID'] ) ? $_POST['productID'] : 0;
			
			//Get Product ID based on Type/Length (if productID is not set)
			//if( !$productID ){
				
				
				//Ensure that Type/Length selected has the proper productID
				$productID = $this->wpws_model->getProductIDByTerm( $term_id_type, $term_id_length );
				
				//If productID is still not yet, that means, the product do not exist
				//Display error message
				if(! $productID ){
					
				 $wpws_settings_pages = get_option( 'wpws_settings_pages' );
				 $productPage = $wpws_settings_pages['wpws_product_page'];

				 $error_message = "Product/service combination does not exist. Either you can go back or click below to view the products/services available.<br />";
				 $error_message .= "<a href='".get_page_link( $productPage ) . "'>Products/Services</a>";
				
				 wp_die( $error_message );
                exit;
				}
			//}
			

			//Store into database table
			$datetime = date( 'Y-m-d g:i:a');
			$data = array(
				
				'date' => $datetime,
				'productID' =>$productID,
				'term_id_category' =>$term_id_category,
				'quantity' =>$quantity,
				'notes' =>$notes,
				'userID' => $userID
			);
			
			//Insert into product order table
			$orderID = $this->wpws_model->addOrder( $data ); 
			
			
			// SUCCESS! Redirect back
			$address = ( empty( $_POST['_wp_http_referer'] ) ? site_url() : $_POST['_wp_http_referer'] );
			wp_redirect( add_query_arg( 'orderID', $orderID, $address ) );
			exit;
			
			} else{ //Fields were empty
				
				 $error_message = "Please go back and fill out all the info.";

				
				 wp_die( $error_message );
                exit;
				
			}
			
			
			
		
			
		} else{
		
			return $template;
				
		}
		
		
	}


	//UTILITY FUNCTIONS
	
	//Log the user in
	//Return: User ID, if successful
	//Return: False, if unsuccessful
	public function _loginUser( $credentials ) 
	{
		$user = wp_signon( $credentials, false );
		
		if ( is_wp_error( $user ) ) {
			$error = $user->get_error_message();
			
			return false;
		} else {
			wp_set_current_user( $user->ID, $user->user_login );
			do_action('set_current_user');
			return $user->ID;
		
		}	
		exit;
		
	}
	
	
	
	
	public function  pr( $data )
	{
		echo '<pre>';
		print_r($data);
		echo '</pre>';	
		
	}		
	
	
	
}

$wpws = new WPWS;
?>