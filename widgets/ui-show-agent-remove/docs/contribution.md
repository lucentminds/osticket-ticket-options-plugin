# Planning for Contribution
From: https://www.sitepoint.com/open-sourcing-javascript-code/

With GitHub, we get an incredible tool to release open source software these days. Not only do we get Git, a tool to contribute code safely without overriding each other's work. We also get an issue tracker, a Wiki to explain and write docs and GitHub Pages to show an executable version of your code right where the source lives.

In order to make it as easy as possible for people to contribute, it makes sense to have a few things in place:

* __A great README.__ This is the first thing people look at. Explain early on what the project does and who it is for. Point to all the other parts listed here and make sure to keep it up-to-date with the latest information.

* __Information on how to run it.__ Most developers won't contribute to a project they can't run themselves. Make sure you define how to try the project yourself, listing all dependencies and environment setup necessary.

* __Have a sensible changelog.__ It shows how you fixed issues, added features and the overall cadence of the project.

* __Code guidelines.__ Describe briefly how you coded the product, what your settings and environments are to ensure that code contributed complies with this. This may spark some discussion, but it makes sure the final product is much easier to maintain.

* __Tests.__ Have ways to automatically test your code and run a series of tests before contributing. This makes sure that contributors have an extra step to take before submitting code that breaks the whole project.

* __Feedback channels.__ Give people a chance to contact you outside of the normal development flow. This gives them a chance to report bad behaviour of others, ask you about commercial agreements, or just say "thank you" without adding to the noise of already busy communication channels.

* __Contribution guidelines.__ Explain how to write a pull request or issue that is most likely to cause the community (which initially is just you) to deal with them as painlessly and quickly as possible. There is nothing worse than an issue that lies unanswered or with lots of "we need more info". The less unanswered issues, the more inviting your project is.

* __Beginner bugs / features.__ If you have a way to flag up simple problems as "beginner bugs", do so. This is a great way for someone new to join the project and learn about it whilst fixing a small issue. It feels good to get into a group by removing an obstacle - however trivial. Much more than just trying to find a foothold and being overwhelmed by how great everybody else is.

* __Consider a contributor code of conduct.__ This may sound over the top but defining what you expect and not expect people to say to one another is a good start to get a healthy and creative community. It also allows admins to block people from contributing without any drama as there is a clear guideline.

Not all these are strictly necessary and sometimes are overkill. They are a great help though for your project to scale and grow. If you want to see a great example repository with all these things in place, check out [Microsoft's Visual Studio Code on GitHub](https://github.com/Microsoft/vscode). If you're thinking about a Code of Conduct, the [TODO group offers a template](http://todogroup.org/opencodeofconduct/).

## Scaling for Commercial Use
It is great to see your product used in a commercial product. If someone like Google, Facebook or Microsoft uses your script, it is quite the boost. However, this also means you need to ensure that certain things are in place, or there is no way for this to happen. These are generally great things to have, but they are a show-stopper for commercial users unless you provide them.

* __Make sure your product supports internationalisation.__ Have a way to translate strings, make sure the layout can shift from left to right to right to left. Allow for support of non-ASCII input.

* __Make sure your product is accessible with various input devices.__ Accessibility is a legal requirement for large corporations and it will get audited.

* __Make sure your product is not fixed to one environment.__ Sometimes a big player would love to use your product, but can't as you developed it for your computer, not the world at large.

* __Make sure your product has a license that allows for commercial use.__ This should be obvious, but many products can't be re-used because of an initial license that is too strict.

If you want to learn more about this, there is a [great talk by Chris Dias at Enterprise JS](https://vimeo.com/157660470) about how Microsoft built Visual Studio Code on top of open source projects and the problems that caused.