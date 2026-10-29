<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(): View
    {
        return view('home.reports');
    }
}
