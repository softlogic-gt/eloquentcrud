<?php
namespace SoftlogicGt\EloquentCrud\Console;

use Illuminate\Console\GeneratorCommand;

class MakeEloquentCrudCommand extends GeneratorCommand
{

    protected $name = 'make:eloquentcrud';

    protected $description = 'Create an empty Eloquent CRUD controller';
    protected $type        = 'EloquentCrudController';

    protected function getStub()
    {
        return __DIR__ . '/stubs/eloquentcrud.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Http\Controllers';
    }

    protected function buildClass($name)
    {
        $controllerNamespace = $this->getNamespace($name);

        $replace = [];

        $replace["use {$controllerNamespace}\Controller;\n"] = '';

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }
}
