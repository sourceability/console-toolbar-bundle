# sourceability/console-toolbar-bundle

Renders the profiler toolbar in your terminal.

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

By default, the profiler does not always run in the `test` environment.
You can enable it like this:

```diff
--- a/config/packages/test/web_profiler.yaml
+++ b/config/packages/test/web_profiler.yaml
@@ -3,4 +3,4 @@ web_profiler:
     intercept_redirects: false

 framework:
-    profiler: { collect: false }
+    profiler: { only_exceptions: false }
```

## Behat

This bundle becomes really useful when writing/debugging behat scenarios.

First enable the behat extension by adding the following to your behat configuration:

```yaml
default:
    extensions:
        Sourceability\ConsoleToolbarBundle\Behat\SymfonyProfilerExtension: ~
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

- *Note that this won't work without a library we will later release.*

`bin/console` now has a new global option `--toolbar`:

<img width="1242" alt="Screen Shot 2021-05-18 at 18 02 22" src="https://user-images.githubusercontent.com/611271/118685271-3f385080-b803-11eb-95f0-7d68c0e96857.png">
