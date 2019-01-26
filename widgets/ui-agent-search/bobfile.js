/**
 * 01-25-2019
 * Buildfile.
 * ~~ Scott Johnson
 */

/** List jshint ignore directives here. **/
/* jshint undef: true, unused: true */
/* jslint node: true */
/* jshint esversion: 6 */
/* eslint-env es6 */

var Q = require( 'q' );
var copy = require( 'promise-file-copy' );
var concat = require( 'promise-file-concat' );
var empty = require( 'promise-empty-dir' );
var jsify = require( 'promise-file-jsify' );
var write = require( 'promise-file-write' );
//var replace = require( 'promise-file-replace' );

var build = module.exports = function( bob ){ // jshint ignore:line
   // Create the 'build' job for all.
   var oJobBuild = bob.createJob( 'build' );

   var onBuildFail = function( /*err*/ ){
      console.log( 'Failed to build ui-agent-search!' );
      console.log( '\n\n' );
   };// /onBuildFail()

   /**
    * Set the current working directory for this bob for bob.resolve().
    */
   bob.cwd( __dirname );

   // Clean the build directories.
   oJobBuild.addTask( 'empty', function(){
      return empty([
         bob.resolve( './build' ),
         bob.resolve( './temp' )
      ], true );
   });

   // Jsify the html files for this plugin.
   oJobBuild.addTask( 'jsify', function(){
      // Jsify and minify.
      return Q.all([
         jsify( bob.resolve( './src/ui-agent-search.htm' ), true )
         .then(function( cResult ){

            var cJs;

            cResult = cResult.replace( /'/g, '\\\'' );

            cJs = "var cTemplate='".concat( cResult, "';");
            return write( bob.resolve( './temp/ui-agent-search-htm.js' ), cJs );
         }),

         jsify( bob.resolve( './src/ui-agent-search-results.htm' ), true )
         .then(function( cResult ){

            var cJs;

            cResult = cResult.replace( /'/g, '\\\'' );

            cJs = "var cResultsTemplate='".concat( cResult, "';");
            return write( bob.resolve( './temp/ui-agent-search-htm-results.js' ), cJs );
         }),
      ]);
   });

   

   // Concatenate the main js files for this plugin.
   oJobBuild.addTask( 'concat', function(){



      return Q.all([

         

         copy( bob.resolve('./src/ui-agent-search.css' ), bob.resolve( './build' ) ),



         concat( [

            bob.resolve( './temp/ui-agent-search-htm.js' ),
            bob.resolve( './temp/ui-agent-search-htm-results.js' ),
            bob.resolve( './src/ui-agent-search.js' ),
         ], bob.resolve( './build/ui-agent-search.js' ), {
            prependDatetime: true,
            prependSourcePath: true,
            header: '(function( window, undefined ){\n',
            footer: '\n}( window ));'
         } )



      ]);
   });

   oJobBuild.fail( onBuildFail );

   // Always return bob. :)
   return bob;

};// /build()
