<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PublicSiteController extends Controller
{
    public function home(): Response
    {
        return $this->renderPage('home');
    }

    public function about(): Response
    {
        return $this->renderPage('about');
    }

    public function services(): Response
    {
        return $this->renderPage('services');
    }

    public function information(): Response
    {
        return $this->renderPage('information');
    }

    public function contact(): Response
    {
        return $this->renderPage('contact');
    }

    private function renderPage(string $page): Response
    {
        return Inertia::render('Public/Site', ['page' => $page]);
    }
}
