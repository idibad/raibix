<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\FeeTransaction;
use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class DocumentDownloadController extends Controller
{
    /**
     * Download student vault document with policy ownership authorization.
     */
    public function downloadStudentDocument(int $studentId, int $documentId)
    {
        $student = Student::findOrFail($studentId);
        Gate::authorize('view', $student);

        $document = StudentDocument::where('student_id', $student->id)->findOrFail($documentId);

        $path = $document->file_url;
        $disk = Storage::disk('local')->exists($path) ? 'local' : (Storage::disk('public')->exists($path) ? 'public' : null);

        if (!$disk) {
            abort(404, 'Document file not found on storage.');
        }

        return Storage::disk($disk)->download($path, basename($path));
    }

    /**
     * Download fee payment receipt slip with challan ownership authorization.
     */
    public function downloadFeeReceipt(int $transactionId)
    {
        $transaction = FeeTransaction::with('feeChallan')->findOrFail($transactionId);
        $challan = $transaction->feeChallan;

        if (!$challan) {
            abort(404, 'Associated fee challan not found.');
        }

        Gate::authorize('view', $challan);

        $path = $transaction->receipt_path;
        if (!$path) {
            abort(404, 'No receipt attached to this transaction.');
        }

        $disk = Storage::disk('local')->exists($path) ? 'local' : (Storage::disk('public')->exists($path) ? 'public' : null);

        if ($disk) {
            return Storage::disk($disk)->download($path, basename($path));
        }

        // Check if stored in legacy public path
        $cleanPath = ltrim($path, '/');
        if (file_exists(public_path($cleanPath))) {
            return response()->download(public_path($cleanPath));
        }

        abort(404, 'Receipt file not found on storage.');
    }
}
