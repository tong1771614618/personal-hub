<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class PageController extends Controller
{
    public function dashboard(Request $request)
    {
        return Inertia::render('Capture', [
            'user' => $request->user()->only('id', 'name', 'role'),
        ]);
    }

    public function board()
    {
        return Inertia::render('Board');
    }

    public function stream()
    {
        return Inertia::render('Stream');
    }

    public function review()
    {
        return Inertia::render('Review');
    }

    public function drinks()
    {
        return Inertia::render('Drinks');
    }

    public function settings()
    {
        return Inertia::render('Settings');
    }
}
