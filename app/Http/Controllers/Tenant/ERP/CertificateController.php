<?php

namespace App\Http\Controllers\Tenant\ERP;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use App\Models\IssuedCertificate;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CertificateController extends Controller
{
    /**
     * Display Certificate Templates and Issued Certificates.
     */
    public function index()
    {
        $templates = CertificateTemplate::all();
        
        if ($templates->isEmpty()) {
            CertificateTemplate::create([
                'name' => 'School Leaving Certificate',
                'type' => 'transfer',
                'content' => 'This is to certify that {{student_name}} son/daughter of {{father_name}} bearing Roll Number {{roll_number}} has been a student of {{school_name}} in Class {{class_name}} (Section: {{section_name}}). Their conduct during their stay at this institution was exemplary. They are leaving the school on {{issue_date}} because of completion of studies / parental relocation.',
                'variables' => ['{{student_name}}', '{{father_name}}', '{{roll_number}}', '{{class_name}}', '{{section_name}}', '{{issue_date}}', '{{reference_number}}', '{{school_name}}']
            ]);

            CertificateTemplate::create([
                'name' => 'Character Certificate',
                'type' => 'character',
                'content' => 'This is to certify that {{student_name}} son/daughter of {{father_name}} bearing Roll Number {{roll_number}} has successfully completed their academic term in Class {{class_name}} (Section: {{section_name}}). During their enrollment, they demonstrated excellent moral character, high discipline, responsibility, and an active participation in extracurricular activities.',
                'variables' => ['{{student_name}}', '{{father_name}}', '{{roll_number}}', '{{class_name}}', '{{section_name}}', '{{issue_date}}', '{{reference_number}}', '{{school_name}}']
            ]);

            CertificateTemplate::create([
                'name' => 'Certificate of Appreciation',
                'type' => 'appreciation',
                'content' => 'This is to certify that {{student_name}} son/daughter of {{father_name}} has demonstrated exceptional diligence, dedication, and academic brilliance in Class {{class_name}}. This certificate is awarded on {{issue_date}} in recognition of their outstanding performance and contribution to school life.',
                'variables' => ['{{student_name}}', '{{father_name}}', '{{roll_number}}', '{{class_name}}', '{{section_name}}', '{{issue_date}}', '{{reference_number}}', '{{school_name}}']
            ]);
            
            $templates = CertificateTemplate::all();
        }

        $issued = IssuedCertificate::with('student.user', 'template')->latest()->get();
        $students = Student::with('user', 'currentClass', 'currentSection', 'parent')->where('status', 'active')->get();

        return Inertia::render('Tenant/Admin/ERP/Students/Certificates', [
            'templates' => $templates,
            'issuedCertificates' => $issued,
            'students' => $students,
        ]);
    }

    /**
     * Store new Certificate Template.
     */
    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:transfer,character,appreciation,custom',
            'content' => 'required|string',
            'variables' => 'nullable|array',
        ]);

        CertificateTemplate::create($validated);

        return redirect()->back()->with('success', 'Certificate template saved successfully.');
    }

    /**
     * Issue Certificate to student.
     */
    public function issueCertificate(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'template_id' => 'required|exists:certificate_templates,id',
            'issue_date' => 'required|date',
        ]);

        $refNo = 'CERT-' . date('Y') . '-' . str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        IssuedCertificate::create([
            'student_id' => $validated['student_id'],
            'template_id' => $validated['template_id'],
            'issue_date' => $validated['issue_date'],
            'reference_number' => $refNo,
        ]);

        return redirect()->back()->with('success', "Certificate issued. Reference No: {$refNo}");
    }

    /**
     * Render a print/view of the issued certificate with parsed variables.
     */
    public function print(int $id)
    {
        $issued = IssuedCertificate::with('student.user', 'student.currentClass', 'student.currentSection', 'template')->findOrFail($id);
        $student = $issued->student;
        $template = $issued->template;

        $schoolName = function_exists('tenancy') && tenancy()->initialized && tenancy()->tenant ? tenancy()->tenant->name : config('app.name', 'School Portal');

        // Parse variables inside template content with matching HTML spans
        $variables = [
            '{{student_name}}' => '<span class="text-indigo-950 font-bold border-b-2 border-indigo-955/40 px-3 uppercase tracking-wide font-sans text-sm inline-block mx-1 my-0.5 select-all bg-indigo-50/50 rounded-md">' . e($student->user->name ?? 'N/A') . '</span>',
            '{{father_name}}' => '<span class="text-gray-900 font-bold border-b border-gray-300 px-1.5 inline-block mx-0.5">' . e($student->parent->father_name ?? 'N/A') . '</span>',
            '{{roll_number}}' => '<span class="font-mono bg-gray-100 border border-gray-300 px-1.5 py-0.5 rounded text-2xs font-bold text-gray-700 mx-1 select-all">' . e($student->roll_number ?? 'N/A') . '</span>',
            '{{class_name}}' => '<span class="text-indigo-900 font-extrabold font-sans mx-0.5">' . e($student->currentClass->name ?? 'N/A') . '</span>',
            '{{section_name}}' => '<span class="text-indigo-900 font-extrabold font-sans mx-0.5">' . e($student->currentSection->name ?? 'N/A') . '</span>',
            '{{issue_date}}' => '<span class="font-semibold underline text-gray-900 mx-0.5">' . e($issued->issue_date->format('d-M-Y')) . '</span>',
            '{{reference_number}}' => '<span class="font-mono bg-gray-50 border border-gray-200 px-2 py-0.5 rounded text-3xs font-bold text-gray-600 select-all">' . e($issued->reference_number) . '</span>',
            '{{school_name}}' => '<span class="font-extrabold text-indigo-950">' . e($schoolName) . '</span>',
        ];

        $parsedContent = strtr($template->content, $variables);

        return Inertia::render('Tenant/Admin/ERP/Students/PrintCertificate', [
            'issued' => $issued,
            'parsedContent' => $parsedContent,
        ]);
    }

    /**
     * Issue Certificates in bulk to students.
     */
    public function bulkIssue(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'template_id' => 'required|exists:certificate_templates,id',
            'issue_date' => 'required|date',
        ]);

        $issuedIds = [];

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, &$issuedIds) {
            foreach ($validated['student_ids'] as $studentId) {
                $refNo = 'CERT-' . date('Y') . '-' . str_pad((string) mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

                $cert = IssuedCertificate::create([
                    'student_id' => $studentId,
                    'template_id' => $validated['template_id'],
                    'issue_date' => $validated['issue_date'],
                    'reference_number' => $refNo,
                ]);

                $issuedIds[] = $cert->id;
            }
        });

        return redirect()->route('tenant.admin.certificates.bulk-print', ['ids' => $issuedIds])
            ->with('success', count($issuedIds) . ' certificates successfully issued.');
    }

    /**
     * Print multiple certificates in a bulk page layout.
     */
    public function printBulk(Request $request)
    {
        $ids = $request->input('ids');
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        if (empty($ids) || !is_array($ids)) {
            return redirect()->route('tenant.admin.certificates.index')->with('error', 'No certificates selected for printing.');
        }

        $issuedList = IssuedCertificate::with('student.user', 'student.currentClass', 'student.currentSection', 'template')
            ->whereIn('id', $ids)
            ->get();

        $schoolName = function_exists('tenancy') && tenancy()->initialized && tenancy()->tenant ? tenancy()->tenant->name : config('app.name', 'School Portal');

        $certificates = $issuedList->map(function ($issued) use ($schoolName) {
            $student = $issued->student;
            $template = $issued->template;

            $variables = [
                '{{student_name}}' => '<span class="text-indigo-955 font-bold border-b-2 border-indigo-955/40 px-3 uppercase tracking-wide font-sans text-sm inline-block mx-1 my-0.5 select-all bg-indigo-50/50 rounded-md">' . e($student->user->name ?? 'N/A') . '</span>',
                '{{father_name}}' => '<span class="text-gray-900 font-bold border-b border-gray-300 px-1.5 inline-block mx-0.5">' . e($student->parent->father_name ?? 'N/A') . '</span>',
                '{{roll_number}}' => '<span class="font-mono bg-gray-100 border border-gray-300 px-1.5 py-0.5 rounded text-2xs font-bold text-gray-700 mx-1 select-all">' . e($student->roll_number ?? 'N/A') . '</span>',
                '{{class_name}}' => '<span class="text-indigo-900 font-extrabold font-sans mx-0.5">' . e($student->currentClass->name ?? 'N/A') . '</span>',
                '{{section_name}}' => '<span class="text-indigo-900 font-extrabold font-sans mx-0.5">' . e($student->currentSection->name ?? 'N/A') . '</span>',
                '{{issue_date}}' => '<span class="font-semibold underline text-gray-900 mx-0.5">' . e($issued->issue_date->format('d-M-Y')) . '</span>',
                '{{reference_number}}' => '<span class="font-mono bg-gray-50 border border-gray-200 px-2 py-0.5 rounded text-3xs font-bold text-gray-600 select-all">' . e($issued->reference_number) . '</span>',
                '{{school_name}}' => '<span class="font-extrabold text-indigo-955">' . e($schoolName) . '</span>',
            ];

            return [
                'id' => $issued->id,
                'reference_number' => $issued->reference_number,
                'template_name' => $template->name,
                'parsedContent' => strtr($template->content, $variables),
            ];
        });

        return Inertia::render('Tenant/Admin/ERP/Students/PrintBulkCertificates', [
            'certificates' => $certificates,
        ]);
    }
}
