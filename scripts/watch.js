/**
 * 01-23-2017
 * Watch script.
 * Scott Johnson
 */


/** List jshint ignore directives here. **/
/* jslint node: true */

// Stop jshint from complaining about the promise.catch() syntax.
/* jslint -W024 */
var bob = require( 'builder-bob' );

require( '../bobfile.js' )( bob ).getJob( 'watch' ).run();