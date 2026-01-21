<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Tests;

use Laravel\Surveyor\SurveyorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use OybekDaniyarov\LaravelTrpc\TrpcServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            SurveyorServiceProvider::class,
            TrpcServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('trpc.output_path', '/tmp/trpc-test/api');
        $app['config']->set('trpc.api_prefix', 'api');
        $app['config']->set('trpc.postman.output_path', '/tmp/trpc-test/postman');
    }
}
