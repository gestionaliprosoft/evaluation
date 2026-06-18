<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

class PageController extends BaseController
{
    // Show a page by slug
    public function show(?string $slug = null)
    {
        $folder = env('HOME_BLADE_FOLDER', '');

        return match ($slug) {
            strtolower('login') => redirect('/admin/login'),
            strtolower('register') => redirect('/admin/register'),
            null => $folder ? view($folder.'.home') : view('welcome'),
            default => $folder ? view($folder.'.'.$slug) : view('welcome'),
        };
    }
}
