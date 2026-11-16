<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class ExecutiveLayout extends Component
{
    /**
     * Judul halaman opsional yang dipakai pada tag <title>.
     */
    public function __construct(public ?string $title = null)
    {
        //
    }

    public function render(): View
    {
        return view('layouts.executive');
    }
}
