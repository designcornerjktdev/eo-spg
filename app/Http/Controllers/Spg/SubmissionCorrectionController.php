<?php

namespace App\Http\Controllers\Spg;

use App\Domain\Submissions\Services\SubmissionCorrector;
use App\Http\Controllers\Controller;
use App\Models\FormSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubmissionCorrectionController extends Controller
{
    public function correct(Request $request, FormSubmission $submission, SubmissionCorrector $corrector): RedirectResponse
    {
        $this->authorize('correct', $submission);

        $request->validate([
            'answers' => ['required', 'array'],
            'consent_accepted' => ['sometimes', 'boolean'],
        ]);

        $corrector->correct($submission, $request->user(), $request->input('answers', []), $request->boolean('consent_accepted'));

        return redirect()
            ->route('spg.tasks.show', $submission->assignment_id)
            ->with('success', 'Entry corrected. The old row is marked as voided.');
    }

    public function cancel(Request $request, FormSubmission $submission, SubmissionCorrector $corrector): RedirectResponse
    {
        $this->authorize('correct', $submission);

        $request->validate([
            'reason' => ['required', 'string', 'min:3'],
        ]);

        $corrector->cancel($submission, $request->user(), $request->string('reason'));

        return redirect()
            ->route('spg.tasks.show', $submission->assignment_id)
            ->with('success', 'Entry cancelled.');
    }
}
