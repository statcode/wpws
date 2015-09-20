jQuery(function($) {
	
		//WPWS Order Paid
		$('.isPaid').on('click', function(e) {

			var orderID = $(this).val();
			
			var isCheck = $(this).is(':checked') ? 1 : 0;
			
			$.ajax({
						type: "POST",
						url: myAjax.ajaxurl,
         			    data : {
							action: 'isPaid',
							isCheck: isCheck,
							orderID: orderID
						  },
						dataType: "text",  
						success: function(response){
							//$('#title').focus();
							//$('#title').val(response);
							
							alert( 'Updated!' );
						}
			});
			
		});
      
	  	//WPWS Sent Invoice
		$('.sentInvoice').on('click', function(e) {

			var orderID = $(this).val();
			
			var isCheck = $(this).is(':checked') ? 1 : 0;
			
			$.ajax({
						type: "POST",
						url: myAjax.ajaxurl,
         			    data : {
							action: 'sentInvoice',
							isCheck: isCheck,
							orderID: orderID
						  },
						dataType: "text",  
						success: function(response){
							//$('#title').focus();
							//$('#title').val(response);
							
							alert( 'Updated!' );
						}
			});
			
		});
		
		
		//WPWS Article page - metabox will display information about the OrderID
		$('#wpws_meta_orderID').on('change', function(e) {

			var orderID = $(this).val();
			
			$.ajax({
						type: "POST",
						url: myAjax.ajaxurl,
         			    data : {
							action: 'metabox_orderID',
							orderID: orderID
						  },
						dataType: "text",  
						success: function(response){
							//$('#title').focus();
							//$('#title').val(response);
							
							$('#orderInfo').empty().html( response );
						}
			});
			
		});
		
		
		//Task page - will update the 'status' of a particular Task ID, on select/drop-down change
		$('.wpws_order_status').on('change', function(e) {

			var taskID = $(this).attr('id');
			var status = $(this).val();
			
			$.ajax({
						type: "POST",
						url: myAjax.ajaxurl,
         			    data : {
							action: 'order_status',
						 	status : status,
							taskID: taskID
						  },
						dataType: "text",  
						success: function(response){
							//$('#title').focus();
							//$('#title').val(response);
							
							alert( 'Updated!' );
						}
			});
			
		});
		
		//Metabox - Due Date
		$('#wpws_meta_duedate').datepicker({
			dateFormat : 'm/d/yy'
		});
		
		//Accordian for My Tasks
		$( ".accordian" ).accordion({
            collapsible: true,
			active: 'none',
			event: 'click hoverintent'
        });
 });

