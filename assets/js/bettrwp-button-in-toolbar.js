// wrapped into IIFE - to leave global space clean.
( function( window, wp ){

	//console.log( wp.hooks );
    // just to keep it cleaner - we refer to our link by id for speed of lookup on DOM.

    // check if gutenberg's editor root element is present.
    var editorEl = document.getElementById( 'editor' );
    if( !editorEl ){ // do nothing if there's no gutenberg root element on page.
        return;
    }

	//console.log( wp.data );

    var unsubscribe = wp.data.subscribe( function () {

        setTimeout( function () {

			if( 'publish' !== wp.data.select('core/editor').getCurrentPost().status ) {
				return;
			}

    		var button_id = 'wpzoom_set_front_page-button';

			if( WPZOOMBettrEditor.frontpage_id === WPZOOMBettrEditor.post_id ) {
				var button_html = '<button disabled type="button" id="' + button_id + '" class="components-button editor-post-set-as-homepage is-tertiary">' + WPZOOMBettrEditor.labelPageIsFront + '</button>';
			}
			else {
				// prepare our custom link's html.
				var button_html = '<button type="button" id="' + button_id + '" class="components-button editor-post-set-as-homepage is-tertiary">' + WPZOOMBettrEditor.labelSetPageAsFront + '</button>';
			}

            if ( !document.getElementById( button_id ) ) {

                var toolbalEl = editorEl.querySelector( '.edit-post-header__settings' );

                if( toolbalEl instanceof HTMLElement ) {

					toolbalEl.insertAdjacentHTML( 'afterbegin', button_html );
					const getButton = document.querySelector( '.editor-post-set-as-homepage' );

					getButton.addEventListener( 'click', () => {
						const post_id = wp.data.select('core/editor').getCurrentPost().id;
						const data = { 						
							page_id: post_id,
							action: 'wpzoom_set_frontpage',
							nonce: WPZOOMBettrEditor.ajax_nonce
						};
						if ( window.confirm( WPZOOMBettrEditor.confirmText ) ) {
							fetch( WPZOOMBettrEditor.ajaxUrl, {
							method: 'POST', // or 'PUT'
							credentials: 'same-origin',
							headers: {
								'Content-Type': 'application/x-www-form-urlencoded',
								'Cache-Control': 'no-cache',
							},
							body: new URLSearchParams( data ),
							} )
							.then(
								response => response.json()
							)
							.then( data => {
								console.log( 'Success:', data );
								getButton.disabled = true;
								getButton.textContent = WPZOOMBettrEditor.labelPageIsFront;
							})
							.catch( ( error ) => {
								console.error( 'Error:', error );
							});
						}
					});
                }
            }
        }, 1 )
    });
})( window, wp );