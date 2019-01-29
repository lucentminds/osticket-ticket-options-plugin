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
