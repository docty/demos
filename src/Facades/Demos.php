<?php

namespace Docty\Demos\Facades;

use Illuminate\Support\Facades\Facade;

class Demos extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'demos';
    }
}
