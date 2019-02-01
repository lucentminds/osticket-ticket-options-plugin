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

