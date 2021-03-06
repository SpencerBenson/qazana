var ViewModule = require( '../../utils/view-module' );

module.exports = ViewModule.extend( {
	getDefaultSettings: function() {

		return {
			scrollDuration: 500,
			selectors: {
				links: 'a[href*="#"]',
				targets: '.qazana-element, .qazana-menu-anchor',
				scrollable: 'html, body',
				wpAdminBar: '#wpadminbar'
			}
		};
	},

	getDefaultElements: function() {
		var $ = jQuery,
			selectors = this.getSettings( 'selectors' );

		return {
			$scrollable: $( selectors.scrollable ),
			$wpAdminBar: $( selectors.wpAdminBar )
		};
	},

	bindEvents: function() {
		qazanaFrontend.getElements( '$document' ).on( 'click', this.getSettings( 'selectors.links' ), this.handleAnchorLinks );
	},

	handleAnchorLinks: function( event ) {
		var clickedLink = event.currentTarget,
			isSamePathname = ( location.pathname === clickedLink.pathname ),
			isSameHostname = ( location.hostname === clickedLink.hostname );

		if ( ! isSameHostname || ! isSamePathname || clickedLink.hash.length < 2 ) {
			return;
		}

		var $anchor = jQuery( clickedLink.hash ).filter( this.getSettings( 'selectors.targets' ) );

		if ( ! $anchor.length ) {
			return;
		}

		var hasAdminBar = ( 1 <= this.elements.$wpAdminBar.length ),
			scrollTop = $anchor.offset().top;

		if ( hasAdminBar ) {
			scrollTop -= this.elements.$wpAdminBar.height();
		}

		event.preventDefault();

		scrollTop = qazanaFrontend.hooks.applyFilters( 'frontend/handlers/menu_anchor/scroll_top_distance', scrollTop );

		this.elements.$scrollable.animate( {
			scrollTop: scrollTop
		}, this.getSettings( 'scrollDuration' ), 'linear' );
	},

	onInit: function() {
		ViewModule.prototype.onInit.apply( this, arguments );

		this.bindEvents();
	}
} );
