/**
 * "Plugins by Sanjay Shankar": optional survey when one of the author's
 * plugins is deactivated. Skip always deactivates; nothing is sent without Send.
 */
( function () {
	'use strict';

	var cfg = window.sanjayshankarFeedback || {};
	var dialog = document.querySelector( '.sanjayshankar-survey' );
	if ( ! dialog || ! cfg.plugins || 'function' !== typeof dialog.showModal ) {
		return;
	}

	var form = dialog.querySelector( 'form' );
	var title = dialog.querySelector( '#sanjayshankar-survey-title' );
	var details = dialog.querySelector( '.sanjayshankar-survey__details' );
	var prompt = dialog.querySelector( '.sanjayshankar-survey__prompt' );
	var who = dialog.querySelector( '.sanjayshankar-survey__who' );
	var send = dialog.querySelector( '[data-survey="send"]' );
	var current = null;

	function deactivate() {
		if ( current ) {
			window.location.href = current.href;
		}
	}

	// Intercept the Deactivate link of each registered plugin.
	Object.keys( cfg.plugins ).forEach( function ( slug ) {
		var plugin = cfg.plugins[ slug ];
		var row = document.querySelector( 'tr[data-plugin="' + plugin.file + '"]' );
		var link = row && row.querySelector( '.deactivate a' );
		if ( ! link ) {
			return;
		}
		link.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			current = { slug: slug, href: link.href, link: link };
			form.reset();
			details.hidden = true;
			who.hidden = ! form.elements.contact.checked;
			send.disabled = true;
			send.textContent = send.getAttribute( 'data-label' ) || send.textContent;
			title.textContent = ( cfg.i18n.title || '%s' ).replace( '%s', plugin.name );
			dialog.showModal();
		} );
	} );

	send.setAttribute( 'data-label', send.textContent );

	form.addEventListener( 'change', function ( event ) {
		if ( 'reason' === event.target.name ) {
			details.hidden = false;
			prompt.textContent = cfg.prompts[ event.target.value ] || '';
			send.disabled = false;
		}
		if ( 'contact' === event.target.name ) {
			who.hidden = ! event.target.checked;
		}
	} );

	dialog.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '[data-survey]' );
		if ( event.target === dialog ) {
			dialog.close();
			return;
		}
		if ( ! button ) {
			return;
		}
		var action = button.getAttribute( 'data-survey' );
		if ( 'cancel' === action ) {
			dialog.close();
			return;
		}
		if ( 'skip' === action ) {
			deactivate();
			return;
		}

		// Send, then deactivate whatever happens (at most a few seconds' wait).
		var reason = form.querySelector( 'input[name="reason"]:checked' );
		if ( ! reason ) {
			return;
		}
		var data = new FormData();
		data.append( 'action', 'sanjayshankar_connect' );
		data.append( 'nonce', cfg.nonce );
		data.append( 'task', 'feedback' );
		data.append( 'source', current.slug );
		data.append( 'reason', reason.value );
		data.append( 'details', form.elements.details.value );
		if ( form.elements.contact.checked ) {
			data.append( 'contact', '1' );
			data.append( 'name', form.elements.name.value );
			data.append( 'email', form.elements.email.value );
		}
		send.disabled = true;
		send.textContent = cfg.i18n.sending || '';
		var done = false;
		var finish = function () {
			if ( ! done ) {
				done = true;
				deactivate();
			}
		};
		window.setTimeout( finish, 6000 );
		window
			.fetch( cfg.ajax, { method: 'POST', body: data, credentials: 'same-origin' } )
			.then( finish, finish );
	} );

	dialog.addEventListener( 'close', function () {
		if ( current && current.link && document.contains( current.link ) ) {
			current.link.focus();
		}
	} );
} )();
