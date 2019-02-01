(function( undefined ){

/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/mustache/2.0.0/mustache.js
**/
/*!
 * mustache.js - Logic-less {{mustache}} templates with JavaScript
 * http://github.com/janl/mustache.js
 */

/*global define: false*/

(function (global, factory) {
  if (typeof exports === "object" && exports) {
    factory(exports); // CommonJS
  } else if (typeof define === "function" && define.amd) {
    define(['exports'], factory); // AMD
  } else {
    factory(global.Mustache = {}); // <script>
  }
}(this, function (mustache) {

  var Object_toString = Object.prototype.toString;
  var isArray = Array.isArray || function (object) {
    return Object_toString.call(object) === '[object Array]';
  };

  function isFunction(object) {
    return typeof object === 'function';
  }

  function escapeRegExp(string) {
    return string.replace(/[\-\[\]{}()*+?.,\\\^$|#\s]/g, "\\$&");
  }

  // Workaround for https://issues.apache.org/jira/browse/COUCHDB-577
  // See https://github.com/janl/mustache.js/issues/189
  var RegExp_test = RegExp.prototype.test;
  function testRegExp(re, string) {
    return RegExp_test.call(re, string);
  }

  var nonSpaceRe = /\S/;
  function isWhitespace(string) {
    return !testRegExp(nonSpaceRe, string);
  }

  var entityMap = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': '&quot;',
    "'": '&#39;',
    "/": '&#x2F;'
  };

  function escapeHtml(string) {
    return String(string).replace(/[&<>"'\/]/g, function (s) {
      return entityMap[s];
    });
  }

  var whiteRe = /\s*/;
  var spaceRe = /\s+/;
  var equalsRe = /\s*=/;
  var curlyRe = /\s*\}/;
  var tagRe = /#|\^|\/|>|\{|&|=|!/;

  /**
   * Breaks up the given `template` string into a tree of tokens. If the `tags`
   * argument is given here it must be an array with two string values: the
   * opening and closing tags used in the template (e.g. [ "<%", "%>" ]). Of
   * course, the default is to use mustaches (i.e. mustache.tags).
   *
   * A token is an array with at least 4 elements. The first element is the
   * mustache symbol that was used inside the tag, e.g. "#" or "&". If the tag
   * did not contain a symbol (i.e. {{myValue}}) this element is "name". For
   * all text that appears outside a symbol this element is "text".
   *
   * The second element of a token is its "value". For mustache tags this is
   * whatever else was inside the tag besides the opening symbol. For text tokens
   * this is the text itself.
   *
   * The third and fourth elements of the token are the start and end indices,
   * respectively, of the token in the original template.
   *
   * Tokens that are the root node of a subtree contain two more elements: 1) an
   * array of tokens in the subtree and 2) the index in the original template at
   * which the closing tag for that section begins.
   */
  function parseTemplate(template, tags) {
    if (!template)
      return [];

    var sections = [];     // Stack to hold section tokens
    var tokens = [];       // Buffer to hold the tokens
    var spaces = [];       // Indices of whitespace tokens on the current line
    var hasTag = false;    // Is there a {{tag}} on the current line?
    var nonSpace = false;  // Is there a non-space char on the current line?

    // Strips all whitespace tokens array for the current line
    // if there was a {{#tag}} on it and otherwise only space.
    function stripSpace() {
      if (hasTag && !nonSpace) {
        while (spaces.length)
          delete tokens[spaces.pop()];
      } else {
        spaces = [];
      }

      hasTag = false;
      nonSpace = false;
    }

    var openingTagRe, closingTagRe, closingCurlyRe;
    function compileTags(tags) {
      if (typeof tags === 'string')
        tags = tags.split(spaceRe, 2);

      if (!isArray(tags) || tags.length !== 2)
        throw new Error('Invalid tags: ' + tags);

      openingTagRe = new RegExp(escapeRegExp(tags[0]) + '\\s*');
      closingTagRe = new RegExp('\\s*' + escapeRegExp(tags[1]));
      closingCurlyRe = new RegExp('\\s*' + escapeRegExp('}' + tags[1]));
    }

    compileTags(tags || mustache.tags);

    var scanner = new Scanner(template);

    var start, type, value, chr, token, openSection;
    while (!scanner.eos()) {
      start = scanner.pos;

      // Match any text between tags.
      value = scanner.scanUntil(openingTagRe);

      if (value) {
        for (var i = 0, valueLength = value.length; i < valueLength; ++i) {
          chr = value.charAt(i);

          if (isWhitespace(chr)) {
            spaces.push(tokens.length);
          } else {
            nonSpace = true;
          }

          tokens.push([ 'text', chr, start, start + 1 ]);
          start += 1;

          // Check for whitespace on the current line.
          if (chr === '\n')
            stripSpace();
        }
      }

      // Match the opening tag.
      if (!scanner.scan(openingTagRe))
        break;

      hasTag = true;

      // Get the tag type.
      type = scanner.scan(tagRe) || 'name';
      scanner.scan(whiteRe);

      // Get the tag value.
      if (type === '=') {
        value = scanner.scanUntil(equalsRe);
        scanner.scan(equalsRe);
        scanner.scanUntil(closingTagRe);
      } else if (type === '{') {
        value = scanner.scanUntil(closingCurlyRe);
        scanner.scan(curlyRe);
        scanner.scanUntil(closingTagRe);
        type = '&';
      } else {
        value = scanner.scanUntil(closingTagRe);
      }

      // Match the closing tag.
      if (!scanner.scan(closingTagRe))
        throw new Error('Unclosed tag at ' + scanner.pos);

      token = [ type, value, start, scanner.pos ];
      tokens.push(token);

      if (type === '#' || type === '^') {
        sections.push(token);
      } else if (type === '/') {
        // Check section nesting.
        openSection = sections.pop();

        if (!openSection)
          throw new Error('Unopened section "' + value + '" at ' + start);

        if (openSection[1] !== value)
          throw new Error('Unclosed section "' + openSection[1] + '" at ' + start);
      } else if (type === 'name' || type === '{' || type === '&') {
        nonSpace = true;
      } else if (type === '=') {
        // Set the tags for the next time around.
        compileTags(value);
      }
    }

    // Make sure there are no open sections when we're done.
    openSection = sections.pop();

    if (openSection)
      throw new Error('Unclosed section "' + openSection[1] + '" at ' + scanner.pos);

    return nestTokens(squashTokens(tokens));
  }

  /**
   * Combines the values of consecutive text tokens in the given `tokens` array
   * to a single token.
   */
  function squashTokens(tokens) {
    var squashedTokens = [];

    var token, lastToken;
    for (var i = 0, numTokens = tokens.length; i < numTokens; ++i) {
      token = tokens[i];

      if (token) {
        if (token[0] === 'text' && lastToken && lastToken[0] === 'text') {
          lastToken[1] += token[1];
          lastToken[3] = token[3];
        } else {
          squashedTokens.push(token);
          lastToken = token;
        }
      }
    }

    return squashedTokens;
  }

  /**
   * Forms the given array of `tokens` into a nested tree structure where
   * tokens that represent a section have two additional items: 1) an array of
   * all tokens that appear in that section and 2) the index in the original
   * template that represents the end of that section.
   */
  function nestTokens(tokens) {
    var nestedTokens = [];
    var collector = nestedTokens;
    var sections = [];

    var token, section;
    for (var i = 0, numTokens = tokens.length; i < numTokens; ++i) {
      token = tokens[i];

      switch (token[0]) {
      case '#':
      case '^':
        collector.push(token);
        sections.push(token);
        collector = token[4] = [];
        break;
      case '/':
        section = sections.pop();
        section[5] = token[2];
        collector = sections.length > 0 ? sections[sections.length - 1][4] : nestedTokens;
        break;
      default:
        collector.push(token);
      }
    }

    return nestedTokens;
  }

  /**
   * A simple string scanner that is used by the template parser to find
   * tokens in template strings.
   */
  function Scanner(string) {
    this.string = string;
    this.tail = string;
    this.pos = 0;
  }

  /**
   * Returns `true` if the tail is empty (end of string).
   */
  Scanner.prototype.eos = function () {
    return this.tail === "";
  };

  /**
   * Tries to match the given regular expression at the current position.
   * Returns the matched text if it can match, the empty string otherwise.
   */
  Scanner.prototype.scan = function (re) {
    var match = this.tail.match(re);

    if (!match || match.index !== 0)
      return '';

    var string = match[0];

    this.tail = this.tail.substring(string.length);
    this.pos += string.length;

    return string;
  };

  /**
   * Skips all text until the given regular expression can be matched. Returns
   * the skipped string, which is the entire tail if no match can be made.
   */
  Scanner.prototype.scanUntil = function (re) {
    var index = this.tail.search(re), match;

    switch (index) {
    case -1:
      match = this.tail;
      this.tail = "";
      break;
    case 0:
      match = "";
      break;
    default:
      match = this.tail.substring(0, index);
      this.tail = this.tail.substring(index);
    }

    this.pos += match.length;

    return match;
  };

  /**
   * Represents a rendering context by wrapping a view object and
   * maintaining a reference to the parent context.
   */
  function Context(view, parentContext) {
    this.view = view;
    this.cache = { '.': this.view };
    this.parent = parentContext;
  }

  /**
   * Creates a new context using the given view with this context
   * as the parent.
   */
  Context.prototype.push = function (view) {
    return new Context(view, this);
  };

  /**
   * Returns the value of the given name in this context, traversing
   * up the context hierarchy if the value is absent in this context's view.
   */
  Context.prototype.lookup = function (name) {
    var cache = this.cache;

    var value;
    if (name in cache) {
      value = cache[name];
    } else {
      var context = this, names, index, lookupHit = false;

      while (context) {
        if (name.indexOf('.') > 0) {
          value = context.view;
          names = name.split('.');
          index = 0;

          /**
           * Using the dot notion path in `name`, we descend through the
           * nested objects.
           *
           * To be certain that the lookup has been successful, we have to
           * check if the last object in the path actually has the property
           * we are looking for. We store the result in `lookupHit`.
           *
           * This is specially necessary for when the value has been set to
           * `undefined` and we want to avoid looking up parent contexts.
           **/
          while (value != null && index < names.length) {
            if (index === names.length - 1 && value != null)
              lookupHit = (typeof value === 'object') &&
                value.hasOwnProperty(names[index]);
            value = value[names[index++]];
          }
        } else if (context.view != null && typeof context.view === 'object') {
          value = context.view[name];
          lookupHit = context.view.hasOwnProperty(name);
        }

        if (lookupHit)
          break;

        context = context.parent;
      }

      cache[name] = value;
    }

    if (isFunction(value))
      value = value.call(this.view);

    return value;
  };

  /**
   * A Writer knows how to take a stream of tokens and render them to a
   * string, given a context. It also maintains a cache of templates to
   * avoid the need to parse the same template twice.
   */
  function Writer() {
    this.cache = {};
  }

  /**
   * Clears all cached templates in this writer.
   */
  Writer.prototype.clearCache = function () {
    this.cache = {};
  };

  /**
   * Parses and caches the given `template` and returns the array of tokens
   * that is generated from the parse.
   */
  Writer.prototype.parse = function (template, tags) {
    var cache = this.cache;
    var tokens = cache[template];

    if (tokens == null)
      tokens = cache[template] = parseTemplate(template, tags);

    return tokens;
  };

  /**
   * High-level method that is used to render the given `template` with
   * the given `view`.
   *
   * The optional `partials` argument may be an object that contains the
   * names and templates of partials that are used in the template. It may
   * also be a function that is used to load partial templates on the fly
   * that takes a single argument: the name of the partial.
   */
  Writer.prototype.render = function (template, view, partials) {
    var tokens = this.parse(template);
    var context = (view instanceof Context) ? view : new Context(view);
    return this.renderTokens(tokens, context, partials, template);
  };

  /**
   * Low-level method that renders the given array of `tokens` using
   * the given `context` and `partials`.
   *
   * Note: The `originalTemplate` is only ever used to extract the portion
   * of the original template that was contained in a higher-order section.
   * If the template doesn't use higher-order sections, this argument may
   * be omitted.
   */
  Writer.prototype.renderTokens = function (tokens, context, partials, originalTemplate) {
    var buffer = '';

    var token, symbol, value;
    for (var i = 0, numTokens = tokens.length; i < numTokens; ++i) {
      value = undefined;
      token = tokens[i];
      symbol = token[0];

      if (symbol === '#') value = this._renderSection(token, context, partials, originalTemplate);
      else if (symbol === '^') value = this._renderInverted(token, context, partials, originalTemplate);
      else if (symbol === '>') value = this._renderPartial(token, context, partials, originalTemplate);
      else if (symbol === '&') value = this._unescapedValue(token, context);
      else if (symbol === 'name') value = this._escapedValue(token, context);
      else if (symbol === 'text') value = this._rawValue(token);

      if (value !== undefined)
        buffer += value;
    }

    return buffer;
  };

  Writer.prototype._renderSection = function (token, context, partials, originalTemplate) {
    var self = this;
    var buffer = '';
    var value = context.lookup(token[1]);

    // This function is used to render an arbitrary template
    // in the current context by higher-order sections.
    function subRender(template) {
      return self.render(template, context, partials);
    }

    if (!value) return;

    if (isArray(value)) {
      for (var j = 0, valueLength = value.length; j < valueLength; ++j) {
        buffer += this.renderTokens(token[4], context.push(value[j]), partials, originalTemplate);
      }
    } else if (typeof value === 'object' || typeof value === 'string' || typeof value === 'number') {
      buffer += this.renderTokens(token[4], context.push(value), partials, originalTemplate);
    } else if (isFunction(value)) {
      if (typeof originalTemplate !== 'string')
        throw new Error('Cannot use higher-order sections without the original template');

      // Extract the portion of the original template that the section contains.
      value = value.call(context.view, originalTemplate.slice(token[3], token[5]), subRender);

      if (value != null)
        buffer += value;
    } else {
      buffer += this.renderTokens(token[4], context, partials, originalTemplate);
    }
    return buffer;
  };

  Writer.prototype._renderInverted = function(token, context, partials, originalTemplate) {
    var value = context.lookup(token[1]);

    // Use JavaScript's definition of falsy. Include empty arrays.
    // See https://github.com/janl/mustache.js/issues/186
    if (!value || (isArray(value) && value.length === 0))
      return this.renderTokens(token[4], context, partials, originalTemplate);
  };

  Writer.prototype._renderPartial = function(token, context, partials) {
    if (!partials) return;

    var value = isFunction(partials) ? partials(token[1]) : partials[token[1]];
    if (value != null)
      return this.renderTokens(this.parse(value), context, partials, value);
  };

  Writer.prototype._unescapedValue = function(token, context) {
    var value = context.lookup(token[1]);
    if (value != null)
      return value;
  };

  Writer.prototype._escapedValue = function(token, context) {
    var value = context.lookup(token[1]);
    if (value != null)
      return mustache.escape(value);
  };

  Writer.prototype._rawValue = function(token) {
    return token[1];
  };

  mustache.name = "mustache.js";
  mustache.version = "2.0.0";
  mustache.tags = [ "{{", "}}" ];

  // All high-level mustache.* functions use this writer.
  var defaultWriter = new Writer();

  /**
   * Clears all cached templates in the default writer.
   */
  mustache.clearCache = function () {
    return defaultWriter.clearCache();
  };

  /**
   * Parses and caches the given template in the default writer and returns the
   * array of tokens it contains. Doing this ahead of time avoids the need to
   * parse templates on the fly as they are rendered.
   */
  mustache.parse = function (template, tags) {
    return defaultWriter.parse(template, tags);
  };

  /**
   * Renders the `template` with the given `view` and `partials` using the
   * default writer.
   */
  mustache.render = function (template, view, partials) {
    return defaultWriter.render(template, view, partials);
  };

  // This is here for backwards compatibility with 0.4.x.
  mustache.to_html = function (template, view, partials, send) {
    var result = mustache.render(template, view, partials);

    if (isFunction(send)) {
      send(result);
    } else {
      return result;
    }
  };

  // Export the escaping function so that the user may override it.
  // See https://github.com/janl/mustache.js/issues/244
  mustache.escape = escapeHtml;

  // Export these mainly for testing, but also for advanced usage.
  mustache.Scanner = Scanner;
  mustache.Context = Context;
  mustache.Writer = Writer;

}));

/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/jquery-debounce/jquery-debounce.js
**/
/**
 * 08-04-2016
 * jqUI Plugin Template
 * ~~ Scott Johnson
 */

/** List jshint ignore directives here. **/
/* global alert:false */
/* global clearTimeout:false */
/* global setTimeout:false */
/* global $:false */
/* global cTemplate:false */

(function( $ ){



   /**
    * Allows a function to be called only ONCE then stops all subsequent
    * requests within a given timeframe.
    * @param  {number} nMsWait    Number of seconds to wait before allowing more requests.
    * @param  {function} fnCallback The function to trigger.
    * @return {function}            The function returned.
    */
   //$.debounce = function( nMsWait, context, fnCallback, aParams ) {
   $.debounce = function( fnCallback, nMsWait, context, aParams ) {


      var nTimeout;
      var aArguments;

      //if( typeof context == 'function' ){
      //	aParams = fnCallback;
      //	fnCallback = context;
      //	context = null;
      //}

      aParams = aParams || [];
      nMsWait = nMsWait || 0;

      var doCallback = function(){
         clearTimeout( nTimeout );
         aArguments = [].slice.call( aArguments ).concat( aParams );
         fnCallback.apply( context, aArguments );
      };// /doCallback()

      return function(){
         aArguments = arguments;
         context = context || this;
         clearTimeout( nTimeout );
         nTimeout = setTimeout( doCallback, nMsWait );
      };
   };// /debounce()


}( jQuery ));


/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-state/ui-state.js
**/
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

/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-template/ui-template.js
**/
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

/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-dialog-confirm/build/ui-dialog-confirm.js
**/
(function( window, undefined ){

/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-dialog-confirm/temp/ui-dialog-confirm-htm.js
**/
var cTemplate='<div class="dialog-confirm"> {{#error}} <div class="callout alert"> {{error}} </div> {{/error}} {{#wait}} <div class="callout primary"> {{wait}} </div> {{/wait}} {{^error}} {{^wait}} <div class="warning-banner"> Please confirm! </div> <p> {{message}} </p> <button class="dialog-confirm__btn-cancel action-button"> {{text_cancel}} </button> <button class="dialog-confirm__btn-ok action-button"> {{text_confirm}} </button> {{/wait}} {{/error}} </div>';
/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-dialog-confirm/src/ui-dialog-confirm.js
**/
/**
 * 01-28-2019
 * The best app ever.
 * Check for instance using $( el ).data( 'uiDialogConfirm' );
 * ~~ Scott Johnson
 */


/** List jshint ignore directives here. **/
/* jshint browser:true */
/* global jQuery:false */
/* global cTemplate:false */

/** List jshint ignore directives here. **/
(function( $ ){

   $.widget( 'ui.dialogConfirm', {
      //_id: null,
      //_lastErrors: null,
      _invalidateTimeout: 0,

      options: {
         message: null,
         text_confirm: 'OK',
         text_cancel: 'Cancel',
         on_cancel_click: null,
         on_confirm_click: null
      },
      _create: function() {
         //this._id = Math.round( Math.random()*10000000 );
         //this._lastErrors = [];
         this.element.addClass( 'ui-widget-dialog-confirm' );
         this.element.template({
            renderOnInit: false,
            template: cTemplate,
            state: {
               message: this.options.message,
               text_confirm: this.options.text_confirm,
               text_cancel: this.options.text_cancel,
               error: null,
               wait: null
            },
            beforeRender: $.proxy( this._beforeRender, this ),
            onRender: $.proxy( this._afterRender, this )
         });

         /** 
          * This is how to set a deferred event listener that will automatically
          * be destroyed when the widget is destroyed.
          */
         this.element.on( 'click.dialog-confirm', '.dialog-confirm__btn-cancel', {self:this}, this._on_cancel_click );
         this.element.on( 'click.dialog-confirm', '.dialog-confirm__btn-ok', {self:this}, this._on_confirm_click );

         

         /*
          * Make sure render() is always called within the context/scope of
          * this widget.
          */
         this.render = $.proxy( this.render, this );
         this.render();
      },// /_create()

      _invalidate: function( undefined ) {
         clearTimeout( this._invalidateTimeout );
         this._invalidateTimeout = setTimeout( this.render, 20 );
      },// /_invalidate()

      render: function( undefined ) {
         clearTimeout( this._invalidateTimeout );
         this.element.template( 'render' );
      },// /render()

      /**
       * This method allows you to undo anything that was done during the
       * previous render.
       */
      _beforeRender: function( undefined ) {
      },// /_beforeRender()

      /**
       * This method allows you to initialize widgets, bind events, or do
       * anything else necessary after the new html has been rendered.
       */
      _afterRender: function( undefined ) {
         var oState = this.getState();

         // Trigger an event.
         this._triggerEvent( 'afterRender', { widget:this } );
      },// /_afterRender()
      
      _on_cancel_click: function( event ) {
         var self = event.data.self;

         // Trigger an event.
         self._triggerEvent( 'clickCancel', { widget:self } );
      },// /_on_cancel_click()
      
      _on_confirm_click: function( event ) {
         var self = event.data.self;

         // Trigger an event.
         self._triggerEvent( 'clickConfirm', { widget:self } );
      },// /_on_confirm_click()

      /**
       * This method allows you to call a method listening to this element
       * only as well as trigger a bubbling event.
       */
      _triggerEvent: function( cType, oData ){
         var fnMethod;
         var cFullEventType = ( this.widgetEventPrefix + cType ).toLowerCase();
         var oEvent = $.Event( cFullEventType );

         switch( cType ){
         case 'clickCancel':
            fnMethod = this.options.on_cancel_click;
            break;

         case 'clickConfirm':
            fnMethod = this.options.on_confirm_click;
            break;

         }// /switch()


         if ( fnMethod ) {
            fnMethod( oEvent, oData );
         }

         //this._trigger( cType, oEvent, oData );
         this.element.trigger( oEvent, oData );
      },// /_triggerEvent()

      _showError: function( cMessage ){
         this.setState({
            error: cMessage,
            wait: null
         });

      },// /_showError()

      _showWait: function( cMessage ){
         this.setState({
            error: null,
            wait: cMessage
         });

      },// /_showWait()

      /**
       * This is a proxy method that allows you to get the template state.
       */
      getState: function(){
         return this.element.template( 'getState' );
      },// /getState()

      /**
       * This is a proxy method that allows you to change the template state.
       * @param  {object} oState      Object with changed state values.
       * @param  {boolean} lRender   Whether or not to render changes.
       * @param  {boolean} lDiff      Whether or not to check for changes.
       *                         Useful for saving cpu cycles on changes
       *                         that we don't care about.
       */
      setState: function( oState, lRender, lDiff ){
         this.element.template( 'setState', oState, lRender, lDiff );
      },// /setState()

      _destroy: function(){
         // Undo everything.
         this._beforeRender();
         this.element.off( '.dialog-confirm' );
         this.element.template( 'destroy' );
         this.element.removeClass( 'ui-widget-dialog-confirm' );
      }// /_destroy()
   });

}( jQuery ));

}( window ));
/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-error-banner/build/ui-error-banner.js
**/
(function( window, undefined ){

/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-error-banner/temp/ui-error-banner-htm.js
**/
var cTemplate='{{#message}} <div class="error-banner"> {{message}} </div> {{/message}}';
/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-error-banner/src/ui-error-banner.js
**/
/**
 * 01-25-2019
 * The best app ever.
 * Check for instance using $( el ).data( 'uiErrorBanner' );
 * ~~ Scott Johnson
 */


/** List jshint ignore directives here. **/
/* jshint browser:true */
/* global jQuery:false */
/* global cTemplate:false */

/** List jshint ignore directives here. **/
(function( $ ){

   $.widget( 'ui.errorBanner', {
      //_id: null,
      //_lastErrors: null,
      _invalidateTimeout: 0,

      options: {
         message: null
      },
      _create: function() {
         //this._id = Math.round( Math.random()*10000000 );
         //this._lastErrors = [];
         this.element.addClass( 'ui-widget-error-banner' );
         this.element.template({
            renderOnInit: false,
            template: cTemplate,
            state: {
               message: this.options.message
            },
            beforeRender: $.proxy( this._beforeRender, this ),
            onRender: $.proxy( this._afterRender, this )
         });

         /** 
          * This is how to set a deferred event listener that will automatically
          * be destroyed when the widget is destroyed.
          */
         // this.element.on( 'EVENT.error-banner', '.CLASSNAME', {self:this}, this._HANDLERMETHOD );

         /*
          * Make sure render() is always called within the context/scope of
          * this widget.
          */
         this.render = $.proxy( this.render, this );
         this.render();
      },// /_create()

      _invalidate: function( undefined ) {
         clearTimeout( this._invalidateTimeout );
         this._invalidateTimeout = setTimeout( this.render, 20 );
      },// /_invalidate()

      render: function( undefined ) {
         clearTimeout( this._invalidateTimeout );
         this.element.template( 'render' );
      },// /render()

      /**
       * This method allows you to undo anything that was done during the
       * previous render.
       */
      _beforeRender: function( undefined ) {
      },// /_beforeRender()

      /**
       * This method allows you to initialize widgets, bind events, or do
       * anything else necessary after the new html has been rendered.
       */
      _afterRender: function( undefined ) {
         var oState = this.getState();

         // Trigger an event.
         this._triggerEvent( 'afterRender', { widget:this } );
      },// /_afterRender()

      /**
       * This method allows you to call a method listening to this element
       * only as well as trigger a bubbling event.
       */
      _triggerEvent: function( cType, oData ){
         var fnMethod;
         var cFullEventType = ( this.widgetEventPrefix + cType ).toLowerCase();
         var oEvent = $.Event( cFullEventType );

         switch( cType ){
         case 'anything':
            fnMethod = this.options.onEvent;
            break;

         }// /switch()


         if ( fnMethod ) {
            fnMethod( oEvent, oData );
         }

         //this._trigger( cType, oEvent, oData );
         this.element.trigger( oEvent, oData );
      },// /_triggerEvent()

      /**
       * This is a proxy method that allows you to get the template state.
       */
      getState: function(){
         return this.element.template( 'getState' );
      },// /getState()

      /**
       * This is a proxy method that allows you to change the template state.
       * @param  {object} oState      Object with changed state values.
       * @param  {boolean} lRender   Whether or not to render changes.
       * @param  {boolean} lDiff      Whether or not to check for changes.
       *                         Useful for saving cpu cycles on changes
       *                         that we don't care about.
       */
      setState: function( oState, lRender, lDiff ){
         this.element.template( 'setState', oState, lRender, lDiff );
      },// /setState()

      set_message: function( c_msg ){
         this.setState({
            message: c_msg
         });
      },// /set_message()

      clear: function(){
         this.setState({
            message: null
         });
      },// /clear()

      _destroy: function(){
         // Undo everything.
         this._beforeRender();
         this.element.off( '.error-banner' );
         this.element.template( 'destroy' );
         this.element.removeClass( 'ui-widget-error-banner' );
      }// /_destroy()
   });

}( jQuery ));

}( window ));
/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-user-list/build/ui-user-list.js
**/
(function( window, undefined ){

/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-user-list/temp/ui-user-list-htm.js
**/
var cTemplate='<div class="user-list"> {{#error}} <div class="callout alert"> {{error}} </div> {{/error}} {{#wait}} <div class="callout primary"> {{wait}} </div> {{/wait}} {{^error}} {{^wait}} <button class="user-list__btn-add action-button"> <i class="icon-plus"></i> Add an agent </button> {{^users}} <div class="info-banner"> No agents found. Add some! </div> {{/users}} <div class="ticket-options-plugin__agents"> {{#users}} <div class="ticket-options-plugin__agent"> <i class="user-list__btn-remove icon-remove-circle" data-staff_id="{{staff_id}}" title="Remove {{name}}"></i> <img class="ticket-options-plugin__agent-avatar" src="{{avatar}}"/> <div> <div class="ticket-options-plugin__agent-name ticket-options-plugin__agent-detail"> {{name}} </div> <div class="ticket-options-plugin__agent-email ticket-options-plugin__agent-detail"> {{email}} </div> <div class="ticket-options-plugin__agent-phone ticket-options-plugin__agent-detail"> {{#mobile}} {{mobile}} {{/mobile}} {{^mobile}} {{phone}} {{/mobile}} </div> <div class="ticket-options-plugin__agent-dept ticket-options-plugin__agent-detail"> {{department}} </div> </div> </div> {{/users}} </div> {{/wait}} {{/error}} </div>';
/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-user-list/src/ui-user-list.js
**/
/**
 * 01-24-2019
 * The best app ever.
 * Check for instance using $( el ).data( 'uiUserList' );
 * ~~ Scott Johnson
 */


/** List jshint ignore directives here. **/
/* jshint browser:true */
/* global jQuery:false */
/* global cTemplate:false */

/** List jshint ignore directives here. **/
(function( $ ){

   $.widget( 'ui.userList', {
      //_id: null,
      //_lastErrors: null,
      _invalidateTimeout: 0,
      _user_list: null,

      options: {
         users: null,
         on_remove_click: null,
         on_add_click: null
      },
      _create: function() {
         //this._id = Math.round( Math.random()*10000000 );
         this._user_list = this.options.users;
         this.element.addClass( 'ui-widget-user-list' );
         this.element.template({
            renderOnInit: false,
            template: cTemplate,
            state: {
               users: this.options.users,
               error: null,
               wait: null
            },
            beforeRender: $.proxy( this._beforeRender, this ),
            onRender: $.proxy( this._afterRender, this )
         });

         /** 
          * This is how to set a deferred event listener that will automatically
          * be destroyed when the widget is destroyed.
          */
         this.element.on( 'click.user-list', '.user-list__btn-add', {self:this}, this._on_add_click );
         this.element.on( 'click.user-list', '.user-list__btn-remove', {self:this}, this._on_remove_click );

         /*
          * Make sure render() is always called within the context/scope of
          * this widget.
          */
         this.render = $.proxy( this.render, this );
         this.render();
      },// /_create()

      _invalidate: function( undefined ) {
         clearTimeout( this._invalidateTimeout );
         this._invalidateTimeout = setTimeout( this.render, 20 );
      },// /_invalidate()

      render: function( undefined ) {
         clearTimeout( this._invalidateTimeout );
         this.element.template( 'render' );
      },// /render()

      /**
       * This method allows you to undo anything that was done during the
       * previous render.
       */
      _beforeRender: function( undefined ) {
      },// /_beforeRender()

      /**
       * This method allows you to initialize widgets, bind events, or do
       * anything else necessary after the new html has been rendered.
       */
      _afterRender: function( undefined ) {
         /* var oState = this.getState(); */

         // Trigger an event.
         this._triggerEvent( 'afterRender', { widget:this } );
      },// /_afterRender()

      _on_add_click: function( event ){
         var self = event.data.self;

         // Trigger an event.
         self._triggerEvent( 'addClick', { widget:self } );
      },// /_on_add_click()

      _on_remove_click: function( event ){
         var self = event.data.self;
         var i, l, n_user_id = $(this).data( 'staff_id' );
         var o_user = null;


         // Loop over each user to find this staff_id.
         for( i = 0, l = self._user_list.length; i < l; i++ )
         {
            if( self._user_list[ i ].staff_id  == n_user_id ) {
               o_user = self._user_list[ i ];
            }
         }// /for()

         // Trigger an event.
         self._triggerEvent( 'removeClick', { widget:self, user: o_user } );
      },// /_on_remove_click()


      

      /**
       * This method allows you to call a method listening to this element
       * only as well as trigger a bubbling event.
       */
      _triggerEvent: function( cType, oData ){
         var fnMethod;
         var cFullEventType = ( this.widgetEventPrefix + cType ).toLowerCase();
         var oEvent = $.Event( cFullEventType );

         switch( cType ){
         case 'addClick':
            fnMethod = this.options.on_add_click;
            break;

         case 'removeClick':
            fnMethod = this.options.on_remove_click;
            break;

         }// /switch()


         if ( fnMethod ) {
            fnMethod( oEvent, oData );
         }

         //this._trigger( cType, oEvent, oData );
         this.element.trigger( oEvent, oData );
      },// /_triggerEvent()

      _showError: function( cMessage ){
         this.setState({
            error: cMessage,
            wait: null
         });

      },// /_showError()

      _showWait: function( cMessage ){
         this.setState({
            error: null,
            wait: cMessage
         });

      },// /_showWait()

      /**
       * This is a proxy method that allows you to get the template state.
       */
      getState: function(){
         return this.element.template( 'getState' );
      },// /getState()

      /**
       * This is a proxy method that allows you to change the template state.
       * @param  {object} oState      Object with changed state values.
       * @param  {boolean} lRender   Whether or not to render changes.
       * @param  {boolean} lDiff      Whether or not to check for changes.
       *                         Useful for saving cpu cycles on changes
       *                         that we don't care about.
       */
      setState: function( oState, lRender, lDiff ){
         this.element.template( 'setState', oState, lRender, lDiff );
      },// /setState()

      _destroy: function(){
         // Undo everything.
         this._beforeRender();
         this.element.off( '.user-list' );
         this.element.template( 'destroy' );
         this.element.removeClass( 'ui-widget-user-list' );
      }// /_destroy()
   });

}( jQuery ));

}( window ));
/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-agent-search/build/ui-agent-search.js
**/
(function( window, undefined ){

/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-agent-search/temp/ui-agent-search-htm.js
**/
var cTemplate='<div class="agent-search"> {{#error}} <div class="callout alert"> {{error}} </div> {{/error}} {{#wait}} <div class="callout primary"> {{wait}} </div> {{/wait}} {{^error}} {{^wait}} <form class="agent-search__form"> <label for="query_{{id}}">Search term:</label> <input class="agent-search__form-query" type="text" name="search_query" placeholder="Type a username or id." id="query_{{id}}"/> </form> <div class="agent-search__results"></div> {{/wait}} {{/error}} </div>';
/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-agent-search/temp/ui-agent-search-htm-results.js
**/
var cResultsTemplate='<div class="agent-search-results"> {{#error}} <div class="error-banner"> {{error}} </div> {{/error}} {{#wait}} <div class="warning-banner"> {{wait}} </div> {{/wait}} {{^error}} {{^wait}} <div class="agent-search-results__agents"> {{#results}} <div class="agent-search-results__agent" data-staff_id="{{staff_id}}"> <div class="agent-search-results__agent-button"> <i class="icon-plus"></i> </div> <div class="agent-search-results__agent-card"> <img class="ticket-options-plugin__agent-avatar" src="{{avatar}}"/> <div> <div class="agent-search-results__agent-name agent-search-results__agent-detail"> {{name}} </div> <div class="agent-search-results__agent-email agent-search-results__agent-detail"> {{email}} </div> </div> </div> </div> {{/results}} </div> {{/wait}} {{/error}} </div>';
/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-agent-search/src/ui-agent-search.js
**/
/**
 * 01-25-2019
 * The best app ever.
 * Check for instance using $( el ).data( 'uiAgentSearch' );
 * ~~ Scott Johnson
 */


/** List jshint ignore directives here. **/
/* jshint browser:true */
/* global jQuery:false */
/* global cTemplate:false */
/* global cResultsTemplate:false */


/** List jshint ignore directives here. **/
(function( $ ){

   $.widget( 'ui.agentSearch', {
      _id: null,
      //_lastErrors: null,
      _invalidateTimeout: 0,
      _$results: null,
      _$form: null,
      _$query: null,
      _last_query: null,

      options: {
         on_agent_click: null
      },
      _create: function() {
         this._id = Math.round( Math.random()*10000000 );
         //this._lastErrors = [];
         this.element.addClass( 'ui-widget-agent-search' );
         this.element.template({
            renderOnInit: false,
            template: cTemplate,
            state: {
               id: this._id,
               error: null,
               wait: null
            },
            beforeRender: $.proxy( this._beforeRender, this ),
            onRender: $.proxy( this._afterRender, this )
         });

         /** 
          * This is how to set a deferred event listener that will automatically
          * be destroyed when the widget is destroyed.
          */
         this.element.on( 'keyup.agent-search', '.agent-search__form-query', {self:this}, this._on_search_change );
         this.element.on( 'change.agent-search', '.agent-search__form-query', {self:this}, this._on_search_change );
         this.element.on( 'submit.agent-search', '.agent-search__form', {self:this}, this._on_submit );
         
         // params: fnCallback, nMsWait, context, aParams
         this._defer_search = $.debounce( this._defer_search, 500, this, [] );

         /*
          * Make sure render() is always called within the context/scope of
          * this widget.
          */
         this.render = $.proxy( this.render, this );
         this.render();
      },// /_create()

      _invalidate: function( undefined ) {
         clearTimeout( this._invalidateTimeout );
         this._invalidateTimeout = setTimeout( this.render, 20 );
      },// /_invalidate()

      render: function( undefined ) {
         clearTimeout( this._invalidateTimeout );
         this.element.template( 'render' );
      },// /render()

      /**
       * This method allows you to undo anything that was done during the
       * previous render.
       */
      _beforeRender: function( undefined ) {
         if( this._$results ){
            this._$results.agentSearchResults( 'destroy' );
         }

         this._$results = null;
         this._$form = null;
         this._$query = null;
      },// /_beforeRender()

      /**
       * This method allows you to initialize widgets, bind events, or do
       * anything else necessary after the new html has been rendered.
       */
      _afterRender: function( undefined ) {
         var oState = this.getState();

         this._$results = $( '.agent-search__results', this.element )
         .agentSearchResults({
            on_agent_click: this.options.on_agent_click
         });
         this._$form = $( '.agent-search__form', this.element );
         this._$query = $( '.agent-search__form-query', this.element );

         this._$query.focus();

         // Trigger an event.
         this._triggerEvent( 'afterRender', { widget:this } );
      },// /_afterRender()

      _on_submit: function( event ){
         var self = event.data.self;
         var c_value = self._$query[0].value;

         event.preventDefault();

         self._$query.blur();
         self._search( c_value );

      },// /_on_submit()

      _on_search_change: function( event ){
         var self = event.data.self;
         var c_value = this.value;


         self._search( c_value );

      },// /_on_search_change()

      _search: function( c_value ){
         var self = this;

         if( this._last_query == c_value ){
            return;
         }

         this._showWait( 'Searching...' );

         this._last_query = c_value;

         self._defer_search( c_value );

      },// /_search()

      _defer_search: function( c_value ){
         var self = this;

         self._search_now( c_value )
         .then(function( a_agents ){
            self._$results.agentSearchResults( 'update_results', a_agents, c_value );
         });

      },// /_defer_search()

      _search_now: function( c_query ){
         var deferred = $.Deferred();
         var self = this;

         this._showWait( 'Searching...' );

         console.log( '_search_now' );

         $.ajax({
            url: 'ajax.php/ticket_options/script/search_agents.php',
            method: 'get',
            type: 'json',
            dataType: 'json',
            data: {
               q: c_query
            }
         })
         .then(function( o_result, status, o_xhr ){
            if( o_result.error ) {
               return self._showError( o_result.error.message );
            }

            if( o_result.result == 'ok' )
            {
               deferred.resolve( o_result.agents );
               return;
            }

            debugger;
            
         })
         .fail(function( o_xhr, c_status, o_error  ){
            if( c_status == 'parsererror' )
            {
               return self._showError( 'get_ticket_agents failure: '.concat( o_error.message ) );
            }

            debugger;
         });

         return deferred.promise();
      },// /_search_now()

      /**
       * This method allows you to call a method listening to this element
       * only as well as trigger a bubbling event.
       */
      _triggerEvent: function( cType, oData ){
         var fnMethod;
         var cFullEventType = ( this.widgetEventPrefix + cType ).toLowerCase();
         var oEvent = $.Event( cFullEventType );

         switch( cType ){
         case 'anything':
            fnMethod = this.options.onEvent;
            break;

         }// /switch()


         if ( fnMethod ) {
            fnMethod( oEvent, oData );
         }

         //this._trigger( cType, oEvent, oData );
         this.element.trigger( oEvent, oData );
      },// /_triggerEvent()

      _showError: function( cMessage ){
         this.setState({
            error: cMessage,
            wait: null
         });

      },// /_showError()

      _showWait: function( cMessage ){
         this._$results.agentSearchResults( 'show_wait', cMessage );

      },// /_showWait()

      /**
       * This is a proxy method that allows you to get the template state.
       */
      getState: function(){
         return this.element.template( 'getState' );
      },// /getState()

      /**
       * This is a proxy method that allows you to change the template state.
       * @param  {object} oState      Object with changed state values.
       * @param  {boolean} lRender   Whether or not to render changes.
       * @param  {boolean} lDiff      Whether or not to check for changes.
       *                         Useful for saving cpu cycles on changes
       *                         that we don't care about.
       */
      setState: function( oState, lRender, lDiff ){
         this.element.template( 'setState', oState, lRender, lDiff );
      },// /setState()

      ///**
      // * This returns the data of the form.
      // */
      //getData: function(){
      //   var oState = this.getState();
      //   var oForm = $.deserialize( this._$form.serialize() );
      //
      //   return {
      //      somestring: oState.somestring,
      //      somedate: oState.somedate
      //   };
      //},// /getData()
      //
      ///**
      // * This validates the data of the form.
      // */
      //validate: function(){
      //   var oData = this.getData();
      //   this._lastErrors = [];
      //
      //   if( oData.somestring.length < 3 ){
      //      this._lastErrors.push({
      //         message: 'Somestring is NOT valid. Must be at least three characters.'
      //      });
      //   }
      //
      //   return this._lastErrors.length < 1;
      //},// /validate()
      //
      ///**
      // * This returns the last validation errors.
      // */
      //getErrors: function(){
      //   return this._lastErrors;
      //},// /getErrors()

      _destroy: function(){
         // Undo everything.
         this._beforeRender();
         this.element.off( '.agent-search' );
         this.element.template( 'destroy' );
         this.element.removeClass( 'ui-widget-agent-search' );
      }// /_destroy()
   });


   $.widget( 'ui.agentSearchResults', {
      //_id: null,
      //_lastErrors: null,
      _invalidateTimeout: 0,

      options: {
         results: null,
         on_agent_click: null
      },
      _create: function() {
         //this._id = Math.round( Math.random()*10000000 );
         //this._lastErrors = [];
         this.element.addClass( 'ui-widget-agent-search-results' );
         this.element.template({
            renderOnInit: false,
            template: cResultsTemplate,
            state: {
               results: this.options.results,
               error: null,
               wait: null
            },
            beforeRender: $.proxy( this._beforeRender, this ),
            onRender: $.proxy( this._afterRender, this )
         });

         /** 
          * This is how to set a deferred event listener that will automatically
          * be destroyed when the widget is destroyed.
          */
         this.element.on( 'click.agent-search-results', '.agent-search-results__agent', {self:this}, this._on_agent_click );

         /*
          * Make sure render() is always called within the context/scope of
          * this widget.
          */
         this.render = $.proxy( this.render, this );
         this.render();
      },// /_create()

      _invalidate: function( undefined ) {
         clearTimeout( this._invalidateTimeout );
         this._invalidateTimeout = setTimeout( this.render, 20 );
      },// /_invalidate()

      render: function( undefined ) {
         clearTimeout( this._invalidateTimeout );
         this.element.template( 'render' );
      },// /render()

      /**
       * This method allows you to undo anything that was done during the
       * previous render.
       */
      _beforeRender: function( undefined ) {
      },// /_beforeRender()

      /**
       * This method allows you to initialize widgets, bind events, or do
       * anything else necessary after the new html has been rendered.
       */
      _afterRender: function( undefined ) {
         var oState = this.getState();

         // Trigger an event.
         this._triggerEvent( 'afterRender', { widget:this } );
      },// /_afterRender()

      _on_agent_click: function( event ){
         var self = event.data.self;
         var n_staff_id = $(this).data( 'staff_id' );

         event.preventDefault();

         self._triggerEvent( 'agentClick', { widget:self, staff_id: n_staff_id } );
      },// /_on_agent_click()

      /**
       * This method allows you to call a method listening to this element
       * only as well as trigger a bubbling event.
       */
      _triggerEvent: function( cType, oData ){
         var fnMethod;
         var cFullEventType = ( this.widgetEventPrefix + cType ).toLowerCase();
         var oEvent = $.Event( cFullEventType );

         switch( cType ){
         case 'agentClick':
            fnMethod = this.options.on_agent_click;
            break;

         }// /switch()


         if ( fnMethod ) {
            fnMethod( oEvent, oData );
         }

         //this._trigger( cType, oEvent, oData );
         this.element.trigger( oEvent, oData );
      },// /_triggerEvent()

      _showError: function( cMessage ){
         this.setState({
            error: cMessage,
            wait: null
         });

      },// /_showError()

      _showWait: function( cMessage ){
         this.setState({
            error: null,
            wait: cMessage
         });

      },// /_showWait()

      /**
       * This is a proxy method that allows you to get the template state.
       */
      getState: function(){
         return this.element.template( 'getState' );
      },// /getState()

      /**
       * This is a proxy method that allows you to change the template state.
       * @param  {object} oState      Object with changed state values.
       * @param  {boolean} lRender   Whether or not to render changes.
       * @param  {boolean} lDiff      Whether or not to check for changes.
       *                         Useful for saving cpu cycles on changes
       *                         that we don't care about.
       */
      setState: function( oState, lRender, lDiff ){
         this.element.template( 'setState', oState, lRender, lDiff );
      },// /setState()

      update_results: function( a_results, c_term ){

         if( a_results.length < 1 ){
            this.show_wait( ''.concat( 'No results for "',c_term,'".' ) );
            return;
         }

         this.setState({
            results: a_results,
            error: null,
            wait: null
         });
      },// /update_results()

      show_error: function( cMessage ){
         this._showError( _showError );

      },// /show_error()

      show_wait: function( cMessage ){
         this._showWait( cMessage );

      },// /show_wait()

      ///**
      // * This returns the data of the form.
      // */
      //getData: function(){
      //   var oState = this.getState();
      //   var oForm = $.deserialize( this._$form.serialize() );
      //
      //   return {
      //      somestring: oState.somestring,
      //      somedate: oState.somedate
      //   };
      //},// /getData()
      //
      ///**
      // * This validates the data of the form.
      // */
      //validate: function(){
      //   var oData = this.getData();
      //   this._lastErrors = [];
      //
      //   if( oData.somestring.length < 3 ){
      //      this._lastErrors.push({
      //         message: 'Somestring is NOT valid. Must be at least three characters.'
      //      });
      //   }
      //
      //   return this._lastErrors.length < 1;
      //},// /validate()
      //
      ///**
      // * This returns the last validation errors.
      // */
      //getErrors: function(){
      //   return this._lastErrors;
      //},// /getErrors()

      _destroy: function(){
         // Undo everything.
         this._beforeRender();
         this.element.off( '.agent-search-results' );
         this.element.template( 'destroy' );
         this.element.removeClass( 'ui-widget-agent-search-results' );
      }// /_destroy()
   });

}( jQuery ));

}( window ));
/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-show-agent-add/build/ui-show-agent-add.js
**/
(function( window, undefined ){

/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-show-agent-add/temp/ui-show-agent-add-htm.js
**/
var cTemplate='<div class="show-agent-add"> {{#error}} <div class="error-banner"> {{error}} </div> {{/error}} {{#wait}} <div class="info-banner"> {{wait}} </div> {{/wait}} {{^error}} {{^wait}} Hello, World! {{/wait}} {{/error}} </div>';
/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-show-agent-add/src/ui-show-agent-add.js
**/
/**
 * 01-28-2019
 * The best app ever.
 * Check for instance using $( el ).data( 'uiShowAgentAdd' );
 * ~~ Scott Johnson
 */


/** List jshint ignore directives here. **/
/* jshint browser:true */
/* global jQuery:false */
/* global cTemplate:false */

/** List jshint ignore directives here. **/
(function( $ ){

   $.widget( 'ui.showAgentAdd', {
      //_id: null,
      //_lastErrors: null,
      _invalidateTimeout: 0,

      options: {
         staff_id: null,
         ticket_id: null,
         on_response: null
      },
      _create: function() {
         var self = this;

         //this._id = Math.round( Math.random()*10000000 );
         //this._lastErrors = [];
         this.element.addClass( 'ui-widget-show-agent-add' );
         this.element.template({
            renderOnInit: false,
            template: cTemplate,
            state: {
               error: null,
               wait: null,
            },
            beforeRender: $.proxy( this._beforeRender, this ),
            onRender: $.proxy( this._afterRender, this )
         });

         /** 
          * This is how to set a deferred event listener that will automatically
          * be destroyed when the widget is destroyed.
          */
         // this.element.on( 'EVENT.show-agent-add', '.CLASSNAME', {self:this}, this._HANDLERMETHOD );

         /*
          * Make sure render() is always called within the context/scope of
          * this widget.
          */
         this.render = $.proxy( this.render, this );
         this._add_ticket_agent()
         .then(function( o_response ){
            self._triggerEvent( 'onResponse', { widget:self, response: o_response } );
         });
      },// /_create()

      _invalidate: function( undefined ) {
         clearTimeout( this._invalidateTimeout );
         this._invalidateTimeout = setTimeout( this.render, 20 );
      },// /_invalidate()

      render: function( undefined ) {
         clearTimeout( this._invalidateTimeout );
         this.element.template( 'render' );
      },// /render()

      /**
       * This method allows you to undo anything that was done during the
       * previous render.
       */
      _beforeRender: function( undefined ) {
      },// /_beforeRender()

      /**
       * This method allows you to initialize widgets, bind events, or do
       * anything else necessary after the new html has been rendered.
       */
      _afterRender: function( undefined ) {
         var oState = this.getState();

         // Trigger an event.
         this._triggerEvent( 'afterRender', { widget:this } );
      },// /_afterRender()

      _add_ticket_agent: function(){
         var deferred = $.Deferred();
         var self = this;
         var c_ticket_id = this.options.ticket_id;
         var n_staff_id = this.options.staff_id;

         this._showWait( ''.concat( 'Adding agent ',n_staff_id,' to ticket ',c_ticket_id,'...' ) );

         $.ajax({
            url: 'ajax.php/ticket_options/script/add_ticket_agent.php',
            method: 'post',
            type: 'json',
            dataType: 'json',
            data: {
               ticket_id: c_ticket_id,
               staff_id: n_staff_id
            }
         })
         .then(function( o_result, status, o_xhr ){

            if( o_result.error ) {
               return self._showError( o_result.error.message );
            }

            if( o_result.result == 'ok' )
            {
               deferred.resolve( o_result );
               return;
            }

         })
         .fail(function( o_xhr, c_status, o_error  ){
            if( c_status == 'parsererror' )
            {
               return self._showError( 'get_ticket_agent failure: '.concat( o_error.message ) );
            }

            debugger;
         });

         return deferred.promise();
      },// /_add_ticket_agent()

      /**
       * This method allows you to call a method listening to this element
       * only as well as trigger a bubbling event.
       */
      _triggerEvent: function( cType, oData ){
         var fnMethod;
         var cFullEventType = ( this.widgetEventPrefix + cType ).toLowerCase();
         var oEvent = $.Event( cFullEventType );

         switch( cType ){
         case 'onResponse':
            fnMethod = this.options.on_response;
            break;

         }// /switch()


         if ( fnMethod ) {
            fnMethod( oEvent, oData );
         }

         //this._trigger( cType, oEvent, oData );
         this.element.trigger( oEvent, oData );
      },// /_triggerEvent()

      _showError: function( cMessage ){
         this.setState({
            error: cMessage,
            wait: null
         });

      },// /_showError()

      _showWait: function( cMessage ){
         this.setState({
            error: null,
            wait: cMessage
         });

      },// /_showWait()

      /**
       * This is a proxy method that allows you to get the template state.
       */
      getState: function(){
         return this.element.template( 'getState' );
      },// /getState()

      /**
       * This is a proxy method that allows you to change the template state.
       * @param  {object} oState      Object with changed state values.
       * @param  {boolean} lRender   Whether or not to render changes.
       * @param  {boolean} lDiff      Whether or not to check for changes.
       *                         Useful for saving cpu cycles on changes
       *                         that we don't care about.
       */
      setState: function( oState, lRender, lDiff ){
         this.element.template( 'setState', oState, lRender, lDiff );
      },// /setState()

      _destroy: function(){
         // Undo everything.
         this._beforeRender();
         this.element.off( '.show-agent-add' );
         this.element.template( 'destroy' );
         this.element.removeClass( 'ui-widget-show-agent-add' );
      }// /_destroy()
   });

}( jQuery ));

}( window ));
/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-show-agent-remove/build/ui-show-agent-remove.js
**/
(function( window, undefined ){

/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-show-agent-remove/temp/ui-show-agent-remove-htm.js
**/
var cTemplate='<div class="show-agent-remove"> {{#error}} <div class="error-banner"> {{error}} </div> {{/error}} {{#wait}} <div class="info-banner"> {{wait}} </div> {{/wait}} {{^error}} {{^wait}} Hello, World! {{/wait}} {{/error}} </div>';
/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-show-agent-remove/src/ui-show-agent-remove.js
**/
/**
 * 01-28-2019
 * The best app ever.
 * Check for instance using $( el ).data( 'uiShowAgentRemove' );
 * ~~ Scott Johnson
 */


/** List jshint ignore directives here. **/
/* jshint browser:true */
/* global jQuery:false */
/* global cTemplate:false */

/** List jshint ignore directives here. **/
(function( $ ){

   $.widget( 'ui.showAgentRemove', {
      //_id: null,
      //_lastErrors: null,
      _invalidateTimeout: 0,

      options: {
         staff_id: null,
         ticket_id: null,
         on_response: null
      },
      _create: function() {
         var self = this;

         //this._id = Math.round( Math.random()*10000000 );
         //this._lastErrors = [];
         this.element.addClass( 'ui-widget-show-agent-remove' );
         this.element.template({
            renderOnInit: false,
            template: cTemplate,
            state: {
               error: null,
               wait: null
            },
            beforeRender: $.proxy( this._beforeRender, this ),
            onRender: $.proxy( this._afterRender, this )
         });

         /** 
          * This is how to set a deferred event listener that will automatically
          * be destroyed when the widget is destroyed.
          */
         // this.element.on( 'EVENT.show-agent-remove', '.CLASSNAME', {self:this}, this._HANDLERMETHOD );

         /*
          * Make sure render() is always called within the context/scope of
          * this widget.
          */
         this.render = $.proxy( this.render, this );
         this._remove_ticket_agent()
         .then(function( o_response ){
            self._triggerEvent( 'onResponse', { widget:self, response: o_response } );
         });
      },// /_create()

      _invalidate: function( undefined ) {
         clearTimeout( this._invalidateTimeout );
         this._invalidateTimeout = setTimeout( this.render, 20 );
      },// /_invalidate()

      render: function( undefined ) {
         clearTimeout( this._invalidateTimeout );
         this.element.template( 'render' );
      },// /render()

      /**
       * This method allows you to undo anything that was done during the
       * previous render.
       */
      _beforeRender: function( undefined ) {
      },// /_beforeRender()

      /**
       * This method allows you to initialize widgets, bind events, or do
       * anything else necessary after the new html has been rendered.
       */
      _afterRender: function( undefined ) {
         var oState = this.getState();

         // Trigger an event.
         this._triggerEvent( 'afterRender', { widget:this } );
      },// /_afterRender()

      _remove_ticket_agent: function(){
         var deferred = $.Deferred();
         var self = this;
         var c_ticket_id = this.options.ticket_id;
         var n_staff_id = this.options.staff_id;

         this._showWait( ''.concat( 'Removing agent ',n_staff_id,' to ticket ',c_ticket_id,'...' ) );

         $.ajax({
            url: 'ajax.php/ticket_options/script/remove_ticket_agent.php',
            method: 'post',
            type: 'json',
            dataType: 'json',
            data: {
               ticket_id: c_ticket_id,
               staff_id: n_staff_id
            }
         })
         .then(function( o_result, status, o_xhr ){

            if( o_result.error ) {
               return self._showError( o_result.error.message );
            }

            if( o_result.result == 'ok' )
            {
               deferred.resolve( o_result );
               return;
            }

         })
         .fail(function( o_xhr, c_status, o_error  ){
            if( c_status == 'parsererror' )
            {
               return self._showError( 'remove_ticket_agent failure: '.concat( o_error.message ) );
            }

            debugger;
         });

         return deferred.promise();
      },// /_remove_ticket_agent()

      /**
       * This method allows you to call a method listening to this element
       * only as well as trigger a bubbling event.
       */
      _triggerEvent: function( cType, oData ){
         var fnMethod;
         var cFullEventType = ( this.widgetEventPrefix + cType ).toLowerCase();
         var oEvent = $.Event( cFullEventType );

         switch( cType ){
         case 'onResponse':
            fnMethod = this.options.on_response;
            break;

         }// /switch()


         if ( fnMethod ) {
            fnMethod( oEvent, oData );
         }

         //this._trigger( cType, oEvent, oData );
         this.element.trigger( oEvent, oData );
      },// /_triggerEvent()

      _showError: function( cMessage ){
         this.setState({
            error: cMessage,
            wait: null
         });

      },// /_showError()

      _showWait: function( cMessage ){
         this.setState({
            error: null,
            wait: cMessage
         });

      },// /_showWait()

      /**
       * This is a proxy method that allows you to get the template state.
       */
      getState: function(){
         return this.element.template( 'getState' );
      },// /getState()

      /**
       * This is a proxy method that allows you to change the template state.
       * @param  {object} oState      Object with changed state values.
       * @param  {boolean} lRender   Whether or not to render changes.
       * @param  {boolean} lDiff      Whether or not to check for changes.
       *                         Useful for saving cpu cycles on changes
       *                         that we don't care about.
       */
      setState: function( oState, lRender, lDiff ){
         this.element.template( 'setState', oState, lRender, lDiff );
      },// /setState()

      _destroy: function(){
         // Undo everything.
         this._beforeRender();
         this.element.off( '.show-agent-remove' );
         this.element.template( 'destroy' );
         this.element.removeClass( 'ui-widget-show-agent-remove' );
      }// /_destroy()
   });

}( jQuery ));

}( window ));
/**
build time: Fri Feb 01 2019 11:47:46 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/src/ticket-view-agents.js
**/
/**
 * 01-25-2019
 * Main app/tab.
 * ~~ {%= author_name %}
 */

/** List jshint ignore directives here. **/
/* jshint undef: true, unused: true */
/* jshint browser:true */
/* global jQuery:false */


(function ($, undefined) {
$(document).on('ready pjax:success', function() { // This is required because osticket uses pjax for stuff.


   var $agent_list = $( '#agent-list' );

   if( $agent_list.length < 1 ){
      // We are not on a ticket page.
      return;
   }

   var $agent_list_error = $( '#agent-list-error' ).errorBanner();
   var $agent_list_search = $( '#agent-list-search' );
   var $agent_add_progress = $( '#agent-add-progress' );
   var $agent_remove_progress = $( '#agent-remove-progress' );
   var $agent_confirm_remove = $( '#agent-confirm-remove' );
   

   var view = {
      _views: {},
      _current: null,

      add: function( c_name, o_view ) {
         return this._views[ c_name ] = o_view;
      },// /add()

      show: function( c_name, o_data ) {
         var self = this;

         if( !this._views[ c_name ] ) {
            throw new Error( ''.concat( 'View "',c_name,'" is not defined.' ) );
         }

         if( this._current == this._views[ c_name ] ) {
            return;
         }

         if( this._current != null ) {
            $.when( this._views[ this._current ].hide() )
            .then(function(){
               self._show_now( c_name, o_data );
            });

            return;
         }

         return self._show_now( c_name, o_data );

      },// /show()

      _show_now: function( c_name, o_data ) {
         var c_previous = this._current;

         this._current = c_name;
         this._views[ c_name ].show( c_previous, o_data );

      }// /_show_now()


   };

   var show_error = function( c_msg ){
      return $agent_list_error.errorBanner( 'set_message', c_msg );
   };// /show_error()

   var clear_error = function(){
      return $agent_list_error.errorBanner( 'clear' );
   };// /clear_error()

   view.add( 'default', {
      show: function(){
         $.ajax({
            url: 'ajax.php/ticket_options/script/get_ticket_agents.php',
            method: 'get',
            type: 'json',
            dataType: 'json',
            data: {
               ticket_id: $.ticket_id
            }
         })
         .then(function( o_result/* , status, o_xhr */ ){
            if( o_result.error ) {
               return show_error( o_result.error.message );
            }

            if( o_result.result == 'ok' )
            {
               $agent_list.userList({
                  users: o_result.agents,
                  on_add_click: function( event, ui ){
                     view.show( 'add_agent_form' );
                  },// /on_add_click()

                  on_remove_click: function( event, ui ){
                     console.log( ui );
                     view.show( 'remove_agent_confirm', { agent: ui.user });
                  }// /on_remove_click()
                  
               });
               return;
            }

            debugger;
            
         })
         .fail(function( o_xhr, c_status, o_error  ){
            if( c_status == 'parsererror' )
            {
               return show_error( 'get_ticket_agents failure: '.concat( o_error.message ) );
               
            }

            debugger;
         });

      },

      hide: function(){
         $agent_list.userList( 'destroy' );
         clear_error();
      }
   });

   view.add( 'add_agent_form', {
      show: function(){
         $agent_list_search.agentSearch({
            on_agent_click: function( event, ui ){
               view.show( 'add_agent_progress', { staff_id: ui.staff_id } );
            }
         });
      },

      hide: function(){
         $agent_list_search.agentSearch( 'destroy' );
         clear_error();
      }
   });


   view.add( 'add_agent_progress', {
      show: function( c_previous, o_data ){
         $agent_add_progress.showAgentAdd({
            staff_id: o_data.staff_id,
            ticket_id: $.ticket_id,
            on_response: function( /* event, ui */ ){
               view.show( 'default' );
            }
         });
      },

      hide: function(){
         $agent_add_progress.showAgentAdd( 'destroy' );
      }
   });


   view.add( 'remove_agent_progress', {
      show: function( c_previous, o_data ){

         $agent_remove_progress.showAgentRemove({
            staff_id: o_data.agent.staff_id,
            ticket_id: $.ticket_id,

            on_response: function( /* event, ui */ ){
               view.show( 'default' );
            }
         });
      },

      hide: function(){
         $agent_remove_progress.showAgentRemove( 'destroy' );
      }
   });


   view.add( 'remove_agent_confirm', {
      show: function( c_previous, o_data ){
         
         $agent_confirm_remove.dialogConfirm({
            message: ''.concat( 'This cannot be undone.',
            ' Are you sure you wish to remove staff member "',o_data.agent.name,'" from this ticket?' ),

            text_confirm: 'Yes, remove this agent',
            text_cancel: 'No, Do NOT remove this agent',

            on_cancel_click: function( /* event, ui */ ){
               view.show( 'default' );
            },

            on_confirm_click: function( /* event, ui */ ){
               view.show( 'remove_agent_progress', o_data );
            }
         });
      },

      hide: function(){
         $agent_confirm_remove.dialogConfirm( 'destroy' );
      }
   });

   


   view.show( 'default' );
});

}( jQuery ));
}());