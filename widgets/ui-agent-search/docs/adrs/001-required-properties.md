# Title
ADR-001: Allow Required Properties.

# Summary
Added a command line argument to specify properties that are required.

# Context
Scott recently added a `path_url` prompt to get the eventual url a web1 app would end up. The purpose of this property was to update the css and javascript url paths in a web app html file. The problem is that typical useage of the lifecorp-init app is to press enter through each prompt and accept the defaults. For a web1 app, there is no default for the url path because there is no standard for where the app will be placed on the lifecorp network. If this field does NOT get filled in when the app is intiated, then the developer must manually fill in this information. The purpose of lifecorp-init is to get a project skeleton up and running to a "Hello World" state with little to no modifications.

# Decision
Scott will add a parameter called `--require` and `-r` that will allow a string to be passed to tell lifecorp-init to require a specific prompt to not be empty for some projects, but remain empty for others.

# Consequences
The lifcorp-init will be able to block users from skipping required parameters.

# Status
Accepted