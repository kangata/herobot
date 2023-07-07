<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Integration::class);
    }

    public function index(Request $request)
    {
        return inertia('Integrations/Index');
    }

    public function create(Request $request)
    {
        return inertia('Integrations/Create');
    }
}
