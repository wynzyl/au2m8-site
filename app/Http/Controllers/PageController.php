<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home', [
            'company' => [
                'name' => 'Au2m8',
                'tagline' => 'Building the web, streamlining the process, and delivering excellence.',
                'description' => 'We craft modern, performant web applications using the latest technologies.',
            ],
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
            'company' => [
                'name' => 'Au2m8',
                'founded' => 2010,
                'mission' => 'To deliver exceptional web solutions that help businesses thrive in the digital age.',
            ],
            'team' => [
                ['name' => 'Wenzel', 'role' => 'Lead Developer'],
                ['name' => 'Pat', 'role' => 'UI/UX Designer'],
                ['name' => 'Ian Benedict', 'role' => 'Backend Engineer'],
            ],
        ]);
    }

    public function contact(): View
    {
        return view('pages.contact', [
            'company' => [
                'name' => 'Au2m8',
                'email' => 'wenzel@au2m8.com',
                'phone' => '+63 (919) 095-9251',
                'address' => 'Poblacion, Urdaneta City, Pangasinan, 2428 Philippines',
            ],
        ]);
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
                    'title' => 'Healthcare Dashboard',
                    'description' => 'Real-time patient monitoring system with analytics and reporting.',
                    'image' => '/images/healthcare-dashboard.png',
                    'tags' => ['Laravel', 'Livewire', 'PostgreSQL'],
                ],
            ],
        ]);
    }
}
