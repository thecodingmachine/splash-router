Writing controllers
===================

In this document, we will see how to create a controller.
You will also learn more about what makes a controller.

Note: if you are using *Mouf*, Splash comes with a [controller creation wizard (a nice user interface)](writing_controllers.md).

What is a controller?
---------------------

In Splash, a controller is a class that contains a number of _Actions_.
_Actions_ are methods that can be directly accessed from the browser.

There are several ways to declare a method to be an action. The most common ways are:
 - The *@URL* annotation
 - The *@Action* annotation


The @URL annotation
-------------------

This is the preferred way of declaring an action:

```php
<?php
namespace Test\Controllers;

use TheCodingMachine\Splash\Annotations\URL;

class MyController {
    
    /**
     * My first action.
     *
     * @URL("/path/to/my/action")
     * @param string $var1
     * @param string $var2
     */
    public function myUrl($var1, $var2) {
        $str = "<html><head></head>";
        $str .= "<body>";
        $str .= "var1 value is ".htmlentities($var1)." and var2 value is ".htmlentities($var2);
        $str .= "</body>";
        return new HtmlResponse($str);
    }
}
```

Note: this class must be auto-loadable by Composer. Be sure to put the class in the correct repository according to your *composer.json* `autoload` section.


The *@URL* annotation points to the web path the action is bound to.

The action takes 2 parameters: `$var1` and `$var2`. This means that the page needs both parameters passed
either in GET or POST.

In order to test this, we must first create an instance of the controller in your container.
How you do this really depends on the container you are using. See the [installation section for more information](install/index.md).

Finally, we must also register this controller in Splash. How this is done essentially depends on your Splash installation.

 - if you are using Mouf, you have nothing to do. Splash will automatically detect your controllers.
 - if you are using Splash service provider, you need to put in your container an entry named 'thecodingmachine.splash.controllers' that is an array of controller identifiers.
   The way to do this depends on the container you are using, but might be something similar to:
    
    ```php
    $container->set('thecodingmachine.splash.controllers', [
       'myController',
       'myOtherController',
    ]);
    ```

Now, let's test our code.
By browsing to `http://localhost/{my_app}/path/to/my/action?var1=42&var2=24`, we should see the message displayed!

*Troubleshooting:* If for some reason, your controller is not detected, you can try to purge your cache.

Done? Then let's move on! 
 
The @Get / @Post annotations
----------------------------

We might decide that an action should always be called via GET, or via POST (or PUT or DELETE if you want to provide REST services).
Splash makes that very easy to handle. You can just add a @Get or @Post annotation (or @Put or @Delete). Here is a sample:

```php
<?php
namespace Test\Controllers;

use TheCodingMachine\Splash\Annotations\URL;
use TheCodingMachine\Splash\Annotations\Get;
use TheCodingMachine\Splash\Annotations\Post;

/**
 * This is a sample user controller.
 *
 */
class UserController {
    
    /**
     * Viewing the user is performed by a @Get.
     *
     * @URL("/user")
     * @Get
     * @param string $id
     */
    public function viewUser($id) {
        return new HtmlResponse("Here, we might put the form for user ".htmlentities($id));
    }

    /**
     * Modifying the user is performed by a @Post.
     *
     * @URL("/user")
     * @Post
     * @param string $id
     * @param string $name
     * @param string $email
     */
    public function editUser($id, $name, $email) {
         return new HtmlResponse("Here, we might put the code to change the user object.");
    }

}
```

In the example above (a sample controller to view/modify users), the "/user" URL is bound to 2 different methods
based in the HTTP method used to access this URL.

Parametrized URLs
-----------------

You can put parameters in the URLs and fetch them very easily:

```php
<?php
/**
 * This is a sample user controller.
 *
 */
class UserController {
    
    /**
     * Viewing the user is performed by a @Get.
     *
     * @URL("/user/{id}/view")
     * @Get
     * @param string $id
     */
    public function viewUser($id) {
         return new HtmlResponse("Here, we might put the form for user ".htmlentities($id));
    }
}
?>
```

Do you see the @URL annotation? The {id} part is a placeholder that will be replaced by any value found in the URL.
So for instance, if you access http://[server]/[appname]/user/42/view, the $id parameter will be filled with "42".

Returning / outputting values
-----------------------------

As you probably already guessed, you must return a [PSR-7 Response object](http://www.php-fig.org/psr/psr-7/#3-3-psr-http-message-responseinterface).
Splash comes bundled with [Zend Diactoros](https://github.com/zendframework/zend-diactoros)

Therefore, you can write things like:

```php
<?php
use TheCodingMachine\Splash\Annotations\URL;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;

class MyController {

	/**
	 * Returning a Response object
	 *
	 * @URL("/myurl1")
	 */
	public function test1() {
		 return new HtmlResponse('Hello World', 200, array('content-type' => 'text/html'));
	}

	/**
	 * Returning a JSON response
	 *
	 * @URL("/myjsonurl")
	 */
	public function testJson() {
		 return new JsonResponse({ "status" => "ok", "message" => "Hello world!" });
	}
}
?>
```

Typically, in Mouf, you will want to output a template object. You can easily output templates (or any object
implementing the [`HtmlElementInterface`](http://mouf-php.com/packages/mouf/html.htmlelement/README.md) using the `HtmlResponse` object:

```php
<?php
use TheCodingMachine\Splash\Annotations\URL;
use TheCodingMachine\Splash\HtmlResponse;

class MyController {
    /**
     * @var HtmlElementInterface
     */
    private $template;

	...

	/**
	 * Returning a Response object
	 *
	 * @URL("/test_template")
	 */
	public function test1() {
	    // do stuff
		return new HtmlResponse($this->template);
	}
}
```

Uploading files
---------------

Uploaded files are also directly available from the signature of the method:

**HTML:**
```html
<input type="file" name="avatar" />
```

**PHP**:
```php
<?php
use TheCodingMachine\Splash\Annotations\URL;
use Psr\Http\Message\UploadedFileInterface;

class MyController {

	...

	/**
	 * Uploads a file
	 *
	 * @URL("/upload")
	 */
	public function uploadLogo(UploadedFileInterface $logo) {
		$logo->moveTo(__DIR__.'/uploads/logo.png');
		...
	}
}
```

The `$logo` object injected implements the [PSR-7 `UploadedFileInterface`](http://www.php-fig.org/psr/psr-7/#3-6-psr-http-message-uploadedfileinterface)

The @Action annotation
----------------------

The @Action parameter can replace the @URL parameter.
You simply put a @Action annotation in your method. The URLs to access a @Action method are always:

    http://[server-url]/[webapp-path]/[controller-instance-name]/[action-name]

Here is a sample:

```php
<?php
use TheCodingMachine\Splash\Annotations\Action;

/**
 * This is my test controller.
 *
 */
class MyController {
    
    /**
     * My first action.
     *
     * @Action
     * @param string $var1
     * @param string $var2
     */
    public function my_action($var1, $var2) {
        return new HtmlResponse("Hello!");
    }
}
?>
```

The *my_action* method is a Splash action. You know this because there is a @Action annotation in the PHPDoc comment of the method.

Now, we can access the example page using this URL:

    http://[server-url]/[webapp-path]/my_controller/my_action?var1=42&var2=toto

Default actions
---------------

Sometimes, when using @Action annotations, we might want to have a URL that is a bit shorter than /my_webapp/my_controller/my_action.
Splash supports a special method called "index". If no action is provided in the URL, the index method will be called instead.

```php
<?php
/**
 * This is my test controller.
 */
class MyController extends Controller {
    
    /**
     * The action called if no action is provided in the URL.
     *
     * @Action
     */
    public function index() {
        return new HtmlResponse("This is the index");
    }
}
?>
```

The test page can be accessed using the URL:

    http://[server-url]/[webapp-path]/my_controller/.

[Wanna learn more? Have a look at the Mouf controller cration wizard](mouf/writing_controllers.md)
