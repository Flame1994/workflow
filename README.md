## Laravel Workflow Generator

### Description
This package provides a way for generating workflow definitions & workflows from those definitions.

### Installation

Require this package with Composer using the following command:

```bash
composer require rhaarhoff/workflow
```

After updating Composer, add the service provider to the `providers` array in `config/app.php`:

```php
Rhaarhoff\Workflow\WorkflowServiceProvider::class
```

Run the `dump-autoload` command:
```bash
composer dump-autoload
```

In Laravel, instead of adding the service provider in the `config/app.php` file, you can add the following code to your `app/Providers/AppServiceProvider.php` file, within the `register()` method:

```php
public function register()
{
    if ($this->app->environment() !== 'production') {
        $this->app->register(\Rhaarhoff\Workflow\WorkflowServiceProvider::class);
    }
    // ...
}
```

### Commands

Below you can find all the commands that you can use, including the parameters that you can specify.

```
COMMAND                     PARAMETER             DESCRIPTION
-----------------------------------------------------------------------------------------------------------------------
workflow:create <name>                              Generates a workflow definition with the given name
workflow:create <name>        -s <start state>      Generates a workflow definition with the given name & start state
workflow:generate <name>                          Generates workflows for the specified workflow name in the Workflow folder
workflow:generate                                 Generates all workflows from all definitions in the Workflow folder
```

### License

The Laravel Workflow generator is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).


[ico-version]: https://poser.pugx.org/rhaarhoff/laravel-artisan-commands/v/stable
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://poser.pugx.org/rhaarhoff/laravel-artisan-commands/downloads
[ico-stars]: https://img.shields.io/github/stars/Flame1994/laravel-artisan-commands.svg

[link-packagist]: https://packagist.org/packages/rhaarhoff/laravel-artisan-commands
[link-downloads]: https://packagist.org/packages/rhaarhoff/laravel-artisan-commands
[link-stars]: https://github.com/Flame1994/laravel-artisan-commands
