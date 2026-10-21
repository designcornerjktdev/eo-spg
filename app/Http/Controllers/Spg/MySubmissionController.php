<?php

namespace App\Http\Controllers\Spg;

use App\Http\Controllers\Controller;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MySubmissionController extends Controller
{
    public function index(Request $request): Response
    {
        $submissions = FormSubmission::query()
            ->where('submitted_by', $request->user()->id)
            ->with(['campaign', 'assignment.location', 'formTemplate.fields'])
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('spg/my-data', [
            'submissions' => $submissions,
            'todayCount' => FormSubmission::query()
                ->where('submitted_by', $request->user()->id)
                ->whereDate('submitted_at', now()->toDateString())
                ->count(),
        ]);
    }
}
