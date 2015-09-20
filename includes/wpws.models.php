<?php
class WPWS_Model extends WPWS //MODEL
{
	public function __construct()
	{
		
	}
	
	//Table:wpws_taxonomy
	//$data: 'taxonomy
	public function getTaxonomyID( $taxonomy )
	{
	global $wpdb;
	
	$table = $wpdb->prefix."wpws_taxonomy";
	
	$query = "
			SELECT taxonomy_id
			FROM {$table}
			WHERE  taxonomy = %s
		";
		$prepared = $wpdb->prepare( $query, $taxonomy );
		
		$taxonomy_id = $wpdb->get_var( $prepared );
	
		return $taxonomy_id;
		
	}
	
	//Table:wpws_taxonomy
	//$data: taxonomy array
	//Insert as an array
	public function addTaxonomy( $data )
	{
	global $wpdb;
	
	$table = $wpdb->prefix."wpws_taxonomy";
	
		foreach( $data as $taxonomy){
			$data = array( 'taxonomy' => $taxonomy);
			
			$taxonomy_id = $wpdb->insert( $table,  $data );
		}
		
	}
	
	//Table:wpws_taxonomy_term
	//taxonomy_id and term
	//Insert one item at a time
	//Return: term_id
	public function addTerm ( $taxonomy_id, $term )
	{
	global $wpdb;
	
	$table = $wpdb->prefix."wpws_taxonomy_term";

			$data = array( 'taxonomy_id' => $taxonomy_id,'term' => $term );
			
			$term_id = $wpdb->insert( $table, $data );
			return $term_id;
	}
	
	//Table:wpws_taxonomy_term
	//taxonomy_id 
	//Return: terms as an array
	public function getTerms ( $taxonomy_id )
	{
	global $wpdb;
	
	$table = $wpdb->prefix."wpws_taxonomy_term";

		$query = "
			SELECT * 
			FROM {$table}
			WHERE taxonomy_id = %d
		";
		
		$prepared = $wpdb->prepare( $query, $taxonomy_id );
		$termA = $wpdb->get_results( $prepared ); //return as an object
		
		return $termA;
	}
	
	
	//Given a term ID for the Article Type and Length taxonomies, it will return its Product ID
	//If nothing exists, it will return false
	//Table: wpws_product
	public function getProductIDByTerm( $term_id_type = 0, $term_id_length  = 0)
	{
	global $wpdb;
	$table = $wpdb->prefix."wpws_product";
		
		$query = "
			SELECT productID 
			FROM {$table}
			WHERE  term_id_type = %d AND term_id_length = %d
		";
		$prepared = $wpdb->prepare( $query, $term_id_type, $term_id_length );
		
		$productID = $wpdb->get_var( $prepared );
		
		return $productID;
	
	}	
	
	//Retrieve terms based on productID
	//Table: wpws_product
	public function getTermsByProductID( $productID )
	{
	global $wpdb;
	$table = $wpdb->prefix."wpws_product";
	
		$query = "
			SELECT term_id_type, term_id_length
			FROM {$table}
			WHERE  productID = %d
		";
		$prepared = $wpdb->prepare( $query, $productID );
		
		$terms = $wpdb->get_row( $prepared, ARRAY_A  );
	
		
		return $terms;
			
		
	}
	
	//Retrieve term based on term ID
	//Table: wpws_taxonomy_term
	public function getTermByTermID( $term_id )
	{
	global $wpdb;
	$table = $wpdb->prefix."wpws_taxonomy_term";
	
		$query = "
			SELECT term
			FROM {$table}
			WHERE  term_id = %d
		";
		$prepared = $wpdb->prepare( $query, $term_id );
		
		$term = $wpdb->get_var( $prepared );
	
		
		return $term;
			
		
	}
	
	
	//Table: wpws_product_order
	public function addOrder( $data )
	{
	global $wpdb;
	
	$table = $wpdb->prefix."wpws_product_order";
	
		$wpdb->insert( $table, $data );
		
		return $wpdb->insert_id;
		
	}
	
	//Table: wpws_product
	public function addProduct( $data )
	{
	global $wpdb;
	
	$table = $wpdb->prefix."wpws_product";
	
	$term_id_type = $data['wpws_product_type'];
	$term_id_length = $data['wpws_product_length'];
	
	$dataA = array();
	
	$dataA = array(
		'term_id_type' => $term_id_type,
		'term_id_length' => $term_id_length,
	);

	
		$productID = $wpdb->insert( $table, $dataA );
		
		return $productID;
		
	}
	
	//Table: wpws_product
	public function updatePriceProduct( $data )
	{
	global $wpdb;
	
	$table = $wpdb->prefix."wpws_product";
	
	$productID = $data['productID'];
	$price = $data['price'];

		$wpdb->update( $table, array('price'=>$price), array( 'productID'=>$productID), array( '%f' ), array( '%d' ) );
		
	}
	
	//Table: wpws_product
	//Return: true if successfully deleted
	public function delProduct( $productID )
	{
	global $wpdb;
	
	$table = $wpdb->prefix."wpws_product";
	
		if( $wpdb->delete( $table, array( 'productID' => $productID ), array( '%d' ) )){
			return true;	
		}
		return false;
		
	}
	
	//Will retrieve all data from the product table
	//Table: wpws_product
	public function getProducts()
	{
	global $wpdb;
	
	$table = $wpdb->prefix."wpws_product";

		$query = "
			SELECT * 
			FROM {$table}
		";
		
		$productA = $wpdb->get_results( $query ); //return as an object
		
		return $productA;
		
	}
	
	//Will retrieve all data from the order table
	//If userID is given, then retrieve only those orders within a UserID
	//Table: wpws_product_order
	public function getOrders( $userID = NULL )
	{
	global $wpdb;
	
	$table = $wpdb->prefix."wpws_product_order";

		$query = "
			SELECT * 
			FROM {$table}
			";
		
		if( $userID ){
			$query .= "
				WHERE userID = %d
			";
		}
			
		$query .="
			ORDER BY orderID DESC
		";
		
		if( $userID ){
		$prepared = $wpdb->prepare( $query, $userID );
		}else{
		$prepared = $query ;	
		}
		
		
		$orderA = $wpdb->get_results( $prepared ); //return as an object
		
		return $orderA;
		
	}
	
	//Table: wpws_writer_project
	public function updateOrderProject( $data, $isClaim )
	{
	global $wpdb;
	
	$table = $wpdb->prefix."wpws_writer_task";
	
	$userID = $data['userID'];
	$orderIDA = $data['orderIDA'];
	

		if( $isClaim == 'claim' ){ //CLAIM
			
			foreach( $orderIDA  as $orderID ){
			$orderID = intval( $orderID );	
				
				$dataA = array(
				'userID'=>$userID,
				'orderID'=>$orderID
				);
					
					$query = "
						SELECT orderID
						FROM {$table}
						WHERE orderID = {$orderID}
					";
				
				if(! $wpdb->get_var( $query )){ //does the Order ID already exists in the system? If not, insert it
				
					
					$wpdb->insert( $table, $dataA ); 
	
					
				} else{ //update it if it exists ONLY FOR ADMIN
									//is user an admin? (admin has the power to override all claims
					if( current_user_can( 'assign_writers' ) ){
						$wpdb->update( $table, $dataA, array( 'orderID' => $orderID) ); //"Replace a row in a table if it exists or insert a new row in a table if the row did not already exist."	
					}
				}

			}
		} else{ //UNCLAIM
			foreach( $orderIDA  as $orderID ){
				$wpdb->delete( $table, array('orderID'=>$orderID), array( '%d' ) );
			}	
		}
		

		
	}
	
	
	//Table: wpws_product_order, wpws_product
	public function getDataOrderID( $orderID )
	{
	global $wpdb;
	
	$tableProduct = $wpdb->prefix."wpws_product";
	$tableProductOrder = $wpdb->prefix."wpws_product_order";
	$tableWriterTask = $wpdb->prefix."wpws_writer_task";
	
		$query = "
			SELECT po.orderID, po.productID, p.price, p.term_id_type, p.term_id_length,  po.term_id_category, po.quantity, po.notes, wt.status
			FROM {$tableProductOrder} po
			INNER JOIN {$tableProduct} p ON po.productID = p.productID
			LEFT JOIN {$tableWriterTask} wt ON wt.orderID = po.orderID
			WHERE po.orderID = %d

		";
		

		$prepared = $wpdb->prepare( $query, $orderID );
		
		$orderData = $wpdb->get_row( $prepared );
		
		return $orderData;
	
	}
	
	//Table: wpws_writer_task
	public function getUserByOrderID( $orderID )
	{
	global $wpdb;
	
	$table = $wpdb->prefix."wpws_writer_task";
	
	
		$query = "
			SELECT userID
			FROM {$table}
			WHERE  orderID = %d

		";
		$prepared = $wpdb->prepare( $query, $orderID );
		
		$userID = $wpdb->get_var( $prepared );
		
		return $userID;
	
	}
	
	//Table: wp_postmeta
	public function getPostIDByOrderID( $orderID )
	{
	global $wpdb;
	
	$table = "wp_postmeta";
	
	
		$query = "
			SELECT post_id
			FROM {$table}
			WHERE meta_key = 'wpws_meta_orderID'
			AND meta_value = %d

		";

		$prepared = $wpdb->prepare( $query, $orderID );
		
		$postIDA = $wpdb->get_results( $prepared );
		
		return $postIDA;
	
	}
	
	//Will retrieve all data from the task table by on userID
	//Table: wpws_writer_task
	public function getTasks( $userID, $status = false, $outputType = OBJECT )
	{
	global $wpdb;

	$tableWriterTask = $wpdb->prefix."wpws_writer_task";
	$tableProductOrder = $wpdb->prefix."wpws_product_order";
	
	$status = esc_sql( $status ); //esc_sql since the data comes from a GET URL request
	
	

		$query = "
			SELECT wt.taskID, wt.status, wt.userID,  po.userID as clientID, wt.orderID, po.quantity, po.productID, po.term_id_category, po.notes
			FROM {$tableWriterTask} wt
			INNER JOIN {$tableProductOrder}  po ON wt.orderID = po.orderID
			WHERE wt.userID = %d
			";
			
			if( $status ){
				
				//If status is MISC then select all the ones that are not assigned to a status yet
				if( $status == 'misc' ){
					$query .="
					AND (wt.status = '' OR wt.status IS NULL)
					";
				} else{
				
				$query .="
				AND wt.status = %s
				";
				}
			}
			
			$query .= "	
			ORDER BY wt.taskID DESC
		";
		
		if(!$status){
		$prepared = $wpdb->prepare( $query, $userID);
		} else{
		$prepared = $wpdb->prepare( $query, $userID, $status );
		}

		$taskA = $wpdb->get_results( $prepared, $outputType ); //return as an object
		
		return $taskA;
		
	}
	
	//Table: wpws_writer_task
	public function updateOrderStatus( $taskID, $status )
	{
	global $wpdb;
	
	$table = $wpdb->prefix."wpws_writer_task";

		$wpdb->update( $table, array('status'=>$status), array( 'taskID'=>$taskID), array( '%s' ), array( '%d' ) );
		
	}
	
	
}