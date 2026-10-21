<?php

namespace App\Http\Controllers\Pages;

use App\Enums\CategoryType;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\EventResource;
use App\Models\Category;
use App\Models\Event;
use App\Models\Speaker;
use Inertia\Inertia;

class ContactUsController extends Controller
{
    public function contact_us()
    {
        return Inertia::render('ContactUs/ContactUs');
    }

    public function save()
    {
        return to_route('home')->with(['success' => '¡Consulta recibida! Te responderemos lo antes posible.']);
    }
}
