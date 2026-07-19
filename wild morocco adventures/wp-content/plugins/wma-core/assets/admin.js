( function ( $ ) {
	'use strict';
	const index = () => String( Date.now() ) + String( Math.floor( Math.random() * 1000 ) );
	$( document ).on( 'click', '[data-wma-add-row]', function () {
		const root = $( this ).closest( '[data-wma-repeater]' );
		const type = $( this ).data( 'wma-add-row' );
		root.find( '[data-wma-repeater-rows]' ).append( root.find( 'template[data-wma-template="' + type + '"]' ).html().replaceAll( '__INDEX__', index() ) );
	} );
	$( document ).on( 'click', '[data-wma-remove-row]', function () { $( this ).closest( '[data-wma-row]' ).remove(); } );
	$( document ).on( 'click', '[data-wma-gallery-select]', function () {
		const root = $( this ).closest( '[data-wma-gallery]' );
		const frame = wp.media( { title: wmaAdmin.galleryTitle, button: { text: wmaAdmin.galleryButton }, multiple: true, library: { type: 'image' } } );
		frame.on( 'select', function () { const images = frame.state().get( 'selection' ).toJSON(); root.find( '[data-wma-gallery-input]' ).val( images.map( ( image ) => image.id ).join( ',' ) ); root.find( '[data-wma-gallery-preview]' ).html( images.map( ( image ) => '<img src="' + ( image.sizes?.thumbnail?.url || image.url ) + '" alt="">' ).join( '' ) ); } );
		frame.open();
	} );
	$( document ).on( 'click', '[data-wma-gallery-clear]', function () { const root = $( this ).closest( '[data-wma-gallery]' ); root.find( 'input' ).val( '' ); root.find( '[data-wma-gallery-preview]' ).empty(); } );
	$( document ).on( 'click', '[data-wma-image-select]', function () {
		const root = $( this ).closest( '[data-wma-image]' );
		const frame = wp.media( { title: wmaAdmin.imageTitle, button: { text: wmaAdmin.imageButton }, multiple: false, library: { type: 'image' } } );
		frame.on( 'select', function () { const image = frame.state().get( 'selection' ).first().toJSON(); root.find( '[data-wma-image-input]' ).val( image.id ); root.find( '[data-wma-image-preview]' ).html( '<img src="' + ( image.sizes?.medium?.url || image.url ) + '" alt="">' ); } );
		frame.open();
	} );
	$( document ).on( 'click', '[data-wma-image-clear]', function () { const root = $( this ).closest( '[data-wma-image]' ); root.find( 'input' ).val( '' ); root.find( '[data-wma-image-preview]' ).empty(); } );
}( jQuery ) );
