Filters
=======

Using filters
-------------

Splash supports the notion of _"Filter"_. A filter is a piece of code that can wrap an action.

There could be many reason why you want to run a filter:

 - Check that a user is logged before starting an action
 - Check that the action is performed on an SSL channel
 - Provide caching
 - Initialize some frameworks, ...

In Splash, filters are **annotations**.

Below are sample filters:

```php
/**
 * @URL("/test")
 * @Logged
 * @RequireHttps("yes")
 */
function deleteUser($password) { ... }
```

This sample provides 2 filters:

 - *@Logged* is used by Splash to check that the user is logged. If not, the user is sent to the login page.
 - *@RequireHttps* is used by Splash to make sure the action is run on an HTTPS channel. If not, an error message is displayed.

Note: the *@Logged* filter is not part of the base distribution of Splash. Indeed, Splash is in no way an authentication framework.
However, if you are using the _mouf/security.userservice_ package, you might want to add the _mouf/security.userservice-splash_ package
that provides this usefull *@Logged* annotation.

Please note the <b>@RequireHttps</b> annotation accepts one parameter. This parameter can be:

- By passing @RequireHttps("yes"), an Exception is thrown if the action is called in HTTP.
- By passing @RequireHttps("no"), no test is performed.
- By passing @RequireHttps("redirect"), the call is redirected to HTTPS. This does only work with GET requests.


There is a third default filter worth mentioning:

The *@RedirectToHttp* filter will bring the user back to HTTP if the user is in HTTPS. The port can be specified in parameter if needed. The filter
works only with GET requests. If another type of request is performed (POST), an exception will be thrown.

Developing your own filters
---------------------------

You can of course develop your own filters.
Each filter is represented by a class.

To create a filter:

 - The filters must be a valid [Doctrine annotation](http://doctrine-orm.readthedocs.io/projects/doctrine-common/en/latest/reference/annotations.html).
 - The filter must implement the  FilterInterface.

Note that a filter is very similar to a PSR-15 middleware, except the `process` method is additionally passed a container (useful to bind the annotation to a given service).

Here is the typical filter layout:

```php
/**
 * A filter is an annotation, therefore, it MUST have the @Annotation annotation.
 *
 * @Annotation
 */
class SampleFilter
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next, ContainerInterface $container)
    {
        // Do some stuff before the action is called
        // ...
        
        // Then call the action:
        $response = $next($request, $response);
        
        // Then do some stuff after the action is called
        // ...
        
        // Finally, return the response
        return $response;
    }
}
```
