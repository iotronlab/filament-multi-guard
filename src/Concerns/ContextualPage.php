<?php

namespace Iotronlab\FilamentMultiGuard\Concerns;

use Filament\Facades\Filament;

trait ContextualPage
{
    public static function getRouteName(): string
    {
        $slug = static::getSlug();

        return Filament::currentContext().".pages.{$slug}";
    }
}
