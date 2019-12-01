<?php
namespace Rhaarhoff\Workflow;

use Illuminate\Support\ServiceProvider;

class WorkflowServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Used to bind our package to the classes inside the app container.
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Used to initialize some routes or add an event listener.
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Workflow\CreateWorkflow::class
            ]);
        }
    }
}
