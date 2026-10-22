/**
 * "Plugins by Sanjay Shankar" newsletter card and settings box:
 * subscribe, not now, don't show again.
 */
( function () {
	'use strict';

	var cfg = window.sanjayshankarConnect || {};
	var i18n = cfg.i18n || {};

	function send( card, task, extra ) {
		var data = new FormData();
		data.append( 'action', 'sanjayshankar_connect' );
		data.append( 'nonce', cfg.nonce );
		data.append( 'task', task );
		data.append( 'source', card.getAttribute( 'data-source' ) || '' );
		Object.keys( extra || {} ).forEach( function ( key ) {
			data.append( key, extra[ key ] );
		} );
		return window
			.fetch( cfg.ajax, { method: 'POST', body: data, credentials: 'same-origin' } )
			.then( function ( response ) {
				return response.json();
			} );
	}

	function setup( card ) {
		var form = card.querySelector( 'form' );
		var message = card.querySelector( '.sanjayshankar-connect__message' );

		// "Not now" and "Don't show again" (the dismissible card only).
		card.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( '[data-connect-action]' );
			if ( ! button ) {
				return;
			}
			event.preventDefault();
			send( card, button.getAttribute( 'data-connect-action' ) ).catch( function () {} );
			card.classList.add( 'is-leaving' );
			window.setTimeout( function () {
				card.remove();
			}, 200 );
		} );

		if ( ! form ) {
			return;
		}

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			var email = form.elements.email.value.trim();
			var submit = form.querySelector( '[type="submit"]' );

			if ( ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email ) ) {
				message.textContent = i18n.invalid || '';
				message.className = 'sanjayshankar-connect__message is-error';
				form.elements.email.focus();
				return;
			}

			submit.disabled = true;
			message.className = 'sanjayshankar-connect__message';
			message.textContent = i18n.sending || '';

			send( card, 'subscribe', { email: email, first_name: form.elements.first_name.value.trim() } )
				.then( function ( json ) {
					if ( ! json || ! json.success ) {
						throw new Error( json && json.data && json.data.message ? json.data.message : '' );
					}
					var done = document.createElement( 'p' );
					done.className = 'sanjayshankar-connect__done';
					done.setAttribute( 'role', 'status' );
					done.textContent = json.data && json.data.message ? json.data.message : '';
					form.replaceWith( done );
					var close = card.querySelector( '.sanjayshankar-connect__close' );
					if ( close ) {
						close.remove();
					}
				} )
				.catch( function ( error ) {
					submit.disabled = false;
					message.className = 'sanjayshankar-connect__message is-error';
					message.textContent = ( error && error.message ) || i18n.error || '';
				} );
		} );
	}

	document.querySelectorAll( '.sanjayshankar-connect' ).forEach( setup );
} )();
