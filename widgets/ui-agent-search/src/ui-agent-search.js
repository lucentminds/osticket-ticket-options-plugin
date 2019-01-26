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
         on_add_click: null
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
            on_add_click: this.options.on_add_click
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
         on_add_click: null
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
         this.element.on( 'click.agent-search-results', '.agent-search-results__btn-add', {self:this}, this._on_add_click );

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

      _on_add_click: function( event ){
         var self = event.data.self;
         var n_staff_id = $(this).data( 'staff_id' );

         console.log( 'click', n_staff_id );

         self._triggerEvent( 'addClick', { widget:self, staff_id: n_staff_id } );
      },// /_on_add_click()

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
