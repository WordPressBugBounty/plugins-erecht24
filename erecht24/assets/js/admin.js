( function () {
	'use strict';

	var __ = window.wp && window.wp.i18n && window.wp.i18n.__ ? window.wp.i18n.__ : function ( text ) {
		return text;
	};

	document.addEventListener( 'click', function ( event ) {
		var target = event.target;

		if ( target && target.matches( '#erecht24-delete-api-key-btn' ) ) {
			var confirmed = window.confirm( __( 'API-Schlüssel wirklich entfernen?', 'erecht24' ) );

			if ( confirmed ) {
				var checkbox = document.getElementById( 'erecht24-delete-api-key' );
				if ( checkbox ) {
					checkbox.checked = true;
				}
				var form = target.closest( 'form' );
				if ( form ) {
					form.submit();
				}
			}
		}

		if ( target && target.matches( '.erecht24-copy-remote-to-local' ) ) {
			var importConfirmed = window.confirm( __( 'Lokale Texte wirklich mit den zuletzt synchronisierten Texten überschreiben?', 'erecht24' ) );

			if ( ! importConfirmed ) {
				event.preventDefault();
			}
		}

		if ( target && target.matches( '#erecht24-copy-debug' ) ) {
			event.preventDefault();

			var field = document.getElementById( 'erecht24-debug-data' );
			var result = document.getElementById( 'erecht24-copy-debug-result' );

			if ( ! field ) {
				return;
			}

			field.select();
			field.setSelectionRange( 0, field.value.length );

			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( field.value ).then( function () {
					if ( result ) {
						result.textContent = __( 'Kopiert.', 'erecht24' );
					}
				} );
				return;
			}

			document.execCommand( 'copy' );
			if ( result ) {
				result.textContent = __( 'Kopiert.', 'erecht24' );
			}
		}
	} );

	function updateDocumentSourceRows( container ) {
		var checked = container.querySelector( 'input[name$="[source]"]:checked' );
		var source = checked ? checked.value : 'remote';
		var rows = container.querySelectorAll( '[data-erecht24-source-row]' );
		var labels = container.querySelectorAll( '.erecht24-segmented label' );
		var actions = container.querySelectorAll( '[data-erecht24-source-action]' );

		rows.forEach( function ( row ) {
			row.style.display = row.getAttribute( 'data-erecht24-source-row' ) === source ? '' : 'none';
		} );

		actions.forEach( function ( action ) {
			action.style.display = action.getAttribute( 'data-erecht24-source-action' ) === source ? '' : 'none';
		} );

		labels.forEach( function ( label ) {
			var input = label.querySelector( 'input' );
			if ( input && input.value === source ) {
				label.classList.add( 'is-active' );
			} else {
				label.classList.remove( 'is-active' );
			}
		} );
	}

	document.querySelectorAll( '.erecht24-document-form' ).forEach( function ( form ) {
		updateDocumentSourceRows( form );
		form.addEventListener( 'change', function ( event ) {
			if ( event.target && event.target.matches( 'input[name$="[source]"]' ) ) {
				updateDocumentSourceRows( form );
			}
		} );
	} );
}() );
