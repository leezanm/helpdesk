<?php

namespace App\View\Components;

use Illuminate\View\Component;

class SidebarDivider extends Component
{   

    public $title;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($title ='TITLE')
    {
        //
        $this->title = $title;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.sidebar-divider');
    }
}
