<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class CustomWelcomeWidget extends Widget
{
    protected static string $view = 'filament.widgets.custom-welcome-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return true;
    }
}
