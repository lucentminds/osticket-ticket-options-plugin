# ui-gridNav
The best widget ever..

### Useage:

```
// Sample code here.
$( '#myElement' ).template({
		renderOnInit: false,
		template: '<strong>{{param1}} {{param2}}</strong>',
		state: {
			param1: 'Hello,',
			param2: 'World!'
		},
		beforeRender: function( event, ui ){
			console.log( ui.state );
		},
		onRender: function( event, ui ){
			console.log( ui.state );
		}
	});
```