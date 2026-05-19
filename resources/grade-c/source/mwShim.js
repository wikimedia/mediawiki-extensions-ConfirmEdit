/**
 * Minimal mw global for the Grade C hCaptcha bundle. ES5.
 */
( function ( window ) {
	'use strict';

	// utils.js uses Object.entries; landed in Chrome 54.
	if ( !Object.entries ) {
		Object.entries = function ( obj ) {
			return Object.keys( obj ).map( function ( k ) {
				return [ k, obj[ k ] ];
			} );
		};
	}

	function makeMap() {
		var store = {};
		return {
			get: function ( key ) {
				return Object.prototype.hasOwnProperty.call( store, key ) ? store[ key ] : null;
			},
			set: function ( map ) {
				for ( var k in map ) {
					if ( Object.prototype.hasOwnProperty.call( map, k ) ) {
						store[ k ] = map[ k ];
					}
				}
			},
			exists: function ( key ) {
				return Object.prototype.hasOwnProperty.call( store, key );
			}
		};
	}

	var bootstrap = window.__confirmEditHCaptchaGradeC || {};
	var config = makeMap();
	var messages = makeMap();
	config.set( bootstrap.config || {} );
	messages.set( bootstrap.messages || {} );

	window.mw = {
		config: config,
		messages: messages,
		msg: function ( key ) {
			return messages.get( key ) || '⧼' + key + '⧽';
		},
		now: Date.now,
		track: function () {},
		hook: function () {
			return { fire: function () {} };
		},
		errorLogger: {
			logError: function ( err, label ) {
				window.console.error( label || 'error', err );
			}
		},
		log: {
			warn: function ( msg ) {
				window.console.warn( msg );
			}
		}
	};
}( window ) );
