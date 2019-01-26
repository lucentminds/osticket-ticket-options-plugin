/**
 * 06-01-2017
 * Buildfile.
 * ~~ Scott Johnson
 */

/** List jshint ignore directives here. **/
/* jshint undef: true, unused: true */
/* jslint node: true */
/* jshint esversion: 6 */
/* eslint-env es6 */

var os = require('os');
var path = require('path');
var Q = require('q');
var copy = require('promise-file-copy');
var concat = require('promise-file-concat');
var empty = require('promise-empty-dir');
//var jsify = require( 'promise-file-jsify' );
//var write = require( 'promise-file-write' );
var replace = require('promise-file-replace');
var resolve = require('promise-resolve-path');
var NETROOT = path.resolve(process.env.LIFECORP || process.env.HOME + '/mnt');

if (os.platform() == 'win32') {
   console.log('This builder is NOT configured to run on Windows.');
   process.exit(1);
}

var build = module.exports = function (bob) { // jshint ignore:line

   /**
    * Set the current working directory for this bob for bob.resolve().
    */
   bob.cwd(__dirname);

   // Create the 'build' job.
   var oJobBuild = bob.createJob('build');

   // Create the "build" job for modules.
   var buildJobMods = bob.createJob('modules');

   // Add the dependencies from the modules.
   buildJobMods.addDependencies('build', [
      bob.resolve( './widgets/ui-user-list' ),
      bob.resolve( './widgets/ui-error-banner' ),
      bob.resolve( './widgets/ui-agent-search' )
   ], {});

   // Create the 'deploy' job.
   var oJobDeploy = bob.createJob('deploy');

   var onBuildFail = function ( /*err*/) {
      console.log('Failed to build app!');
      console.log('\n\n');
   };// /onBuildFail()


   var buildModules = function () {
      return buildJobMods.run();
   };// /buildModules()

   var concatToTemp = function () {

      return Q.all([
         // copy([
         //    './src/app.html'
         // ], './temp'),

         concat([
            './widgets/ui-user-list/build/ui-user-list.css',
            './widgets/ui-agent-search/build/ui-agent-search.css',
            './src/ticket-view-agents.css'
         ],
            './temp/ticket-view-agents.css', {
               prependSourcePath: true,
               prependDatetime: true

            }),

         concat([
            
            './widgets/mustache/2.0.0/mustache.js',
            './widgets/jquery-debounce/jquery-debounce.js',
            './widgets/ui-state/ui-state.js',
            './widgets/ui-template/ui-template.js',
            './widgets/ui-error-banner/build/ui-error-banner.js',
            './widgets/ui-user-list/build/ui-user-list.js',
            './widgets/ui-agent-search/build/ui-agent-search.js',
            './src/ticket-view-agents.js'
         ],
            './temp/ticket-view-agents.js', {
               prependSourcePath: true,
               prependDatetime: true,
               header: '(function( undefined ){\n',
               footer: '\n}());'
            })


      ]);
   };// /concatToTemp()

   // Clean the build directories.
   oJobBuild.addTask('empty', function () {
      return empty([
         './build',
         './temp'
      ], true);
   });
   oJobDeploy.addTask('empty', function () {
      return empty([
         './build',
         './temp'
      ], true);
   });

   // Add module build task.
   oJobBuild.addTask('build-modules', buildModules);
   oJobDeploy.addTask('build-modules', buildModules);

   // Concatenate the main js files for this app.
   oJobBuild.addTask('concat', concatToTemp);
   oJobDeploy.addTask('concat', concatToTemp);

   // Make replacements.
   // oJobBuild.addTask('replace-build', function () {
   //    return replace('./temp/app.html',
   //       [{
   //          search: /\{%= build-dist-folder %\}/g, replace: 'build'
   //       }]);
   // });
   // oJobDeploy.addTask('replace-dist', function () {
   //    return replace('./temp/app.html',
   //       [{
   //          search: /\{%= build-dist-folder %\}/g, replace: 'dist'
   //       }]);
   // });

   // Copy temp to output folder.
   oJobBuild.addTask('copy-build', function () {
      return copy('./temp', './build')
         .then(function () {
            return 'build';
         });
   });
   oJobDeploy.addTask('copy-deploy', function () {
      return copy('./temp', './build')
         .then(function () {
            return 'dist';
         });
   });

   // Copy build over network.
   oJobBuild.addTask('copy-network', function (cBuildType) {
      return copy_to_static(cBuildType);
   });
   oJobDeploy.addTask('copy-network', function (cBuildType) {
      return copy_to_static(cBuildType);
   });

   var copy_to_static = function (cBuildType) {
      if (!cBuildType || 'dist build'.indexOf(cBuildType) < 0) {
         console.log('Build type is missing or invalid.');
         process.exit();
      }

      // Determines the path that MUST exist before any copying is done.
      var cPathShare = path.resolve('./');

      // Determines the path that will exist AFTER copying is done.
      var cPathDest = path.join(cPathShare, '/public/static/app' );

      console.log('Copy to', cPathDest);

      // Resolve, verify, and copy/deploy build.
      return resolve(cPathShare, true)
         .then(function () {
            return copy('./temp', cPathDest);
         });
   };// /copyOverNetwork()




   oJobBuild.fail(onBuildFail);
   oJobDeploy.fail(onBuildFail);

   var oJobWatch = bob.createJob('watch');

   oJobWatch.addTask('watch', function () {

      // Run the main build job.
      return oJobBuild.run()


         .then(function () {

            // Add an event listener on this bob for changes.
            bob.on('change', function ( /*bob, cPathChanged*/) {
               oJobBuild.run();
            });

            // Setup the watcher for the main source.
            return bob.watch([
               './widgets/ui-error-banner/src',
               './widgets/ui-user-list/src',
               './widgets/ui-agent-search/src',
               './src'
            ]);

         });
   });


   // Always return bob. :)
   return bob;

};// /build()
