/**
 * VIEW! Privacy — Admin scripts.
 * Vanilla JS. Regelt de mediaset-uploader voor de optionele, eigen
 * herstelknop-afbeelding op de instellingenpagina (het VIEW!-logo zelf
 * blijft vast ingesteld en is niet via de uploader te wijzigen).
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var uploadBtn = document.getElementById( 'vp-reopen-icon-upload' );
		var resetBtn = document.getElementById( 'vp-reopen-icon-reset' );
		var urlField = document.getElementById( 'vp-reopen-icon-url' );
		var preview = document.getElementById( 'vp-reopen-icon-preview' );

		if ( ! uploadBtn || ! urlField || typeof wp === 'undefined' || ! wp.media ) {
			return;
		}

		var frame = null;

		uploadBtn.addEventListener( 'click', function ( e ) {
			e.preventDefault();

			if ( frame ) {
				frame.open();
				return;
			}

			frame = wp.media( {
				title: uploadBtn.getAttribute( 'data-title' ) || 'Kies een afbeelding',
				button: { text: uploadBtn.getAttribute( 'data-button' ) || 'Gebruiken' },
				library: { type: 'image' },
				multiple: false,
			} );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();

				// Gebruik bewust de door WordPress gegenereerde thumbnail-variant
				// (doorgaans max. 150×150px) in plaats van het volledige origineel,
				// zodat een groot geüpload bestand de laadtijd van de site niet
				// onnodig belast. De herstelknop toont de afbeelding toch maar op
				// kleine schaal (desktop en mobiel).
				var url = ( attachment.sizes && attachment.sizes.thumbnail )
					? attachment.sizes.thumbnail.url
					: attachment.url;

				urlField.value = url;
				if ( preview ) {
					preview.innerHTML = '<img src="' + url + '" alt="" style="width:40px;height:40px;object-fit:contain;border-radius:50%;background:#f5f4f4;" />';
				}
				if ( resetBtn ) {
					resetBtn.style.display = '';
				}
			} );

			frame.open();
		} );

		if ( resetBtn ) {
			resetBtn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				urlField.value = '';
				resetBtn.style.display = 'none';
				if ( preview ) {
					var defaultUrl = uploadBtn.getAttribute( 'data-default-url' );
					var defaultBadge = uploadBtn.getAttribute( 'data-default-badge' ) || '';
					preview.innerHTML = '<img src="' + defaultUrl + '" alt="" style="width:40px;height:40px;object-fit:contain;border-radius:50%;background:#f5f4f4;" /> <span class="vp-badge vp-badge-neutral">' + defaultBadge + '</span>';
				}
			} );
		}
	} );
} )();
