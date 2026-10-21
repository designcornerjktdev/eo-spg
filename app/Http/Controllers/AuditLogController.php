<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        return Inertia::render('audit/index', [
            'logs' => AuditLog::query()
                ->with('user')
                ->latest()
                ->paginate(30)
                ->withQueryString(),
        ]);
    }
}
