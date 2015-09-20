<?php
class WPWS_AJAX extends WPWS //AJAX FUNCTIONS
{
	public function __construct()
	{
		
	}
	
	//Updating isPaid
	public function isPaid()
	{
	
		$orderID = intval( $_POST['orderID'] );
		$isCheck = intval( $_POST['isCheck'] );
	
		 WPWS_Model::updatePaid( $orderID, $isCheck );

		die;
		
	}
	
	//Updating Sent Invoice
	public function sentInvoice()
	{
	
		$orderID = intval( $_POST['orderID'] );
		$isCheck = intval( $_POST['isCheck'] );
	
		 WPWS_Model::updateSentInvoice( $orderID, $isCheck );

		die;
		
	}
	
	//For updating a Task status
	public function order_status()
	{
	
	$taskID = intval( $_POST['taskID'] );
	$status = esc_attr( $_POST['status'] );
	
		 WPWS_Model::updateOrderStatus( $taskID, $status );

		die;
		
	}
	
	public function metabox_orderID()
	{
	
	$orderID = intval( $_POST['orderID'] );
	
		$wpwsO = WPWS_Model:: getDataOrderID( $orderID );
		
		//Type
		$term_id_type = $wpwsO->term_id_type;
		$wpws_type = WPWS_Model::getTermByTermID( $term_id_type );
		
		//Length
		$term_id_length = $wpwsO->term_id_length;
		$wpws_length = WPWS_Model::getTermByTermID( $term_id_length );
		
		//Category
		$categoryO = get_term( $wpwsO->term_id_category, 'category' );
		$wpws_category = $categoryO->name;

		//Quantity
		$wpws_quantity = $wpwsO->quantity;
		
		//Notes
		$wpws_notes = stripslashes_deep( nl2br( $wpwsO->notes ));
	?>	
    <ul style="list-style-type:none; margin:0; padding:0;">
       <li><strong><em>Type: </em></strong><?php echo $wpws_type;?></li>
       <li> <strong><em>Length: </em></strong><?php echo $wpws_length;?> </li>
        <li><strong><em>Category: </em></strong><?php echo $wpws_category;?> </li>
        <li><strong><em>Quantity: </em></strong><?php echo $wpws_quantity;?></li>
        <li><strong><em>Notes: </em></strong><?php echo $wpws_notes;?></li>
     </ul>

	<?php
		die;
		
	}
	
	
	
	
}