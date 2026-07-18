<?php

namespace App\Http\Controllers;

class PublicPageController extends Controller
{
    public function index()
    {
        return view('public.index');
    }
}
