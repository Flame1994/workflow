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
workflow:generate <name>                            Generates workflows for the specified workflow name in the Workflow folder
workflow:generate                                   Generates all workflows from all definitions in the Workflow folder
```

### How it works
A workflow is much like a state machine (see [here](https://www.techopedia.com/definition/16447/state-machine) for more information). Basically
a workflow is divided into multiple 'states' or 'steps' with each step receiving an input, doing some logic, and returning
an output. The ultimate goal of a workflow is to receive input, go through a defined number of steps through transitions and give
an output at the end.

#### Setting up a definition file
Once you run the command to create a definition file, like `php artisan workflow:create Example` you will see something like this:
```json
{
    "name": "Example",
    "uses": [],
    "namespace": "App\\Workflows\\Example",
    "startState": "start_state_function_name",
    "input": {
        "input_name": "input_type"
    },
    "output": {
        "return_name": "return_type"
    },
    "workflow": {
        "start_state_function_name": {
            "parameters": {
                "parameter_name": "parameter_type"
            },
            "result": {
                "return_value": "return_type"
            },
            "transition": {
                "function_name": ""
            }
        }
    }
}
```
What does this all mean? See the table below
```
DEFINITION PARAMETER         DESCRIPTION
----------------------------------------------------------------------------------------------
name                         The name of the workflow
uses                         The imports (use) of the workflow
namespace                    The namespace of the workflow
startState                   The function name which to start from in the workflow
input                        The list of input parameters and their type
output                       The output parameter and its type
workflow                     This is the list of workflow states / steps,
                             each listing their input parameters, result and
                             transition requirements to the next workflow state
```
In order to create a working definition file, all of the information needs to be provided within the placeholder fields.
For a decent example on all capabilities of a workflow definition file, see below:
```json
{
    "name": "UserUpdate",
    "uses": [
        "App\\User",
        "Common\\Id",
        "Common\\Status",
        "Common\\Contact"
    ],
    "namespace": "App\\Workflows\\UserUpdate",
    "startState": "GetExistingUserOrNull",
    "input": {
        "id": "Id",
        "name": "string|null",
        "status": "Status|null",
        "allContact": "Contact[]|null"
    },
    "output": {
        "user": "User"
    },
    "workflow": {
        "GetExistingUserOrNull": {
            "parameters": {
                "id": "Id"
            },
            "result": {
                "user": "User|null"
            },
            "transition": {
                "End": "is_null($this->user) === true",
                "ShouldUpdateUser": "is_null($this->user) === false"
            }
        },
        "ShouldUpdateUser": {
            "parameters": {
                "user": "User",
                "name": "string|null",
                "status": "Status|null",
                "allContact": "Contact[]|null"
            },
            "result": {
                "shouldUpdateUser": "bool"
            },
            "transition": {
                "End": "$this->shouldUpdateUser === false",
                "UpdateUser": "$this->shouldUpdateUser === true"
            }
        },
        "UpdateUser": {
            "parameters": {
                "user": "User",
                "name": "string|null",
                "status": "Status|null",
                "allContact": "Contact[]|null"
            },
            "result": {
                "user": "User"
            },
            "transition": {
                "End": ""
            }
        }
    }
}
```
You can see there are **three** transitions within this workflow:

- GetExistingUserOrNull is the **start state** and transitions to **ShouldUpdateUser** only if the condition specified is met, otherwise it transitions to **End**. 
- ShouldUpdateUser transitions to **UpdateUser** only if the condition is met, otherwise transitions
to **End**.
- UpdateUser only transitions to **End** with no condition specified.

Additional information on workflow states:

- Each workflow state takes **zero or more parameters as input** and can only specify **one output**.
- Make sure the **startState** specified, is the **first** workflow state you define.
- For every workflow transition condition, make sure to use ```$this->``` when referencing a variable.
- Make sure for every variable you define in the workflow, that an **import/use** is specified.
- You can't define a circular transition. (state1 -> state2 -> state3 -> state1)
- The use of the following primitive types are allowed:
    - string
    - bool
    - integer
    - float

When you have finished setting up your definition files, run the following command to generate the code:
```
php artisan workflow:generate
```
A base **Workflow** class will be generated that all other workflow's extend from.

For each definition file, it will generate a **Base Class** as well as a class that **Extends** the base class. Your
file structure for a working definition file should then look something like this:
```
Workflows
  - Common
    - Workflow.php                      // This is the base workflow class that all base workflows extend from.
  - UserUpdate
    - Code
      - WorkflowUserUpdate.php          // The workflow class where your business logic will go. This extends the base class
    - Definition
      - UserUpdate.json                 // Definition file where you define everything
    - Generated
      - WorkflowUserUpdateBase.php      // The auto generated base class that extends Workflow.php
  - ...
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
