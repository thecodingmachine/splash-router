Welcome to the Splash MVC framework common library
==================================================

What is Splash?
---------------

Splash is a PHP router. It takes an HTTP request and dispatches it to the appropriate container.
  
- Splash is [PSR-7 compatible](http://www.php-fig.org/psr/psr-7/)
- It uses PSR-7 middlewares
- It is based on **controllers** and **annotations** (routes are declared as annotations in the controllers)
- It is heavily optimized, relying on an underlying [PSR-6 cache](http://www.php-fig.org/psr/psr-6/)
- It is a **pure** router. It is not a full-featured MVC framework. No views management, no model, only routing!
- It promotes best practices. Controllers must be declared in a [container-interop compatible container](https://github.com/container-interop/container-interop/).
- It is extensible.










TODO

Splash is a MVC PHP framework. It is making a heavy use of annotations, and of [the Mouf dependency injection framework](http://www.mouf-php.com).
You might want to use Splash in order to seperate cleanly the controllers (that perform the actions required when you navigate your web application) and the view (that generates and displays the HTML that makes your web pages).

What is this common library thing?
----------------------------------

This package contains the common functions used by all libraries respecting the Splash syntax (annotations, etc...)
All libraries you said? Yes, all libraries. Indeed, there are many packages respecting the Splash syntax.

Of course, there is [Splash (a full-featured MVC framework)](http://mouf-php.com/packages/mouf/mvc.splash).
But there is also [Druplash (a module for Drupal that adds a Splash-compatible MVC framework)](http://mouf-php.com/packages/mouf/integration.drupal.druplash),
[Moufpress (a plugin for Wordpress that adds a Splash-compatible MVC framework)](http://mouf-php.com/packages/mouf/integration.wordpress.moufpress),
[Moufla (a plugin for Joomla that adds a Splash-compatible MVC framework)](https://github.com/thecodingmachine/integration.joomla.moufla),
[moufgento (a plugin for Magento that adds a Splash-compatible MVC framework)](https://github.com/thecodingmachine/integration.magento.moufgento).


Documentation
-------------

To learn more about Splash, [check the documentation on the Splash website](http://mouf-php.com/packages/mouf/mvc.splash/)