var InlineEditingBehavior;

InlineEditingBehavior = Marionette.Behavior.extend( {
	editing: false,

	$currentEditingArea: null,

	ui: function() {
		return {
			inlineEditingArea: '.' + this.getOption( 'inlineEditingClass' )
		};
	},

	events: function() {
		return {
			'click @ui.inlineEditingArea': 'onInlineEditingClick',
			'input @ui.inlineEditingArea':'onInlineEditingUpdate'
		};
	},

	getEditingSettingKey: function() {
		return this.$currentEditingArea.data().qazanaSettingKey;
	},

	startEditing: function( $element ) {
		if ( this.editing ) {
			return;
		}

		this.$currentEditingArea = $element;

		var elementData = this.$currentEditingArea.data(),
			editModel = this.view.getEditModel();

		/**
		 *  Replace rendered content with unrendered content.
		 *  This way the user can edit the original content, before shortcodes and oEmbeds are fired.
		 */
		this.$currentEditingArea.html( editModel.getSetting( this.getEditingSettingKey() ) );

		var QazanaInlineEditor = qazanaFrontend.getElements( 'window' ).QazanaInlineEditor;

		this.editing = true;

		this.view.allowRender = false;

		var inlineEditingConfig = qazana.config.inlineEditing,
			elementDataToolbar = elementData.qazanaInlineEditingToolbar;

		this.pen = new QazanaInlineEditor( {
			linksInNewWindow: true,
			stay: false,
			editor: this.$currentEditingArea[0],
			list: 'none' === elementDataToolbar ? [] : inlineEditingConfig.toolbar[ elementDataToolbar || 'basic' ],
			toolbarIconsPrefix: 'eicon-editor-',
			toolbarIconsDictionary: {
				externalLink: {
					className: 'eicon-editor-external-link'
				},
				list: {
					className: 'eicon-editor-list-ul'
				},
				insertOrderedList: {
					className: 'eicon-editor-list-ol'
				},
				insertUnorderedList: {
					className: 'eicon-editor-list-ul'
				},
				createlink: {
					className: 'eicon-editor-link'
				},
				unlink: {
					className: 'eicon-editor-unlink'
				},
				blockquote: {
					className: 'eicon-editor-quote'
				},
				p: {
					className: 'eicon-editor-paragraph'
				},
				pre: {
					className: 'eicon-editor-code'
				}
			}
		} );

		var $menuItems = jQuery( this.pen._menu ).children();

		/**
		 * When the edit area is not focused (on blur) the inline editing is stopped.
		 * In order to prevent blur event when the user clicks on toolbar buttons while editing the
		 * content, we need the prevent their mousedown event. This also prevents the blur event.
		 */
		$menuItems.on( 'mousedown', function( event ) {
			event.preventDefault();
		} );

		this.$currentEditingArea
			.focus()
			.on( 'blur', _.bind( this.onInlineEditingBlur, this ) );
	},

	stopEditing: function() {
		this.editing = false;

		this.pen.destroy();

		this.view.allowRender = true;

		/**
		 * Inline editing has several toolbar types (advanced, basic and none). When editing is stopped,
		 * we need to rerender the area. To prevent multiple renderings, we will render only areas that
		 * use advanced toolbars.
		 */
		if ( 'advanced' === this.$currentEditingArea.data().qazanaInlineEditingToolbar ) {
			this.view.getEditModel().renderRemoteServer();
		}
	},

	onInlineEditingClick: function( event ) {
		var self = this,
			$targetElement = jQuery( event.currentTarget );

		/**
		 * When starting inline editing we need to set timeout, this allows other inline items to finish
		 * their operations before focusing new editing area.
		 */
		setTimeout( function() {
			self.startEditing( $targetElement );
		}, 30 );
	},

	onInlineEditingBlur: function() {
		var self = this;

		/**
		 * When exiting inline editing we need to set timeout, to make sure there is no focus on internal
		 * toolbar action. This prevent the blur and allows the user to continue the inline editing.
		 */
		setTimeout( function() {
			var selection = qazanaFrontend.getElements( 'window' ).getSelection(),
				$focusNode = jQuery( selection.focusNode );

			if ( $focusNode.closest( '.pen-input-wrapper' ).length ) {
				return;
			}

			self.stopEditing();
		}, 20 );
	},

	onInlineEditingUpdate: function() {
		this.view.getEditModel().setSetting( this.getEditingSettingKey(), this.$currentEditingArea.html() );
	}
} );

module.exports = InlineEditingBehavior;
