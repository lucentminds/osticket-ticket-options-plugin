/**
 * 04-01-2015
 * Plugin that renders view states. Requires Mustache.js
 * ~~ Scott Johnson
 */


/** List jshint ignore directives here. **/
/*global alert:false */
/*global clearTimeout:false */
/*global setTimeout:false */
/*global $:false */

(function( $, undefined ){
	$.state = function( oInitialState ){
		var self;
		self = {
			_initialState: oInitialState,
			_currentState: oInitialState,

			getState: function( undefined ) {
				return self._currentState;
			},// /getState()

			setState: function( oState, lDiff, undefined ) {
				var oLastState, oNewState;

				/**
				 * If lDiff is defined, use whatever it is defined as otherwise
				 * it's true. lDiff defines whether or not we should expend cpu
				 * cycles to determine if the state data has changed.
				 */
				lDiff = ( lDiff === undefined ) ?true :lDiff;

				/**
				 * 10-06-2015
				 * jQuery extend doesn't reset from populated arrays to empty
				 * arrays.
				 * ~~ Scott Johnson
				 */
				if ( lDiff && isEqual( oState, self._currentState, true ) ) {
					// Nothing changed.
					return false;
				}

				oNewState = extend( true, {}, self._currentState );
				extend( true, oNewState, oState );

				oLastState = extend( true, {}, self._currentState );
				this._currentState = extend( true, {}, oNewState );


				return { newState:oNewState, oldState:oLastState };
			},// /setState()

			getInitialState: function( undefined ) {
				return self._initialState;
			},// /getInitialState()

			setInitialState: function( oState, undefined ) {
				self._initialState = oState;
			},// /setInitialState()

			destroy: function( undefined ) {
				self._initialState = nullify( self._initialState );
				self._currentState = nullify( self._currentState );
				//self._lastState = nullify( self._lastState );
			}// /destroy()
		};// /self{}

		return self;
	};// /state()

	$.widget( 'ui.state', {
		_state: null,

		options: {
			state: null,
			onChange: null
		},


		_create: function() {
			this._state = $.state( this.options.state );
			this.element.addClass( 'ui-widget-state' );

			if ( this.options.onChange ) {
				this._onChange = this.options.onChange;
			}
		},// /_create()

		_onChange: function(){},

		setState: function( oState, lEmit, undefined ) {
			var result = this._state.setState( oState );

			if ( lEmit !== false && result !== false ) {
				this._triggerEvent( 'change', result );
			}
		},// /setState()

		getState: function( undefined ) {
			return this._state.getState();
		},// /getState()

		setInitialState: function( oState, undefined ) {
			this._state.setInitialState( oState );
		},// /setInitialState()

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
			case 'change':
				fnMethod = this.options.onChange;
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
			this.element.removeClass( 'ui-widget-state' );
		}// /_destroy()
	});

	var toStr = Object.prototype.toString;

	var isArray = function isArray(arr) {
		if (typeof Array.isArray === 'function') {
			return Array.isArray(arr);
		}

		return toStr.call(arr) === '[object Array]';
	};



	var extend = (function(){
		var hasOwn = Object.prototype.hasOwnProperty;

		var isPlainObject = function isPlainObject(obj) {
			if (!obj || toStr.call(obj) !== '[object Object]') {
				return false;
			}

			var hasOwnConstructor = hasOwn.call(obj, 'constructor');
			var hasIsPrototypeOf = obj.constructor && obj.constructor.prototype && hasOwn.call(obj.constructor.prototype, 'isPrototypeOf');
			// Not own constructor property must be Object
			if (obj.constructor && !hasOwnConstructor && !hasIsPrototypeOf) {
				return false;
			}

			// Own properties are enumerated firstly, so to speed up,
			// if last one is own, then all properties are own.
			var key;
			for (key in obj) {/**/}

			return typeof key === 'undefined' || hasOwn.call(obj, key);
		};

		var _extend = function() {
			var options, name, src, copy, copyIsArray, clone,
				target = arguments[0],
				i = 1,
				length = arguments.length,
				deep = false;

			// Handle a deep copy situation
			if (typeof target === 'boolean') {
				deep = target;
				target = arguments[1] || {};
				// skip the boolean and the target
				i = 2;
			} else if ((typeof target !== 'object' && typeof target !== 'function') || target === null) {
				target = {};
			}

			for (; i < length; ++i) {
				options = arguments[i];
				// Only deal with non-null/undefined values
				if (options !== null) {
					// Extend the base object
					for (name in options) {
						src = target[name];
						copy = options[name];

						// Prevent never-ending loop
						if (target !== copy) {
							// Recurse if we're merging plain objects or arrays
							if (deep && copy && (isPlainObject(copy) || (copyIsArray = isArray(copy)))) {
								if (copyIsArray) {
									copyIsArray = false;
									//clone = src && isArray(src) ? src : [];
									clone = src && isArray(src) ? [] : [];
								} else {
									clone = src && isPlainObject(src) ? src : {};
								}

								// Never move original objects, clone them
								target[name] = _extend(deep, clone, copy);

							// Don't bring in undefined values
							} else if (typeof copy !== 'undefined') {
								target[name] = copy;
							}
						}
					}
				}
			}

			// Return the modified object
			return target;
		};

		return _extend;
	}());



	/**
	 * Compares two objects to see if they are the same. Returns true if both
	 * objects are exactly the same. Returns false otherwise.
	 * Param "a" should contain the shorter new object to increase speed.
	 */
	var isEqual = function( a, b, lDeep ) {
		//App.isEqual( {a:[2]}, {a:[1]}, true ); should return false.
		var n, i, l, itemA, itemB, typeA, typeB, obs = lDeep ?[] :null;


		if ( ( a === null || b === null ) && a !== b ) {
			return false;
		}

		for ( n in a ) {
			itemA = a[n];
			itemB = b[n];
			typeA = typeof itemA;
			typeB = typeof itemB;

			if ( typeA != typeB ) {
				return false;
			}

			if ( typeA == 'object' ) {
				if ( itemA === null || itemB === null && itemA != itemB ) {
					return false;
				}

				if ( isArray( itemA ) && isArray( itemB ) && itemA.length != itemB.length ) {
					return false;
				}



				if ( lDeep ) {
					/**
					 * Add this property name to the list of object properties
					 * array.
					 */
					obs.push( n );
				}
				continue;
			}

			if ( itemA !== itemB ) {
				return false;
			}
		}// /for()

		if ( obs && obs.length > 0 ) {
			/**
			 * Loop over each property name that is an object type and compare the
			 * values.
			 */
			l = obs.length;

			for ( i = 0; i < l; i++ ) {
				n = obs[i];

				itemA = a[n];
				itemB = b[n];

				if ( !isEqual( itemA, itemB, lDeep ) ) {
					return false;
				}
			}// /for()
		}

		return true;
	};// /isEqual()

	var nullify = function( o ) {
		var i;

		if ( !o ) {
			return null;
		}

		for( i in o ) {
			o[i] = null;
			delete o[i];
		}

		return null;
	};// /nullify()



}( jQuery ));
