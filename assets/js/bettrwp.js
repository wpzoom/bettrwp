jQuery(document).ready(function(){
	( function($, Bettrwp) {

		$('.wpzoom-bettrwp, #wpzoom_set_front_page-link').on( 'click', function(e){
			e.preventDefault();
			
			var $this = $(this);
			
			var page_id = $this.data('page-id');
			var nonce = $this.data('nonce');

			var data = {
				page_id:page_id,
			    action: 'wpzoom_set_frontpage',
				nonce: nonce,
			};

			$.post( Bettrwp.ajaxUrl, data, function(response){
				//console.log( data );	
				if ( ! response.success ) {
					alert('Something went wrong!')
				};
				location.reload();
			});
		});

	})(jQuery, WPZOOM_Bettrwp);
});