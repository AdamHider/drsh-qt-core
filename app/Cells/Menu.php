<?php

namespace App\Cells;

use CodeIgniter\View\Cells\Cell;

class Menu extends Cell
{
    public $data;
    public function render(): string
    {
        $menus = [
            [
                'title' => 'Dashboard',
                'link'  => '/admin/dashboard',
                'icon'  => 'speedometer2',
                'type'  => 'menu'
            ],
            [
                'title' => 'Lessons',
                'link'  => '/admin/lessons',
                'icon'  => 'files',
                'type'  => 'menu'
            ],
            [
                'title' => 'Menus',
                'link'  => '/admin/menus',
                'icon'  => 'list-nested',
                'type'  => 'menu'
            ],
            [
                'type'  => 'separator'
            ],
            [
                'title' => 'Languages',
                'link'  => '/admin/languages',
                'icon'  => 'translate',
                'type'  => 'menu'
            ],
        ];
        $this->data['items'] =  $menus;
        $this->setActive();
        return view('cells/menu', $this->data);
    }

    private function setActive()
    {
        foreach($this->data['items'] as &$menu){
            if(isset($menu['link']) && strpos($this->data['current_uri'], $menu['link']) !== false){
                $menu['is_active'] = true;
                return;
            }
        }
    }
}