# sourceability/console-toolbar-bundle

Render the symfony profiler toolbar in your terminal.

<img width="1375" alt="Screen Shot 2021-05-18 at 17 52 13" src="https://user-images.githubusercontent.com/611271/118683608-cf759600-b801-11eb-98b5-715df3d26452.png">

Each panel links to the corresponding web profiler page.
Make sure to use a [terminal that support hyperlinks][hyperlink_terminals] to leverage this feature.

## Installation

Install the bundle using composer:

```sh
$ composer require --dev sourceability/console-toolbar-bundle
```

Enable the bundle by updating `config/bundles.php`:

```php
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    // ...
    FriendsOfBehat\SymfonyExtension\Bundle\FriendsOfBehatSymfonyExtensionBundle::class => ['dev' => true, 'test' => true],
    Sourceability\ConsoleToolbarBundle\SourceabilityConsoleToolbarBundle::class => ['dev' => true, 'test' => true],
];
```

Configure the bundle in `config/packages/{dev,test}/sourceability_console_toolbar.yaml`:

```yaml
sourceability_console_toolbar:
    toolbar:
        base_url: http://localhost:8200/ # for the iTerm2 hyperlinks
        hidden_panels:
            - config
            - form
            - validator
            - logger
```

If your application is not exposed at `http://localhost` exactly, make sure that
[you've configured the router request context][symfony_doc_request_context] for your environment.

By default, the profiler does not always run in the `test` environment.
You can enable it like this:

```diff
--- a/config/packages/test/web_profiler.yaml
+++ b/config/packages/test/web_profiler.yaml
@@ -3,4 +3,4 @@ web_profiler:
     intercept_redirects: false

 framework:
-    profiler: { collect: false }
+    profiler: { enabled:true, collect: true, only_exceptions: false }
```

Also add web profiler routes in `config/routes/test/web_profiler.yaml`

```yaml
web_profiler_wdt:
    resource: '@WebProfilerBundle/Resources/config/routing/wdt.xml'
    prefix: /_wdt

web_profiler_profiler:
    resource: '@WebProfilerBundle/Resources/config/routing/profiler.xml'
    prefix: /_profiler
```

## Behat

This bundle becomes really useful when writing/debugging behat scenarios.

First enable the behat extension by adding the following to your behat configuration:

```yaml
default:
    extensions:
        FriendsOfBehat\SymfonyExtension: ~
        Sourceability\ConsoleToolbarBundle\Behat\SymfonyToolbarExtension: ~
```

This will display the console toolbar whenever a new symfony profile is detected:

<img width="1375" alt="Screen Shot 2021-05-18 at 17 52 13" src="https://user-images.githubusercontent.com/611271/118683608-cf759600-b801-11eb-98b5-715df3d26452.png">

## PHPUnit

Add the following to your `phpunit.xml` configuration:

```xml
    <extensions>
        <extension class="Sourceability\ConsoleToolbarBundle\PHPUnit\ConsoleToolbarExtension">
            <arguments>
                <boolean>false</boolean> <!-- always show, if false use: TOOLBAR=true phpunit ...-->
                <integer>4</integer> <!-- Indentation -->
            </arguments>
        </extension>
    </extensions>
```

<img width="1242" alt="Screen Shot 2021-05-18 at 17 46 52" src="https://user-images.githubusercontent.com/611271/118682929-321a6200-b801-11eb-8390-90e2c7056c95.png">

## Console

`bin/console` now has a new global option `--toolbar`:

<img width="1242" alt="Screen Shot 2021-05-18 at 18 02 22" src="https://user-images.githubusercontent.com/611271/118685271-3f385080-b803-11eb-95f0-7d68c0e96857.png">

This feature requires [sourceability/instrumentation][hyperlink_terminals] with the following bundle configuration:

```yaml
sourceability_instrumentation:
    profilers:
        symfony:
            enabled: true
    listeners:
        command:
            enabled: true
```

[hyperlink_terminals]: https://gist.github.com/egmontkob/eb114294efbcd5adb1944c9f3cb5feda
[sourceability_instrumentation]: https://github.com/sourceability/instrumentation
[symfony_doc_request_context]: https://symfony.com/doc/4.4/routing.html#generating-urls-in-commands
