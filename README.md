oxid-test-driver
================

Functional Testing Framework for OXID eShop

Usage
-----

* Configure the driver class before the oxid bootstrap is loaded, the driver class will then load
  the oxids bootstrap
* Create driver instance within your test
* Request some resource on your shop with the driver
* Before each request the state of the shop is reset
* After each request the state of the shop is modified, so you can do verifications on the
  loaded controllers

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

There is some black magic involved, because an autoloader is used to replace some classes with new ones. You can see all replacements in the replacements directory. This has to be done, because otherwise it is not possible to reset the application state. The replacements classes extend the original ones and overload only as few as possible methods.

Also there are classes which are overloads in a more classical manner. These are defined in the overloads directory and follow the same principles, but in a more lightweight fassion, like classical module overloads.

TODO
----

* Allow easy access to view data
* QuickStart Guide
* Easy mocking for framework instances(stuff created with oxNew)
* Easy config change for tests
