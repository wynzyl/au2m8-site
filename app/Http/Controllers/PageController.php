<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home', [
            'services' => [
                ['name' => 'Web Development', 'description' => 'Custom web applications built with Laravel and modern frameworks'],
                ['name' => 'UI/UX Design', 'description' => 'Beautiful, intuitive interfaces that users love'],
                ['name' => 'API Development', 'description' => 'Robust, scalable APIs for your applications'],
            ],
        ]);
    }

    public function about(): View
    {
        return view('pages.about', [
            'team' => [
                ['name' => 'Wenzel', 'role' => 'Lead Developer'],
                ['name' => 'Pat', 'role' => 'UI/UX Designer'],
                ['name' => 'Ian Benedict', 'role' => 'Backend Engineer'],
            ],
        ]);
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function projects(): View
    {
        return view('pages.projects', [
            'projects' => Project::query()->published()->ordered()->get(),
        ]);
    }
}
