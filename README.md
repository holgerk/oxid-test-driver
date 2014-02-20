oxid-test-driver
================

Functional Testing Framework for OXID eShop

Features
--------

* Simulation of multiple requests in one process
* Resets state before each request
* Easy Way to test controllers
* Allows verification of expected output
* Maintains cookies across serveral requests
* No Browser involved, so every error or warning pops up directly
* Allows using SEO-Urls
* Low level access to response text and headers
* Easy extendable with higher level abstraction

How this works
--------------

There is some black magic involved, because an autoloader is used to replace some classes with new ones. You can See all replacements in the replacements directory. This has to be done, because otherwise it is not possible to reset the application state. The replacements classes extend the original ones and overload only as few as possible methods. 

Also there are classes which are overloads in a more classical manner. These are defined in the overloads Folder and follow the same pribciple, but in a more lightweight fassio, like classical module overloads. 

TODO
----

* Allow easy access to view data
* QuickStart Guide
* Easy mocking for framework instances(stuff created with oxNew)
