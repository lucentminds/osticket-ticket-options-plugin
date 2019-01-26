/**
 * 04-01-2015
 * Plugin that renders view states. Requires Mustache.js
 * ~~ Scott Johnson
 */


/** List jshint ignore directives here. **/
/*global alert:false */
/*global clearTimeout:false */
/*global setTimeout:false */
/*global Mustache:false */
/*global $:false */

(function( $, undefined ){

	$.widget( 'ui.template', {
		_state: null,
		_template: '',
		_invalidated: true,
		_id: null,

		options: {
			renderOnInit: true,
			state: null,
			template: '',
			beforeRender: null,
			onRender: null,
			onStateChange: null
		},

		_create: function() {
			this._template = this.options.template;
			this._id = ''.concat( Math.random()*10000 ).replace( '.', '' );

			if ( typeof this.options.beforeRender == 'function' ) {
				this._beforeRender = this.options.beforeRender;
			}

			//if ( typeof this.options.onRender == 'function' ) {
			//	this._afterRender = this.options.onRender;
			//}

			if ( !this._template ) {
				this._template = this.element[0].innerHTML;
			}

			this._state = $.state( this.options.state );
			this.element.addClass( 'ui-widget-template ui-template' );
			this.element.empty();

			if ( this.options.renderOnInit ) {
				this.render();
			}

			//this.element.on( 'statechange', {self:this, id:this._id}, this._onStateChange );
		},// /_create()

		_onStateChange: function( event ){
			var self = event.data.self;
			var id = event.data.id;

			if( $( event.target ).data().uiTemplate._id !== id ) {
				/**
				 * This event was triggered on a different stated element than
				 * ours.
				 */
				return;
			}

			self.render();
		},// /_onStateChange()

		_beforeRender: function(){},
		_afterRender: null,

		render: function( undefined ) {
			var cHtml, oState;
			var deferred = $.Deferred();

			if ( this._beforeRender( undefined, {state:oState} ) === false ) {
				// Do not render yet.
				return;
			}

			oState = this._state.getState();
			cHtml = Mustache.render( this._template, oState );
			this.element[0].innerHTML = cHtml;

			deferred.resolve();

			if ( this._afterRender ) {
				this._afterRender( undefined, {state:oState} );
			}

			this._triggerEvent( 'render', {state:oState} );

			return deferred.promise();
		},// /render()

		getState: function( undefined ) {
			return this._state.getState();
		},// /getState()


		/**
		 * This is a proxy method that allows you to change the template state.
		 * @param  {object} oState		Object with changed state values.
		 * @param  {boolean} lRender	Whether or not to render changes.
		 * @param  {boolean} lDiff		Whether or not to check for changes.
		 */
		setState: function( oState, lRender, lDiff, undefined ) {
			var oResult = this._state.setState( oState, lDiff );

			if ( oResult !== false ) {
				this._triggerEvent( 'statechange', oResult );

				if ( lRender !== false ) {
					this.render();
				}
			}
		},// /setState()

		setTemplate: function( cTemplate, lRender, undefined ) {
			this._template = cTemplate;

			if ( lRender !== false ) {
				this.render();
			}
		},// /setTemplate()

		getInitialState: function( undefined ) {
			return this._state.getInitialState();
		},// /getInitialState()

		/**
		 * This method allows you to call a method listening to this element
		 * only as well as trigger a bubbling event.
		 */
		_triggerEvent: function( cType, oData ){
			var oEvent = $.Event( cType );
			var fnMethod;

			switch( cType ){
			case 'statechange':
				fnMethod = this.options.onStateChange;
				break;

			case 'render':
				fnMethod = this.options.onRender;
				break;

			}// /switch()


			if ( fnMethod ) {
				fnMethod( oEvent, oData );
			}
			this._trigger( cType, oEvent, oData );
		},// /_triggerEvent()


		_destroy: function(){
			this._state.destroy();
			this._state = null;

			this._template = '';

			//if ( this.element.data( 'uiState' ) ) {
			//	this.element.state( 'destroy' );
			//}

			this.element.removeClass( 'ui-widget-template ui-template' );
			this.element[0].innerHTML = this._template;

		}// /_destroy()
	});
}( jQuery ));
