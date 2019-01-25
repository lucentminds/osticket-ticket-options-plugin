(function( undefined ){

/**
build time: Thu Jan 24 2019 20:29:50 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-user-list/build/ui-user-list.js
**/
(function( window, undefined ){

/**
build time: Thu Jan 24 2019 20:29:50 GMT-0600 (CST)
build source: /home/sjohnson/project/osticket-helpdesk/public_html_56/ost/include/plugins/osticket-ticket-options-plugin/widgets/ui-user-list/temp/ui-user-list-htm.js
**/
var cTemplate='<div class="user-list"> {{#error}} <div class="callout alert"> {{error}} </div> {{/error}} {{#wait}} <div class="callout primary"> {{wait}} </div> {{/wait}} {{^error}} {{^wait}} Hello, World! {{/wait}} {{/error}} </div>';
/**
build time: Thu Jan 24 2019 20:29:50 GMT-0600 (CST)
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

      options: {
      },
      _create: function() {
         //this._id = Math.round( Math.random()*10000000 );
         //this._lastErrors = [];
         this.element.addClass( 'ui-widget-user-list' );
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
         // this.element.on( 'EVENT.user-list', '.CLASSNAME', {self:this}, this._HANDLERMETHOD );

         /*
          * Make sure render() is always called within the context/scope of
          * this widget.
          */
         this.render = $.proxy( this.render, this );
         this.render();
      },// /_create()

      //_setOption: function( option, value ) {
      //   $.Widget.prototype._setOption.apply( this, arguments );
      //
      //   switch( option ) {
      //      default:
      //         return;
      //   }// /switch()
      //},// /setOption()

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
         this.element.off( '.user-list' );
         this.element.template( 'destroy' );
         this.element.removeClass( 'ui-widget-user-list' );
      }// /_destroy()
   });

}( jQuery ));

}( window ));
}());