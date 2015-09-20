<?php
class WPWS_Menu extends WPWS //VIEW/CONTROLLER
{
	protected static $errorMessages = array(
		0=> 'No such errors',
		1=> 'Product already exists'
	);
	
	public function __construct()
	{
		$this->wpws_model = new WPWS_Model;
		
		
		// Register function to be called when administration pages init takes place
		add_action( 'admin_init', array( $this, 'wpws_admin_init' ));	
	}
	
	// Register functions to be called when options are saved
	public function wpws_admin_init() 
	{
		//Saving the pages settings
		add_action('admin_post_save_settings_pages', array( $this, 'wpws_process_settings_pages' ));
		
		//Saving the categories settings
		add_action('admin_post_save_settings_categories', array( $this, 'wpws_process_settings_categories' ));
		
		//Saving the products settings
		add_action('admin_post_save_settings_products', array( $this, 'wpws_process_settings_products' ));
		
		//Saving the payemail setting
		add_action('admin_post_save_settings_payemail', array( $this, 'wpws_process_settings_payemail' ));
		
		//Saving the prices settings
		add_action('admin_post_save_settings_prices', array( $this, 'wpws_process_settings_prices' ));
		
		//Saving the claim orders
		add_action('admin_post_save_settings_orders', array( $this, 'wpws_process_settings_orders' ));
		
		////////////////////////////////////////
		//Deleting a product?
		if( isset( $_GET['action']  ) && $_GET['action'] == 'del' ) {
		
			//Only the admin can delete something
			if( !current_user_can( 'assign_writers' ) ){
				
				return false;
			}
			
			//Is there an ID?
			if( !isset( $_GET['productID'] )){
				return false;
			}
			
			//Get the product ID to delete
			$productID = intval( $_GET['productID'] );
			
			if( $this->wpws_model->delProduct( $productID ) ){
			
				//Redirect page to Settings page
				$cleanaddress = add_query_arg( array( 'page' => 'wpws-settings','updated' => 1), admin_url( 'admin.php' ) );
				wp_redirect( $cleanaddress );
				exit;	
			}

		}
		///////////////////////////////////////

	}
	
	//Update the pages
	public function wpws_process_settings_pages()
	{
		// Check if user has proper security level (only admin can modify settings)
		if ( !current_user_can( 'assign_writers' ) )
			wp_die( 'Not allowed' );

		// Check if nonce field is present for security
		check_admin_referer( 'wpws_settings_pages' );
		
		//Retrieve data (order/product) pages from the form
		$wpws_order_page = intval( $_POST['wpws_order_page'] );
		$wpws_product_page = intval( $_POST['wpws_product_page'] );
		

		$wpws_settings_pages['wpws_order_page'] = $wpws_order_page;
		$wpws_settings_pages['wpws_product_page'] = $wpws_product_page;

		
		//Update the option
		update_option( 'wpws_settings_pages', $wpws_settings_pages );

		//Redirect page to Settings page
		$cleanaddress = add_query_arg( array( 'page' => 'wpws-settings','updated' => 1), admin_url( 'admin.php' ) );
		wp_redirect( $cleanaddress );
		exit;	
		
	}
	
	//Update the categories
	public function wpws_process_settings_categories()
	{
		// Check if user has proper security level (only admin can modify settings)
		if ( !current_user_can( 'assign_writers' ) )
			wp_die( 'Not allowed' );

		// Check if nonce field is present for security
		check_admin_referer( 'wpws_settings_categories' );
		

		//ensures all data in array is a valid integer type
		$categoriesA = array_map( 'intval', $_POST['wpws_product_category'] );
		
		update_option( 'wpws_categories', $categoriesA  );

		//Redirect page to Settings page
		$cleanaddress = add_query_arg( array( 'page' => 'wpws-settings','updated' => 1), admin_url( 'admin.php' ) );
		wp_redirect( $cleanaddress );
		exit;	
		
	}
	
		
	//Add the products
	public function wpws_process_settings_products()
	{
		// Check if user has proper security level (only admin can modify settings)
		if ( !current_user_can( 'assign_writers' ) )
			wp_die( 'Not allowed' );

		// Check if nonce field is present for security
		check_admin_referer( 'wpws_settings_products' );
		
		//If Type is not sleected
		if(!isset( $_POST['wpws_product_type'] ) ){
			
			//Redirect page to Settings page
		wp_die( 'Please select the Type' );	
			
		}
		

		//Retrieve data (type/length) from the form
		$wpws_product_type = intval( $_POST['wpws_product_type'] );
		$wpws_product_length = intval( $_POST['wpws_product_length'] );
		
		//If no length is selected, then it's just 0
		$wpws_product_length = !empty( $wpws_product_length ) ?  $wpws_product_length : 0;
		

		
		//Check to see if product already exists. If so, just redirect back with an error message.
		if( $this->wpws_model->getProductIDByTerm( $wpws_product_type, $wpws_product_length ) ){
			
			//Redirect page to Settings page
			$cleanaddress = add_query_arg( array( 'page' => 'wpws-settings','errorMsg' => 1), admin_url( 'admin.php' ) );
			wp_redirect( $cleanaddress );
			exit;
		}
		
		
		//store into an array to be added to the system
		$dataProductA['wpws_product_type'] = $wpws_product_type;
		$dataProductA['wpws_product_length'] = $wpws_product_length;
		
		$this->wpws_model->addProduct( $dataProductA );

		//Redirect page to Settings page
		$cleanaddress = add_query_arg( array( 'page' => 'wpws-settings','updated' => 1), admin_url( 'admin.php' ) );
		wp_redirect( $cleanaddress );
		exit;	
		
	}
	
	
	//Update the prices
	public function wpws_process_settings_prices()
	{
		// Check if user has proper security level (only admin can modify settings)
		if ( !current_user_can( 'assign_writers' ) )
			wp_die( 'Not allowed' );

		// Check if nonce field is present for security
		check_admin_referer( 'wpws_settings_prices' );
		
		//Update a default product if it's set
		if( isset( $_POST['wpws_product_default'] ) ){
			
			$defaultProductID = intval( $_POST['wpws_product_default'] );
			update_option( 'wpws_default_product', $defaultProductID );
		}
		
		//update prices 
		if( isset( $_POST['wpws_product_price'] ) ){
			
			
			//make sure it's a number (is_numeric)
			foreach( $_POST['wpws_product_price'] as $productID => $price ):
			
				$data['productID'] = $productID;
				$data['price'] = $price;
			
				$this->wpws_model->updatePriceProduct( $data );
				
			endforeach;
			
			
			//Redirect page to Settings page
			$cleanaddress = add_query_arg( array( 'page' => 'wpws-settings','updated' => 1), admin_url( 'admin.php' ) );
			wp_redirect( $cleanaddress );
			exit;	
			
		}
		
	}
	
	//Update the Paypal email
	public function wpws_process_settings_payemail()
	{
		// Check if user has proper security level (only admin can modify settings)
		if ( !current_user_can( 'assign_writers' ) )
			wp_die( 'Not allowed' );

		// Check if nonce field is present for security
		check_admin_referer( 'wpws_settings_payemail' );
		
		//Update a default product if it's set
		if( isset( $_POST['wpws_payemail'] ) ){
			
			$wpws_payemail = sanitize_email( $_POST['wpws_payemail'] );
			update_option( 'wpws_settings_payemail', $wpws_payemail );
			
			//Redirect page to Settings page
			$cleanaddress = add_query_arg( array( 'page' => 'wpws-settings','updated' => 1), admin_url( 'admin.php' ) );
			wp_redirect( $cleanaddress );
			exit;	
			
		}
	}
	
	//Claiming orders
	public function wpws_process_settings_orders()
	{
		global $current_user;
	
		// Check if user has proper security level (only admin/writer can claim an order)
		if ( !current_user_can('publish_articles') )
			wp_die( 'Not allowed' );
			
			//get user ID (to determine who claim the orderID)
			$userID = $current_user->ID;
			
			$orderClaiming = esc_attr( $_POST['orderClaiming'] );
			
			$orderIDA = $_POST['orderIDA']; //array of orderID(s)
			
			$data = array(
						'userID' => $userID,
						'orderIDA' => $orderIDA
			);
					
			switch( $orderClaiming )
			{
				
				case 'Claim':
					$this->wpws_model->updateOrderProject( $data, 'claim' );
				break;	
				
				case 'UnClaim':
					$this->wpws_model->updateOrderProject( $data, 'unclaim' );
				break;	
			}
			
			//Redirect page to Orders page
			$cleanaddress = add_query_arg( array( 'page' => 'wpws-orders','updated' => 1), admin_url( 'admin.php' ) );
			wp_redirect( $cleanaddress );
			exit;
			
	}
	
	//save the meta boxes data
	public function wpws_save_article_meta(  $post_id  )
	{
	
	
		//Return if it's an autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}
		
		
		// Check if nonce field is present for security
		// Verify that the nonce is valid.
		//if ( ! wp_verify_nonce( $_POST['wpws_metabox_nounce'], 'wpws_meta_process' ) )
		//	return $post_id;
			
		//Only process if it's a wpws_article type
		if( !isset( $_POST['post_type']) ) return;
		if( !$_POST['post_type'] == 'wpws_article' ) return;
		
		//Update the meta data	
		if ( isset( $_REQUEST['wpws_meta_orderID'] ) ) {
       		 update_post_meta( $post_id, 'wpws_meta_orderID', intval( $_REQUEST['wpws_meta_orderID'] ) );
    	}
		
		if ( isset( $_REQUEST['wpws_meta_status'] ) ) {
       		 update_post_meta( $post_id, 'wpws_meta_status', sanitize_text_field( $_REQUEST['wpws_meta_status'] ) );
    	}
		
		if ( isset( $_REQUEST['wpws_meta_duedate'] ) ) {
       		 update_post_meta( $post_id, 'wpws_meta_duedate', sanitize_text_field( $_REQUEST['wpws_meta_duedate'] ) );
    	}
		
		if ( isset( $_REQUEST['wpws_meta_notes'] ) ) {
       		 update_post_meta( $post_id, 'wpws_meta_notes', esc_textarea( $_REQUEST['wpws_meta_notes'] ) );
    	}

		
	}

	public function wpws_menu_instruction()
	{
		
	?>
<div class="wrap" style="width:80%;">
		<h2>Instructions</h2>
		<p>First, you will need to create two pages in your Wordpress Adminstration page.</p>
		<ol>
		  <li>An Order page, where your clients/customers can submit their content request.</li>
		  <li>A Product page, where anyone can view the listing of writing services.</li>
</ol>	
        
        
        <p>For your Order page, you can title the page however you want. This will be the page that you will insert a special short code to dispaly a form.  You will then insert a special short code that will automatically display a submission form in the 'Order' page. The 'Order' page can be customized to however you see fit, but the shortcode will provide the submission form for your client.<br />
          <br />
        Similarly, your 'Product' page will contain a description of your writing services and a link/button that will redirect your customer to purchase/order that specific service. Under the [WPWS]-&gt;'Settings' submenu, you can set prices for your services. This will be based on a combination of the Type (SEO, Blog, Article, etc) with the Length (Short, Medium, Long). For instance, you could set a short Blog writing service to be only $3 each, a long Article to be $7, and so on. In the future, this program might allow more flexibilities.      </p>
<p><strong>Short Codes</strong></p>
<p>[wpws-form] = the short code you will need to insert into your Order page. It will generate a submission form.</p>
<p>The forms currently allows your customer to select these attributes:</p>
<ul>
  <li>Type of service</li>
  <li>Length of content</li>
  <li> Category of the content</li>
  <li>Quantity of article/content (i.e. a customer may request 3 articles of the same category)</li>
  <li>Notes (any additonal information that the customer want i.e. keywords, sources for content, etc)</li>
</ul>
<h3>Content Types and Services</h3> 
  <p> There are two main attributes for your writing services. The 'Type' of content (SEO, Blog, Review, Article, etc) and the optionally Length (Long, Medium, Short). Right now, these are the initial/defaut placeholders for the writing service(s) you want to offer. Feel free to add/delete the 'types' of articles under the 'Articles Type' menu page. Likewise, the same applies to the Length ('Articles Length') and Category ('Articles Category') of your services.</p>
  
      <h3>Clients and Registration</h3>
      <p>On the Order page, if a visit is not logged in, the form will display the Email/Password field. If a user is already registered, the user/client will just enter his/her email/password combination to log in. If it's a new<br />
      user, then the Email/Password fields will serve as a new user registration. To keep thing simple, a username is his/her email. </p>
      <p>The purpose of membership registration/login is for your client to have his/her order associate with a product. 
      In addition, a client can logged in and request articles to submit. He/she can also view statistics and status of the project request.</p>
</div>
  <?php    
	}
	
	public function wpws_menu_settings()
	{
		
		
		//Load current set pages
		$wpws_settings_pages = get_option( 'wpws_settings_pages' );
		
		$wpws_settings_pages['wpws_order_page'] = !empty( $wpws_settings_pages['wpws_order_page'] ) ? $wpws_settings_pages['wpws_order_page'] : array();
		$wpws_settings_pages['wpws_product_page'] = !empty( $wpws_settings_pages['wpws_product_page'] ) ? $wpws_settings_pages['wpws_product_page'] : array();

		
		//Load existing pages
		$args_page = array(
		'sort_order' => 'ASC',
		'sort_column' => 'post_title',
		'hierarchical' => 1,
		'authors' => '',
		'child_of' => 0,
		'parent' => -1,
		'exclude_tree' => '',
		'number' => '',
		'offset' => 0,
		'post_type' => 'page',
		'post_status' => 'publish'
		); 
		
		$pagesA = get_pages($args_page); 



	?>
	<div class="wrap">
    
          <!-- Display confirmation message -->
		<?php if ( isset ( $_GET['updated'] ) && $_GET['updated'] ) { ?>
        
    		<div id="message" class="updated"> Settings saved!</div>
            
		<?php } else if(isset ( $_GET['errorMsg'] ) && $_GET['errorMsg'] ){ 
					$msgID = intval($_GET['errorMsg']);
					
							
			?> 
				<div id="message" class="error"> <?php echo WPWS_Menu::$errorMessages[ $msgID ];?></div>
		<?php	} ?>
    
    <h2>Settings</h2>	
    <h3>Pages</h3>
 
            <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">

	<!-- to determine which action to process -->
	  <input type="hidden" name="action" value="save_settings_pages" />
	  
	  <!-- Adding security through hidden referrer field -->
	  <?php wp_nonce_field( 'wpws_settings_pages' ); ?>
      

<table width="768" class="wide-fat">

     <tr>
     	<td colspan="3">Please select your Order and Product page, then hit 'Submit' to set them.<br />
     	  Remember to add the correct short code on the pages.</td>
     </tr>
     
     <tr>
			<td width="24%">Order page:</td>
			<td width="76%">
            <select name="wpws_order_page">
            <option value="0">-- Select--</option>
            <?php foreach($pagesA as $page):?>
            	<option value="<?php echo $page->ID;?>" <?php echo selected($wpws_settings_pages['wpws_order_page'], $page->ID);?>><?php echo $page->post_title;?></option>
            <?php endforeach;?>
            </select>&nbsp;(page that will display the article submission form)</td>
  </tr>
        
        <tr>
			<td>Product listing page:</td>
			<td> 
            <select name="wpws_product_page">
            <option value="0">-- Select--</option>
            <?php foreach($pagesA as $page):?>
            	<option value="<?php echo $page->ID;?>" <?php echo selected($wpws_settings_pages['wpws_product_page'], $page->ID);?>><?php echo $page->post_title;?></option>
            <?php endforeach;?>
            </select>
			  (page that will display your services/products)</td>
		</tr>
        
	</table>

<input type="submit" value="Submit" class="button-primary"/>
</form><hr />

<h3>Categories</h3>
<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">

	<!-- to determine which action to process -->
    <input type="hidden" name="action" value="save_settings_categories" />
	  
	  <!-- Adding security through hidden referrer field -->
	  <?php wp_nonce_field( 'wpws_settings_categories' ); ?>
      

<table width="768" class="wide-fat">

     <tr>
     	<td colspan="3"><p>Check the category that will be part of WPWS categories for your article product. In other words, you will create a subcategory list based on the categories you created in the 'Posts' section. These will be in the drop-down menu on the &quot;Order page&quot;</p></td>
     </tr>
     
     <tr>
			<td width="15%" valign="top">
            <?php

					// Retrieve array of all categories
					$args = array(
					'type'                     => 'post',
					'child_of'                 => 0,
					'parent'                   => '',
					'orderby'                  => 'id',
					'order'                    => 'ASC',
					'hide_empty'               => 0, //include category including ones not part of a post yet
					'hierarchical'             => 1,
					//'exclude'                  => '',
					//'include'                  => $mycategories,
					'number'                   => '',
					'taxonomy'                 => 'category',
					'pad_counts'               => false 
				
				); 
				
				$categories = get_categories( $args );
				
					//retrieve the saved selected categories from the Options table
					$mycategories = get_option( 'wpws_categories' );
					$mycategories = !empty( $mycategories ) ? $mycategories : array();

					// Check if categories were found
					if ( !is_wp_error( $categories ) && !empty( $categories ) ):

						echo '<ul>';
						// Display all types
						foreach( $categories as $category){
						?>
							<li><input name="wpws_product_category[]" type="checkbox" value="<?php echo $category->term_id;?>" <?php if( in_array( $category->term_id, $mycategories) ) echo 'checked';?> /><?php echo $category->name;?></li>
						
                        <?php
                        }
						echo '</ul>';
					endif;
				?>
            
            
            </td>

  </tr>
        
	</table>

<input type="submit" value="Submit" class="button-primary"/>
</form><hr />


<h3>Products</h3>
<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">

	<!-- to determine which action to process -->
    <input type="hidden" name="action" value="save_settings_products" />
	  
	  <!-- Adding security through hidden referrer field -->
	  <?php wp_nonce_field( 'wpws_settings_products' ); ?>
      

<table width="768" class="wide-fat">

     <tr>
     	<td colspan="3"><p>Please select the product Type on the left column and a Length on the right column. The combination of the two will generate a new product (i.e. Article+Short).</p></td>
     </tr>
     
     <tr>
     	<th align="left">Type</th>
        <th align="left">Length</th>
     </tr>
     
     <tr>
			<td width="15%" valign="top">
            <?php

					// Retrieve array of all types in system
					//$types = get_terms( 'wpws_article_type', array( 'orderby' => 'name', 'hide_empty' => 0 ) );
					$taxonomy_id = WPWS_Model::getTaxonomyID( 'Type' );
					$types = WPWS_Model::getTerms( $taxonomy_id );

					// Check if types were found
					if ( !is_wp_error( $types ) && !empty( $types ) ):

						echo '<ul>';
						// Display all types
						foreach ( $types as $type ) {
						?>
							<li><input name="wpws_product_type" type="radio" value="<?php echo $type->term_id;?>" /><?php echo $type->term;?></li>
						
                        <?php
                        }
						echo '</ul>';
					endif;
				?>
            
            
            </td>
			<td width="85%" valign="top">
              <?php

					// Retrieve array of all lengths in system
					//$lengths = get_terms( 'wpws_article_length', array( 'orderby' => 'name', 'hide_empty' => 0 ) );
					$taxonomy_id = WPWS_Model::getTaxonomyID( 'Length' );
					$lengths = WPWS_Model::getTerms( $taxonomy_id );
					

					// Check if types were found
					if ( !is_wp_error( $lengths ) && !empty( $lengths ) ):

						echo '<ul>';
						// Display all lengths
						foreach ( $lengths as $length ) {
						?>
							<li><input name="wpws_product_length" type="radio" value="<?php echo $length->term_id;?>" /><?php echo $length->term;?></li>
						
                        <?php
                        }
						echo '</ul>';
					endif;
				?>
            
            
            </td>
  </tr>
        
	</table>

<input type="submit" value="Submit" class="button-primary"/>
</form><hr />

<h3>Prices/Products</h3>

<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">

	<!-- to determine which action to process -->
    <input type="hidden" name="action" value="save_settings_prices" />
	  
	  <!-- Adding security through hidden referrer field -->
	  <?php wp_nonce_field( 'wpws_settings_prices' ); ?>
      
      <?php
	  
	  		//The link set for the 'Order'
			//(set at the time, so the variable can be reusable in the instruction)
			
			//Load current set pages
			$wpws_settings_pages = get_option( 'wpws_settings_pages' );
			$wpws_order_page = $wpws_settings_pages['wpws_order_page'];
		
	  		$thelink = get_permalink( $wpws_order_page );
	  
	  ?>
      

<table width="100%" class="wide-fat wp-list-table">

     <tr>
     	<td colspan="11"><p>Once you selected to create a product above, you will see it in the list below, which also contains unique URL that links to your product order form. In addition, you may delete your product or edit its price.</p>
   	    <p>To delete a product, just click the [X] on which Product ID you want to remove.<br />
   	      To update a Price, just enter your new price and hit 'Submit'.   	      </p>
   	    <p>The 'Defaut' option allows you to pick a default product/service to be displayed if your client just lands on the <a href="<?php echo $thelink;?>" target="_new">Order</a> page (without any product selected yet).<br />
        </p></td>
     </tr>

        <tr>
        <th width="2%">&nbsp;</th>
        <th width="7%" align="left">Is Default?</th>
        <th width="13%" align="left">&nbsp;Product ID/Item Number</th>
        <th width="7%" align="left">Type</th>
        <th width="8%" align="left">Length</th>
        <th width="8%" align="left">Price</th>
        <th width="55%" align="left">URL (use for linking to an individual writing service/product on the Order page)</th>
      	</tr>
    </thead>
  
  <?php
  	$productA = WPWS_Model::getProducts();
	
	if( $productA ):
	
		$wpws_default_product = get_option( 'wpws_default_product' ); //get default product ID (if not set, it's 1)

		foreach($productA as $product ):
		
		$productID = $product->productID;
		$type = WPWS_Model::getTermByTermID( $product->term_id_type );
		$length = WPWS_Model::getTermByTermID( $product->term_id_length );
		$delURL =  add_query_arg( array( 'page' => 'wpws-settings',  'action' => 'del', 'productID' => $productID ), admin_url( 'admin.php' ) );
		
		
		$url = $thelink . '?productID='.$productID;
  ?>
  <tr>
    <td>&nbsp;<a href="<?php echo $delURL;?>">[X]</a></td>
    <td>&nbsp;<input type="radio" name="wpws_product_default" value="<?php echo $productID;?>" <?php checked($wpws_default_product, $productID);?>/></td>
    <td>&nbsp;<?php echo $productID;?></td>
    <td>&nbsp;<?php echo $type;?></td>
    <td>&nbsp;<?php echo $length;?></td>
    <td>&nbsp;$<input name="wpws_product_price[<?php echo $productID;?>]" type="text" size="7" maxlength="10" value="<?php echo $product->price;?>" /></td>
    <td>&nbsp;<input name="wpws_product_url" type="text" value="<?php echo esc_attr( $url );?>" size="60" readonly="readonly" /></td>
  </tr>
  <?php
  		endforeach;
  	else:
	?>	
<tr>
    <td colspan="8">&nbsp;No Products</td>

  </tr>
  <?php
  	endif;
  ?>
  

     
        
</table>

<input type="submit" value="Submit" class="button-primary"/>
</form><hr />

<h3>Paypal Email</h3>


<?php

$wpws_settings_payemail = get_option( 'wpws_settings_payemail' );

?>

<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">

	<!-- to determine which action to process -->
    <input type="hidden" name="action" value="save_settings_payemail" />
	  
	  <!-- Adding security through hidden referrer field -->
	  <?php wp_nonce_field( 'wpws_settings_payemail' ); ?>
      
      

<p>Enter the PayPal email which you will accept payment from. </p>


<input name="wpws_payemail" type="text" value="<?php echo $wpws_settings_payemail;?>" size="25" /></td>

<input type="submit" value="Submit" class="button-primary"/>
</form><hr />





</div>

        
    <?php  
	  
	}
	
	public function wpws_menu_writers()
	{
	?>
    
<div class="wrap">
		<h2>Writers</h2>	
        
        
  <table cellspacing="0" class="wp-list-table widefat  pages" style="margin: 5px;">
  <thead>
	<tr>
    	<th width="133" class="manage-column column-orderid sortable desc" id="title" style="" scope="col"><a href="wp-admin/admin.php?page=wpws-writers&amp;orderby=userid&amp;order=asc"><span>User ID</span><span class="sorting-indicator"></span></a></th>
        <th width="211" class="manage-column column-categories" id="categories" style="" scope="col">User Login</th>
        <th width="158" class="manage-column column-type" id="email" style="" scope="col">Email</th>
        <th width="137" class="manage-column column-length" id="date" style="" scope="col">Date Registered</th>
        <th width="169" class="manage-column column-quantity" id="ordersCompleted" style="" scope="col">Orders Completed</th>
        <th width="289" class="manage-column column-status" id="orders" style="" scope="col">Orders</th>	
        
    </tr>
	</thead>

	<tbody id="the-list">
    
    <?php
	
		$writersA = get_users('role=wpws_writer');

		$admin_url = admin_url();

		foreach( $writersA as $writer ):	
		
		$userID = $writer->ID;
		
		$editURL =  $admin_url . "user-edit.php?user_id={$userID}";

		$taskA = WPWS_Model::getTasks( $userID, $status = NULL ); //returns as an object
		
		$countCompleted = WPWS_Model::getTasks( $userID, $status = 'completed', ARRAY_A ); //returns as an object
		$countCompleted = count( $countCompleted );
		
		
		
		
	?>
				<tr id="task-<?php echo $taskID;?>" class="task-<?php echo $taskID;?> <?php echo $statusStyle;?>" valign="top">
                <td class="post-title page-title column-orderid"><strong><?php echo $userID;?></strong>
                <div class="locked-info"><span class="locked-avatar"></span> <span class="locked-text"></span></div>
                <div class="row-actions"></div>
                <div class="hidden" id="inline_<?php echo $userID;?>"></div>
				  </td>
					  <td class="categories"><a href="<?php echo $editURL;?>" target="_new"><?php echo $writer->user_login;?></a></td>	
                      <td class="author column-author"><?php echo $writer->user_email;?></td>
						<td class="orderID column-registered"><?php echo $writer->user_registered;?></td>
                        <td class="orderID column-quantity"><?php echo $countCompleted;?></td>
						<td class="due_date column-status">
						<select name="wpws_order_status" mclass="wpws_order_status" size="4" style="height:90px; width:100px;" disabled="disabled">
                        <?php
							foreach( $taskA as $taskO ):
						?>
                           <option value="" <?php if( $taskO->status == 'completed' ) echo 'selected';?> >Order ID: <?php echo $taskO->orderID;?></option>
                        <?php
							endforeach;
						?>
                     </select>
                                        
                        
                        </td>
					</tr>
                    

                    
       <?php
	   	endforeach;
		?>             
                    
	</tbody>
</table>
        
        
  </div>
        
        
        
        
        
        
        
        
        
        
        
    <?php    
	}
	
	public function wpws_menu_orders()
	{
		
		global $current_user;

	?>
    <div class="wrap">
    
      <!-- Display confirmation message -->
		<?php if ( isset ( $_GET['updated'] ) && $_GET['updated'] ) { ?>
        
    		<div id="message" class="updated"> Settings saved!</div>
            
		<?php } else if(isset ( $_GET['errorMsg'] ) && $_GET['errorMsg'] ){ 
					$msgID = intval($_GET['errorMsg']);
					
							
			?> 
				<div id="message" class="error"> <?php echo WPWS_Menu::$errorMessages[ $msgID ];?></div>
		<?php	} ?>
    
	  <h2>Orders</h2>	
<p>Here is where you can see the current Orders made by clients/customers. If you wish to claim a project/Order, then select it and hit 'Claim'. Likewise, to unclaim an order, select it and hit 'Unclaim.'<br />
  Note that if an Order ID is already claimed by someone else, you may not claim it. Only the admin has the access to claim/unclaim any Order ID.
</p>


<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">

	<!-- to determine which action to process -->
    <input type="hidden" name="action" value="save_settings_orders" />
	  
	  <!-- Adding security through hidden referrer field -->
	  <?php wp_nonce_field( 'wpws_settings_orders' ); ?>
<table cellspacing="0" class="wp-list-table widefat pages" style="margin: 5px;">
	<thead>
	<tr>
		<th width="127" class="manage-column column-cb check-column" id="cb" style="" scope="col"><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox"></th>
        <th width="67" class="manage-column column-orderid sortable desc" id="title" style="" scope="col"><a href="http://localhost/wordpress/wp-admin/admin.php?page=wpws-orders&amp;orderby=orderid&amp;order=asc"><span>Order ID</span><span class="sorting-indicator"></span></a></th>
        <th width="73" class="manage-column column-date sortable asc" id="date" style="" scope="col">Claimed By</th>
        
        <th width="33" class="manage-column column-date sortable asc" id="date" style="" scope="col"><a href="http://localhost/wordpress/wp-admin/admin.php?page=wpws-orders&amp;orderby=date&amp;order=desc"><span>Date</span><span class="sorting-indicator"></span></a></th>
		<th width="73" class="manage-column column-categories" id="categories" style="" scope="col">Categories</th>
        <th width="34" class="manage-column column-type" id="author" style="" scope="col">Type</th>
        <th width="47" class="manage-column column-length" id="orderID" style="" scope="col">Length</th>
        <th width="58" class="manage-column column-quantity" id="orderID" style="" scope="col">Quantity</th>
        <th width="40" class="manage-column column-client" id="length" style="" scope="col">Client</th>
        <th width="107" class="manage-column column-notes" id="notes" style="" scope="col">Notes</th>
        <th width="99" class="manage-column column-status" id="status" style="" scope="col">Status</th>	
        
    </tr>
	</thead>

	<tbody id="the-list">
    
    <?php
	
		$orderA = WPWS_Model::getOrders(); //returns as an object
			
		//current logged user
		$userLogin = $current_user->user_login;

				
		foreach( $orderA as $order ):	
		
		$orderID = $order->orderID;
		$userIDProject = WPWS_Model::getUserByOrderID( $orderID ); 
		$orderDate = date('Y/m/d', strtotime($order->date));
		$userID = $order->userID;
		$userO = get_user_by( 'id', $userID );
		
		if( $userIDProject ){
			$userOProject = get_user_by( 'id', $userIDProject);
			$userProject = $userOProject->user_login;
		} else{
			$userProject = NULL;	
		}
		
		
		$quantity = $order->quantity;
		$categoryO = get_term( $order->term_id_category, 'category' ); //term_id, taxonomy; returns a category object
		
		//Type, Length, Status
		$orderData = WPWS_Model::getDataOrderID( $orderID ); //returns an object
		
		$term_id_type = $orderData->term_id_type;
		$term_id_length = $orderData->term_id_length;
		
		$type = WPWS_Model::getTermByTermID( $term_id_type );
		$length = WPWS_Model::getTermByTermID( $term_id_length );

        //Display the status of an order ID
		 $statusA = array(
         	'completed' => 'Completed',
            'inprogress' => 'In-progress',
            'cancelled' => 'Cancelled'
         );            
          
		 if( !empty( $orderData->status )){
			 
			 $wpws_order_status = $statusA[$orderData->status];
		 } else{
			$wpws_order_status =  NULL; 
		 }
		 
		 //Paid?
		 $wpws_paid = !empty( $order->paid ) ? 1 : 0;
		 $wpws_sentInvoice = !empty( $order->sentInvoice ) ? 1 : 0;
		 
			         
		
		//Check if Order ID is already claimed by another user
		//That is, if an Order ID is claim and the current login doesnt match the login of the claimed user
		//Exception is for Admin - they have access control over everything
		$isClaimByOther = false;
		
		if( !empty( $userProject ) && $userProject != $userLogin ){
			
			if( !current_user_can( 'assign_writers' )){
			$isClaimByOther = true;
			}
		}
		
	?>
					<tr id="order-<?php echo $orderID;?>" class="order-<?php echo $orderID;?> type-wpws_article alternate iedit author-self level-0" valign="top">
				<th scope="row" class="check-column">
								<label class="screen-reader-text" for="cb-select-<?php echo $orderID;?>">Select OrderID</label>
				<input id="cb-select-<?php echo $orderID;?>" name="orderIDA[]" value="<?php echo $orderID;?>" type="checkbox" <?php if( $isClaimByOther ) echo 'disabled';?>>
				<div class="locked-indicator"></div>
					  </th>
						<td class="post-title page-title column-orderid"><strong><?php echo $orderID;?></strong>
                <div class="locked-info"><span class="locked-avatar"></span> <span class="locked-text"></span></div>
                <div class="row-actions"></div>
                <div class="hidden" id="inline_<?php echo $orderID;?>"></div>
						</td>
						<td class="date column-claimed">&nbsp;<em><?php echo $userProject;?></em></td>
                      <td class="date column-date"><abbr title="<?php echo $orderDate;?>"><?php echo $orderDate;?></abbr></td>		
                      <td class="categories column-categories"><a href="edit.php?post_type=wpws_article&amp;category_name=<?php echo $categoryO->slug;?>"><?php echo $categoryO->name;?></a></td>	
                      <td class="author column-author"><?php echo $type;?></td>
						<td class="orderID column-length"><?php echo $length;?></td>
                        <td class="orderID column-quantity"><?php echo $quantity;?></td>
						<td class="userID column-userID"><a href="mailto:<?php echo $userO->user_email;?>"><?php echo $userO->user_login;?></a></td>
						<td class="status column-notes">
                        <textarea cols="20" rows="1" readonly="readonly"><?php echo stripslashes_deep( $order->notes );?></textarea>
                        
                        </td>
						<td class="due_date column-status"><?php echo $wpws_order_status;?></td>
					</tr>
       <?php
	   	endforeach;
		?>             
                    
		</tbody>
</table>
<input type="submit" name="orderClaiming" value="Claim" class="button-primary"/>&nbsp;&nbsp;
<input type="submit" name="orderClaiming" value="UnClaim" class="button-primary"/>
</form>
        
</div>    
        
        
        
        
        
    <?php    
	}
	
	
	public function wpws_menu_tasks()
	{
		global $current_user;
		
	?>

 <div class="wrap">
    
      <!-- Display confirmation message -->
		<?php if ( isset ( $_GET['updated'] ) && $_GET['updated'] ) { ?>
        
    		<div id="message" class="updated"> Settings saved!</div>
            
		<?php } else if(isset ( $_GET['errorMsg'] ) && $_GET['errorMsg'] ){ 
					$msgID = intval($_GET['errorMsg']);
					
							
			?> 
				<div id="message" class="error"> <?php echo WPWS_Menu::$errorMessages[ $msgID ];?></div>
		<?php	} ?>
    
	  <h2>My Tasks</h2>	
<p>Here is where you can see the current Orders you have claimed to work on.<br />
From an Order/Project level, you can set the overall status of it. That is, if you are required to write two articles and only one is required for completion, then you could mark
the overall project/Order ID as completed.
</p>


<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">

	<!-- to determine which action to process -->
    <input type="hidden" name="action" value="save_settings_tasks" />
	  
	  <!-- Adding security through hidden referrer field -->
	  <?php wp_nonce_field( 'wpws_settings_tasks' ); ?>
      
      <span style="display:inline; width: 100px;  padding: 2px; border:1px solid #000;cursor:pointer;" onClick="document.location.href='admin.php?page=wpws-tasks&status=misc';">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;
      <em><strong>Not Assigned/Misc</strong></em>&nbsp;&nbsp;&nbsp;<span style="display:inline; width: 100px;  padding: 2px; ;cursor:pointer;" class="inprogressStatus" onClick="document.location.href='admin.php?page=wpws-tasks&status=inprogress';">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
           &nbsp;<em><strong>In Progress</strong></em>&nbsp;&nbsp;&nbsp;<span style="display:inline; width: 100px;  padding: 2px; cursor:pointer;" class="cancelledStatus" onClick="document.location.href='admin.php?page=wpws-tasks&status=cancelled';">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;<em><strong>Cancelled</strong></em>&nbsp;&nbsp;<span style="display:inline; width: 100px;  padding: 2px; cursor:pointer;"  class="completedStatus" onClick="document.location.href='admin.php?page=wpws-tasks&status=completed';">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;&nbsp;&nbsp;<em><strong>Completed</strong></em>
           &nbsp;&nbsp;&nbsp;<a href="admin.php?page=wpws-tasks">[View All Tasks]</a>
<table cellspacing="0" class="wp-list-table widefat  pages" style="margin: 5px;">
  <thead>
	<tr>
    	<th width="133" class="manage-column column-taskid sortable desc" id="title" style="" scope="col"><a href="wp-admin/admin.php?page=wpws-tasks&amp;orderby=taskid&amp;order=asc"><span>Task ID</span><span class="sorting-indicator"></span></a></th>
		<th width="133" class="manage-column column-orderid sortable desc" id="title" style="" scope="col"><a href="wp-admin/admin.php?page=wpws-tasks&amp;orderby=orderid&amp;order=asc"><span>Order ID</span><span class="sorting-indicator"></span></a></th>
        <th width="211" class="manage-column column-categories" id="categories" style="" scope="col">Categories</th>
        <th width="158" class="manage-column column-type" id="author" style="" scope="col">Type</th>
        <th width="137" class="manage-column column-length" id="orderID" style="" scope="col">Length</th>
        <th width="169" class="manage-column column-quantity" id="orderID" style="" scope="col">Quantity</th>
        <th width="117" class="manage-column column-client" id="length" style="" scope="col">Client</th>
        <th width="394" class="manage-column column-notes" id="status" style="" scope="col">Notes</th>
        <th width="289" class="manage-column column-status" id="due_date" style="" scope="col">Status</th>	
        
    </tr>
	</thead>

	<tbody id="the-list">
    
    <?php
	
		//current logged user
		$userLogin = $current_user->user_login;
		$userID = $current_user->ID;
		
		
		$status = !empty( $_GET['status'] ) ? $_GET['status']  : NULL;

		$taskA = WPWS_Model::getTasks( $userID, $status ); //returns as an object


		foreach( $taskA as $order ):	
		
		//reset status style color
		$statusStyle = NULL;
		
		$taskID = $order->taskID;
		$orderID = $order->orderID;
		
		$clientID = $order->clientID;
		$clientO = get_user_by( 'id', $clientID );
		
		$status = $order->status;
		
			//Get color for status
			switch( $status )
			{
				case 'completed':
					$statusStyle = 'completedStatus';
				break;	
				
				case 'inprogress':
					$statusStyle = 'inprogressStatus';
				break;	
				
				case 'cancelled':
					$statusStyle = 'cancelledStatus';
				break;
				
			}
		
		$quantity = $order->quantity;
		$categoryO = get_term( $order->term_id_category, 'category' ); //term_id, taxonomy; returns a category object
		
		//Type and Length
		$orderData = WPWS_Model::getDataOrderID( $orderID ); //returns an object
		$term_id_type = $orderData->term_id_type;
		$term_id_length = $orderData->term_id_length;
		
		$type = WPWS_Model::getTermByTermID( $term_id_type );
		$length = WPWS_Model::getTermByTermID( $term_id_length );
		
		$wpws_order_status = NULL;
		
		
	?>
				<tr id="task-<?php echo $taskID;?>" class="task-<?php echo $taskID;?> <?php echo $statusStyle;?>" valign="top">
                <td class="post-title page-title column-taskid"><strong><?php echo $taskID;?></strong>
				<td class="post-title page-title column-orderid"><span style="font-weight:bold; text-decoration:underline;"><?php echo $orderID;?></span> <div class="hidden" id="inline_<?php echo $taskID;?>"></div>
				  </td>
					  <td class="categories column-categories"><a href="edit.php?post_type=wpws_article&amp;category_name=<?php echo $categoryO->slug;?>"><?php echo $categoryO->name;?></a></td>	
                      <td class="author column-author"><?php echo $type;?></td>
						<td class="orderID column-length"><?php echo $length;?></td>
                        <td class="orderID column-quantity"><?php echo $quantity;?></td>
						<td class="userID column-clientID"><?php echo $clientO->user_login;?></td>
						<td class="status column-notes">
                        <textarea cols="20" rows="1" readonly="readonly"><?php echo stripslashes_deep( $order->notes );?></textarea>
                        
                        </td>
						<td class="due_date column-status">
						<select name="wpws_order_status" id="<?php echo $taskID;?>" class="wpws_order_status">
                           <option value="">-----</option>
                           <option value="completed" <?php selected($status, 'completed');?>>Completed</option>
                           <option value="inprogress" <?php selected($status, 'inprogress');?>>In-progress</option>
                           <option value="cancelled" <?php selected($status, 'cancelled');?>>Cancelled</option>
                     </select>
                                        
                        
                        </td>
					</tr>
                    
                    <tr>
                    
                    	<td colspan="10">
                        <div class="accordian">
                        	<h4 style="padding-left: 30px;">View Articles/Projects (<span style="font-style:italic; font-weight:bold;">Order ID: <?php echo $orderID;?></span>)</h4>
                            <div>
                            
                            	<?php
									$postIDA = WPWS_Model::getPostIDByOrderID( $orderID );
									//Loop through all postIDs
									if( count( $postIDA )){
										$count = 1;
										foreach( $postIDA as $postIDO ):
										
										$post_id = $postIDO->post_id;
										
										//Options
										$wpws_duedate = get_post_meta( $post_id, 'wpws_meta_duedate', true );
										$wpws_status = get_post_meta( $post_id, 'wpws_meta_status', true );
	
										$postO = get_post( $post_id ); 
										
										$title = $postO->post_title;
										
												//Title
											echo "{$count}) <a href=".admin_url() . "post.php?post={$post_id}&action=edit>" .$title."<a/>";
											echo " <strong><em>( Post ID: {$post_id}, Due Date: {$wpws_duedate}, Status: {$wpws_status} )</em></strong> <br />";
	
										$count++;
										endforeach;
	
											
									} else{
										echo "<b>No Article yet - click 'Add New Article' for Order ID # {$orderID}</b><br /><br />\n";
									}
									
								?>
                            
                            <p><a href="post-new.php?post_type=wpws_article">>> Add New Article </a></p>
                            
                            </div>

</div>
                        </td>
                    </tr>
                    
       <?php
	   	endforeach;
		?>             
                    
		</tbody>
</table>
</form>
        
</div>    
        

        
    <?php    
	}
	
	public function wpws_menu_client()
	{
		//Determine which menu to show
		if( current_user_can( 'request_articles' ) && current_user_can( 'assign_writers' ) ){ //admin power
					
					WPWS_Menu::wpws_menu_admin_clientOut();
			} else {
					WPWS_Menu::wpws_menu_client_clientOut();
		}
	}
	
	//Admin version of Clients menu
	public function wpws_menu_admin_clientOut()
	{
	?>
<div class="wrap">
  <h2>Clients</h2>	
        
        
  <table cellspacing="0" class="wp-list-table widefat  pages" style="margin: 5px;">
  <thead>
	<tr>
    	<th width="133" class="manage-column column-orderid sortable desc" id="title" style="" scope="col"><a href="wp-admin/admin.php?page=wpws-client&amp;orderby=userid&amp;order=asc"><span>User ID</span><span class="sorting-indicator"></span></a></th>
        <th width="211" class="manage-column column-categories" id="categories" style="" scope="col">User Login</th>
        <th width="158" class="manage-column column-type" id="email" style="" scope="col">Name</th>
        <th width="158" class="manage-column column-type" id="email" style="" scope="col">Email</th>
        <th width="137" class="manage-column column-length" id="date" style="" scope="col">Date Registered</th>
        <th width="289" class="manage-column column-status" id="orders" style="" scope="col">Orders</th>	
        
    </tr>
	</thead>

	<tbody id="the-list">
    
    <?php
	
		$clientA = get_users('role=wpws_client');
		
		$admin_url = admin_url();

		foreach( $clientA as $client ):	
		
		$userID = $client->ID;
		$userData = get_userdata( $userID );
		
		$userName = $userData->first_name . " " . $userData->last_name;
		
		$editURL =  $admin_url . "user-edit.php?user_id={$userID}";

		$orderA = WPWS_Model::getOrders( $userID ); //returns as an object

		
	?>
				<tr id="task-<?php echo $taskID;?>" class="task-<?php echo $taskID;?> <?php echo $statusStyle;?>" valign="top">
                <td class="post-title page-title column-orderid"><strong><?php echo $userID;?></strong>
                <div class="locked-info"><span class="locked-avatar"></span> <span class="locked-text"></span></div>
                <div class="row-actions"></div>
                <div class="hidden" id="inline_<?php echo $userID;?>"></div>
				  </td>
					  <td class="categories"><a href="<?php echo $editURL;?>" target="_new"><?php echo $client->user_login;?></a></td>
					  <td class="author column-name"><?php echo $userName;?></td>	
                      <td class="author column-email"><?php echo $client->user_email;?></td>
						<td class="orderID column-registered"><?php echo $client->user_registered;?></td>
                        <td class="due_date column-status">
						<select name="wpws_order_status" mclass="wpws_order_status" size="4" style="height:90px; width:100px;" disabled="disabled">
                        <?php
							foreach( $orderA as $orderO ):
						?>
                           <option value="" >Order ID: <?php echo $orderO->orderID;?></option>
                        <?php
							endforeach;
						?>
                     </select>
                                        
                        
                        </td>
					</tr>
                    

                    
       <?php
	   	endforeach;
		?>             
                    
	</tbody>
</table>
        
        
  </div>
     <?php    
	}
	
	//Client version of Client menu
	public function wpws_menu_client_clientOut()
	{
	?>
<div class="wrap">
    
    
	  <h2>My Orders</h2>	
<p>Here is your current order and the status. 
</p>

<table cellspacing="0" class="wp-list-table widefat pages" style="margin: 5px;">
	<thead>
	<tr>
		<th width="67" class="manage-column column-orderid sortable desc" id="title" style="" scope="col"><a href="http://localhost/wordpress/wp-admin/admin.php?page=wpws-orders&amp;orderby=orderid&amp;order=asc"><span>Order ID</span><span class="sorting-indicator"></span></a></th>
        <th width="33" class="manage-column column-date sortable asc" id="date" style="" scope="col"><a href="http://localhost/wordpress/wp-admin/admin.php?page=wpws-orders&amp;orderby=date&amp;order=desc"><span>Date</span><span class="sorting-indicator"></span></a></th>
		<th width="73" class="manage-column column-categories" id="categories" style="" scope="col">Categories</th>
        <th width="34" class="manage-column column-type" id="type" style="" scope="col">Type</th>
        <th width="47" class="manage-column column-length" id="length" style="" scope="col">Length</th>
        <th width="58" class="manage-column column-quantity" id="quantity" style="" scope="col">Quantity</th>
        <th width="107" class="manage-column column-notes" id="notes" style="" scope="col">Notes</th>
        <th width="99" class="manage-column column-paid" id="paid" style="" scope="col">Paid</th>
        <th width="99" class="manage-column column-invoice" id="invoice" style="" scope="col">Invoice Sent?</th>
        <th width="99" class="manage-column column-status" id="status" style="" scope="col">Status</th>	
        
    </tr>
	</thead>

	<tbody id="the-list">
    
    <?php
	
	
			
		//current logged user
		global $current_user;
		
		$orderA = WPWS_Model::getOrders( $current_user->ID ); //returns as an object
				
		foreach( $orderA as $order ):	
		
		$orderID = $order->orderID;
		$userIDProject = WPWS_Model::getUserByOrderID( $orderID ); 
		$orderDate = date('Y/m/d', strtotime($order->date));
		$userID = $order->userID;
		$userO = get_user_by( 'id', $userID );
		
		if( $userIDProject ){
			$userOProject = get_user_by( 'id', $userIDProject);
			$userProject = $userOProject->user_login;
		} else{
			$userProject = NULL;	
		}
		
		
		$quantity = $order->quantity;
		$categoryO = get_term( $order->term_id_category, 'category' ); //term_id, taxonomy; returns a category object
		
		//Type, Length, Status
		$orderData = WPWS_Model::getDataOrderID( $orderID ); //returns an object
		
		$term_id_type = $orderData->term_id_type;
		$term_id_length = $orderData->term_id_length;
		
		$type = WPWS_Model::getTermByTermID( $term_id_type );
		$length = WPWS_Model::getTermByTermID( $term_id_length );

        //Display the status of an order ID
		 $statusA = array(
         	'completed' => 'Completed',
            'inprogress' => 'In-progress',
            'cancelled' => 'Cancelled'
         );            
          
		  //Paid?
		 $wpws_paid = !empty( $order->paid ) ? 'Yes' : 'No';
		 $wpws_sentInvoice = !empty( $order->sentInvoice ) ? 'Yes' : 'No';
		  
		 if( !empty( $orderData->status )){
			 
			 $wpws_order_status = $statusA[$orderData->status];
		 } else{
			$wpws_order_status =  NULL; 
		 }
			         
		
		
	?>
					<tr id="order-<?php echo $orderID;?>" class="order-<?php echo $orderID;?> type-wpws_article alternate iedit author-self level-0" valign="top">
				<td class="post-title page-title column-orderid"><strong><?php echo $orderID;?></strong>
                <div class="locked-info"><span class="locked-avatar"></span> <span class="locked-text"></span></div>
                <div class="row-actions"></div>
                <div class="hidden" id="inline_<?php echo $orderID;?>"></div>
				  </td>
					  <td class="date column-date"><abbr title="<?php echo $orderDate;?>"><?php echo $orderDate;?></abbr></td>		
                      <td class="categories column-categories"><a href="edit.php?post_type=wpws_article&amp;category_name=<?php echo $categoryO->slug;?>"><?php echo $categoryO->name;?></a></td>	
                      <td class="author column-author"><?php echo $type;?></td>
						<td class="orderID column-length"><?php echo $length;?></td>
                        <td class="orderID column-quantity"><?php echo $quantity;?></td>
						<td class="status column-notes">
                        <textarea cols="20" rows="1" readonly="readonly"><?php echo stripslashes_deep( $order->notes );?></textarea>
                        
                        </td>
							<td class="due_date column-paid"><?php echo $wpws_paid;?></td>
						<td class="due_date column-invoice">&nbsp;<?php echo $wpws_sentInvoice;?></td>
						<td class="due_date column-status"><?php echo $wpws_order_status;?></td>
					</tr>
       <?php
	   	endforeach;
		?>             
                    
		</tbody>
</table>
        
</div>    
     <?php

	}
	
	
	public function wpws_menu_reports()
	{
	?>
		<div class="wrap">
        <h2>Reports</h2>	
        
        	Coming soon...
        </div>
        
        
	  <?php    
	}
	
	//meta box for plugin
	public function wpws_meta_box_orderID( $post )
	{
	global $current_user;	
		
		$wpws_meta_orderID = get_post_meta( $post->ID, 'wpws_meta_orderID', true );
		
		
		$taskA = WPWS_Model::getOrders();
	
		
		
		//Get the Order IDs
		foreach( $taskA  as $orderA ){
			$orderIDA[] = $orderA->orderID;
		}
		
	
	?>
    <select name="wpws_meta_orderID" id="wpws_meta_orderID" size="4" style="height:90px;">
    	<?php foreach( $orderIDA as $orderID ):?>
     	<option value="<?php echo $orderID;?>" <?php selected( $wpws_meta_orderID, $orderID);?>>Order ID: <?php echo $orderID;?></option>
        <?php endforeach;?>
	</select>

<p id="orderInfo"></p>


  <?php
	}	
	
	
	public function wpws_meta_box_status( $post )
	{
		// Add an nonce field so we can check for it later.
		wp_nonce_field('wpws_meta_process', 'wpws_metabox_nounce' );
		$wpws_meta_status = get_post_meta( $post->ID, 'wpws_meta_status', true );
	
	?>
      
     <select name="wpws_meta_status">
       <option value="awaiting" <?php selected($wpws_meta_status, 'awaiting');?>>Awaiting</option>
       <option value="inprogress" <?php selected($wpws_meta_status, 'inprogress');?>>In Progess</option>
       <option value="completed" <?php selected($wpws_meta_status, 'completed');?>>Completed</option>
       <option value="cancelled" <?php selected($wpws_meta_status, 'cancelled');?>>Cancelled</option>
     </select>
      
      <?php
	}	
	
	public function wpws_meta_box_duedate( $post )
	{
		//wp_nonce_field( 'wpws_meta_box_duedate' );
	wp_nonce_field('wpws_meta_process', 'wpws_metabox_nounce' );
		$wpws_meta_duedate = get_post_meta( $post->ID, 'wpws_meta_duedate', true );
	
	?>
      
<input name="wpws_meta_duedate" id="wpws_meta_duedate" type="text" size="25" value="<?php echo $wpws_meta_duedate;?>" />
        
<?php
	}	
	
	public function wpws_meta_box_notes( $post )
	{
		//wp_nonce_field( 'wpws_meta_box_notes' );
		wp_nonce_field('wpws_meta_process', 'wpws_metabox_nounce' );
		$wpws_meta_notes = get_post_meta( $post->ID, 'wpws_meta_notes', true );
		$wpws_meta_notes = esc_attr( $wpws_meta_notes );
	//parent::pr( $post );
	
	?>
      
      <textarea name="wpws_meta_notes" cols="50" rows="4"><?php echo $wpws_meta_notes;?></textarea>
        
<?php
	}	
	
}    
