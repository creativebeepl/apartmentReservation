<?php

declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        // Wszystkie momenty w bazie trzymamy w UTC; Doctrine czyta daty w domyślnej strefie PHP.
        date_default_timezone_set('UTC');

        parent::boot();
    }
}
