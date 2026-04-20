<?php

namespace App\Http\Controllers;

use App\Models\EnrollRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnrollController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): View
    {
        $enrollRequests = EnrollRequest::query()
            ->latest('id')
            ->limit(20)
            ->get();

        return view('enroll.index', [
            'enrollRequests' => $enrollRequests,
        ]);
    }
}
