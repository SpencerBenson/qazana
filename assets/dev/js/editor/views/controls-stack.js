var ControlsStack;

ControlsStack = Marionette.CompositeView.extend( {
	className: 'qazana-panel-controls-stack',

	classes: {
		popover: 'qazana-controls-popover',
		popoverToggle: 'qazana-control-popover-toggle-toggle'
	},

	activeTab: null,

	activeSection: null,

	templateHelpers: function() {
		return {
			elementData: qazana.getElementData( this.model )
		};
	},

	ui: function() {
		return {
			tabs: '.qazana-panel-navigation-tab',
			reloadButton: '.qazana-update-preview-button'
		};
	},

	events: function() {
		var events = {
			'click': 'onClick',
			'click @ui.tabs': 'onClickTabControl',
			'click @ui.reloadButton': 'onReloadButtonClick'
		};

		events[ 'click .' + this.classes.popover ] = 'onPopoverClick';

		return events;
	},

	modelEvents: {
		'destroy': 'onModelDestroy'
	},

	behaviors: {
		HandleInnerTabs: {
			behaviorClass: require( 'qazana-behaviors/inner-tabs' )
		}
	},

	initialize: function() {
		this.listenTo( qazana.channels.deviceMode, 'change', this.onDeviceModeChange );
	},

	filter: function( controlModel ) {
		if ( controlModel.get( 'tab' ) !== this.activeTab ) {
			return false;
		}

		if ( 'section' === controlModel.get( 'type' ) ) {
			return true;
		}

		var section = controlModel.get( 'section' );

		return ! section || section === this.activeSection;
	},

	isVisibleSectionControl: function( sectionControlModel ) {
		return this.activeTab === sectionControlModel.get( 'tab' );
	},

	activateTab: function( $tab ) {
		var self = this,
			activeTab = this.activeTab = $tab.data( 'tab' );

		this.ui.tabs.removeClass( 'active' );

		$tab.addClass( 'active' );

		var sectionControls = this.collection.filter( function( controlModel ) {
			return 'section' === controlModel.get( 'type' ) && self.isVisibleSectionControl( controlModel );
		} );

		if ( sectionControls[0] ) {
			this.activateSection( sectionControls[0].get( 'name' ) );
		}
	},

	activateSection: function( sectionName ) {
		this.activeSection = sectionName;
	},

	getChildView: function( item ) {
		var controlType = item.get( 'type' );

		return qazana.getControlView( controlType );
	},

	handlePopovers: function() {
		var self = this,
			popoverStarted = false,
			$popover;

		self.removePopovers();

		self.children.each( function( child ) {
			if ( popoverStarted ) {
				$popover.append( child.$el );
			}

			var popover = child.model.get( 'popover' );

			if ( ! popover ) {
				return;
			}

			if ( popover.start ) {
				popoverStarted = true;

				$popover = jQuery( '<div>', { 'class': self.classes.popover } );

				child.$el.before( $popover );

				$popover.append( child.$el );
			}

			if ( popover.end ) {
				popoverStarted = false;
			}
		} );
	},

	hidePopovers: function() {
		this.$el.find( '.' + this.classes.popover ).hide();
	},

	removePopovers: function() {
		this.$el.find( '.' + this.classes.popover ).remove();
	},

	openActiveSection: function() {
		var activeSection = this.activeSection,
			activeSectionView = this.children.filter( function( view ) {
				return activeSection === view.model.get( 'name' );
			} );

		if ( activeSectionView[0] ) {
			activeSectionView[0].ui.heading.addClass( 'qazana-open' );
		}
	},

	onRenderCollection: function() {
		this.openActiveSection();

		this.handlePopovers();
	},

	onRenderTemplate: function() {
		this.activateTab( this.ui.tabs.eq( 0 ) );
	},

	onModelDestroy: function() {
		this.destroy();
	},

	onClick: function( event ) {
		if ( jQuery( event.target ).closest( '.' + this.classes.popover + ',.' + this.classes.popoverToggle ).length ) {
			return;
		}

		this.hidePopovers();
	},

	onPopoverClick: function( event ) {
		var $currentPopover = jQuery( event.target ).closest( '.' + this.classes.popover );

		this.$el.find( '.' + this.classes.popover ).not( $currentPopover ).hide();
	},

	onClickTabControl: function( event ) {
		event.preventDefault();

		var $tab = this.$( event.currentTarget );

		if ( this.activeTab === $tab.data( 'tab' ) ) {
			return;
		}

		this.activateTab( $tab );

		this._renderChildren();
	},

	onReloadButtonClick: function() {
		qazana.reloadPreview();
	},

	onDeviceModeChange: function() {
		this.$el.removeClass( 'qazana-responsive-switchers-open' );
	},

	onChildviewControlSectionClicked: function( childView ) {
		var isSectionOpen = childView.ui.heading.hasClass( 'qazana-open' );

		this.activateSection( isSectionOpen ? null : childView.model.get( 'name' ) );

		this._renderChildren();
	},

	onChildviewResponsiveSwitcherClick: function( childView, device ) {
		if ( 'desktop' === device ) {
			this.$el.toggleClass( 'qazana-responsive-switchers-open' );
		}
	}
} );

module.exports = ControlsStack;
