Migrating from Splash 7 to Splash 8
-----------------------------------

In order to upgrade from Splash 7 to Splash 8, you need to perform the following steps:

- Update "mouf/mvc.splash" version to "~8.0" in your `composer.json` file.
- Run `php composer.phar update`
- Remove `extends Controller` in each controller. Starting from Splash 8, controllers do not extend any class.
- Update all `@URL` annotations. In Splash 8, annotations are handled by the Doctrine annotations library. Therefore:
    - You must add a `use TheCodingMachine\Splash\Annotations\URL;` in each controller.
    - You must rewrite annotations to the Doctrine format:
    ```
    @URL my/path => @URL("my/path")
    ```
- Update all `@Action` annotations.
    - You must add a `use TheCodingMachine\Splash\Annotations\Action;` in each controller.
- Assuming you are using Mouf (this is a safe assumption since Splash 7 is highly tied to Mouf), run the Splash installer again.
    - Connect to Mouf UI (http://localhost/[yourproject]/vendor/mouf/mouf)
    - Click on *Project > Installation tasks*
    - There are 2 install tasks for "mouf/mvc.splash". Locate those in the table.
    - Click on the **Reinstall** button for both tasks.

You are done. Enjoy the new features!

Migrating from Splash 5 to Splash 7
-----------------------------------

In order to upgrade from Splash 5 to Splash 7, you need to perform the following steps:

- Update "mouf/mvc.splash" version to "~7.0" in your `composer.json` file.
- Run `php composer.phar update`
- Connect to Mouf UI (http://localhost/[yourproject]/vendor/mouf/mouf)
- Click on *Project > Installation tasks*
- There are 2 install tasks for "mouf/mvc.splash". Locate those in the table.
- Click on the **Reinstall** button for both tasks.
- In your controllers, stop using the `Request` and `Response` classes and start using PSR-7's `ServerRequestInterface` and `ResponseInterface`

You are done. Enjoy the new features!

Hey! What about Splash 6?
Mmmm... there was never a stable Splash 6. Splash 6 is a release targeting Mouf 2.1 that is not released as we write these lines.

Migrating from Splash 4 to Splash 5
-----------------------------------

In order to upgrade from Splash 4 to Splash 5, you need to perform the following steps:

- Update "mouf/mvc.splash" version to "~5.0" in your `composer.json` file.
- Run `php composer.phar update`
- Connect to Mouf UI (http://localhost/[yourproject]/vendor/mouf/mouf)
- Click on *Instances > View declared instances*
- Look for the "splash" instance.
- Click on it, then click on the "Delete" button
- Click on *Project > Installation tasks*
- There are 2 install tasks for "mouf/mvc.splash". Locate those in the table.
- Click on the **Reinstall** button for both tasks.

You are done. Enjoy [the new features](http://mouf-php.com/stackphp-support-added-to-splash)!
