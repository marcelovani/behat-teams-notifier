Behat Teams Notifier Extension
=========================
This Behat extension integrates with [https://github.com/marcelovani/behat-notifier](Behat Notifier)
to allow sending MS Teams notifications to channels.

Installation
------------

Install by adding to your `composer.json`:

```bash
composer require --dev marcelovani/behat-teams-notifier
```

Configuration
-------------

Enable the extension in `behat.yml` like this:

The configuration goes in the `Marcelovani\Behat\Notifier` extension, under `notifiers`

```yml
default:
  extensions:
    Marcelovani\Behat\Notifier:
      screenshotExtension: Bex\Behat\ScreenshotExtension
      notifiers:
        Marcelovani\Behat\Notifier\Teams\TeamsNotifier:
          # The MS Teams webhook.
          webhook: 'https://www.foo.bar'
```

Extending
-------------

It is possible to extend this class by implementing your own class and listing it
on the `notifiers` list instead of the default class.

Todo
-------------
- Use Guzzle instead of php curl
- Add example Features and Unit tests
- Add Github actions
- List package on https://packagist.org/
