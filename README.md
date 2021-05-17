# sourceability/console-toolbar-bundle

## Installation

Run

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

## Usage

`bin/console` now has a new global option `--toolbar`:

```
# bin/console cache:warmup --toolbar

 // Warming up the cache for the dev environment with debug true


 [OK] Cache for the "dev" environment (debug=true) was successfully warmed.


┌─────────┬───────────────┬─────────┬─────────┬─────────────────┬──────────┬────────┬───────────────┐
│ Type    │ Name          │ request │ time    │ cache           │ security │ twig   │ elasticsearch │
├─────────┼───────────────┼─────────┼─────────┼─────────────────┼──────────┼────────┼───────────────┤
│ COMMAND │ /cache:warmup │ 200     │ 7609 ms │ 566 in 30.26 ms │ n/a      │ n/a ms │ 0             │
└─────────┴───────────────┴─────────┴─────────┴─────────────────┴──────────┴────────┴───────────────┘
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

```
# behat -vvv features/api.feature
Feature:

  @db
  Scenario: Reverting a manual stock transaction is not possible
┌─────────┬──────────────────────────────┬─────────┬─────────┬──────────┬────────┬───────────────┬─────────────────────┬────────────────────┐
│ Type    │ Name                         │ request │ time    │ security │ twig   │ elasticsearch │ eight_points_guzzle │ db                 │
├─────────┼──────────────────────────────┼─────────┼─────────┼──────────┼────────┼───────────────┼─────────────────────┼────────────────────┤
│ COMMAND │ /doctrine:database:drop      │ 200     │ 1447 ms │ n/a      │ n/a ms │ 0             │                     │                    │
├─────────┼──────────────────────────────┼─────────┼─────────┼──────────┼────────┼───────────────┼─────────────────────┼────────────────────┤
│ COMMAND │ /doctrine:database:create    │ 200     │ 1485 ms │ n/a      │ n/a ms │ 0             │                     │                    │
├─────────┼──────────────────────────────┼─────────┼─────────┼──────────┼────────┼───────────────┼─────────────────────┼────────────────────┤
│ COMMAND │ /doctrine:migrations:migrate │ 200     │ 4841 ms │ n/a      │ n/a ms │ 0             │                     │ 2542 in 1312.73 ms │
└─────────┴──────────────────────────────┴─────────┴─────────┴──────────┴────────┴───────────────┴─────────────────────┴────────────────────┘
    Given the fixtures "api/offer_stock_transactions_manual.yml" are loaded
    When I am authenticating as api client Default with a valid JWT
    And the "Content-Type" request header contains "application/json"
    And I request "/api/offer-stock-transactions/1/revert" using HTTP POST
┌──────┬────────────────────────────────┬────────────────────────────────┬────────┬─────────────────┬────────────────────────────────┬────────┬──────────────┬───────────────┐
│ Type │ Name                           │ request                        │ time   │ cache           │ security                       │ twig   │ db           │ elasticsearch │
├──────┼────────────────────────────────┼────────────────────────────────┼────────┼─────────────────┼────────────────────────────────┼────────┼──────────────┼───────────────┤
│ POST │ /api/offer-stock-transactions/ │ 405 POST @ app_api_offerstockt │ 425 ms │ 604 in 15.63 ms │ 0b13e52d-b058-32fb-8507-10dec6 │ n/a ms │ 5 in 6.54 ms │ 0             │
│      │ 1/revert                       │ ransaction_revertstocktransact │        │                 │ 34a07c                         │        │              │               │
│      │                                │ ion                            │        │                 │                                │        │              │               │
└──────┴────────────────────────────────┴────────────────────────────────┴────────┴─────────────────┴────────────────────────────────┴────────┴──────────────┴───────────────┘
    Then the response code is 405
    When I am authenticating as api client Default with a valid JWT
    And the "Content-Type" request header contains "application/json"
    And I request "/api/offer-stock-transactions/2/revert" using HTTP POST
┌──────┬────────────────────────────────┬────────────────────────────────┬────────┬─────────────────┬────────────────────────────────┬────────┬──────────────┬───────────────┐
│ Type │ Name                           │ request                        │ time   │ cache           │ security                       │ twig   │ db           │ elasticsearch │
├──────┼────────────────────────────────┼────────────────────────────────┼────────┼─────────────────┼────────────────────────────────┼────────┼──────────────┼───────────────┤
│ POST │ /api/offer-stock-transactions/ │ 405 POST @ app_api_offerstockt │ 378 ms │ 604 in 11.01 ms │ 0b13e52d-b058-32fb-8507-10dec6 │ n/a ms │ 4 in 4.75 ms │ 0             │
│      │ 2/revert                       │ ransaction_revertstocktransact │        │                 │ 34a07c                         │        │              │               │
│      │                                │ ion                            │        │                 │                                │        │              │               │
└──────┴────────────────────────────────┴────────────────────────────────┴────────┴─────────────────┴────────────────────────────────┴────────┴──────────────┴───────────────┘
    Then the response code is 405

7 profiles collected, Open profile list
1 scenario (1 passed)
9 steps (9 passed)
0m8.39s (96.13Mb)
```
