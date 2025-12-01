<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class ImageCapture extends Component
{
    public string $name;
    public string $label;
    public ?string $value;

    /**
     * Create a new component instance.
     */
    public function __construct(string $name, string $label, ?string $value = null)
    {
        $this->name = $name;
        $this->label = $label;
        $this->value = $value;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.image-capture');
    }
}
