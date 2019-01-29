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
               wait: ''.concat( 'Adding agent ',this.options.staff_id,' to ticket ',this.options.ticket_id,'...' )
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
         this.element.off( '.show-agent-add' );
         this.element.template( 'destroy' );
         this.element.removeClass( 'ui-widget-show-agent-add' );
      }// /_destroy()
   });

}( jQuery ));
