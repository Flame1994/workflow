<?php
namespace Rhaarhoff\Workflow;

use Illuminate\Support\ServiceProvider;
use Rhaarhoff\Workflow\Console\Workflow\CreateWorkflow;

/**
 * @author Ruan Haarhoff <ruan@aptic.com>
 * @since 20200208 Initial creation.
 */
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
        if ($this->app->runningInConsole()) {
            $this->commands(
                [
                    Console\Workflow\CreateWorkflow::class,
                    Console\Workflow\GenerateWorkflow::class,
                ]
            );
        }
    }
}
