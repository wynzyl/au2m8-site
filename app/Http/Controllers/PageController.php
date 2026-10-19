<?php

namespace App\Http\Controllers;

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
            'projects' => [
                [
                    'title' => 'School Management System',
                    'description' => 'Complete student information system with enrollment, grades, billing, and reporting.',
                    'image' => '/images/Merryland.png',
                    'tags' => ['NextJS', 'React', 'PostgreSQL'],
                ],
                [
                    'title' => 'Healthcare Management System',
                    'description' => 'Real-time patient monitoring system with analytics and reporting.',
                    'image' => '/images/healthcare-dashboard.png',
                    'tags' => ['Laravel', 'Livewire', 'PostgreSQL'],
                ],
            ],
        ]);
    }
}
