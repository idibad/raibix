<?php

namespace App\Http\Controllers\Tenant\ERP;

use App\Http\Controllers\Controller;
use App\Models\LibraryBook;
use App\Models\BookIssue;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class LibraryController extends Controller
{
    /**
     * Display Library catalog and issued books.
     */
    public function index()
    {
        $books = LibraryBook::all();
        $issues = BookIssue::with('book', 'student.user')->latest()->get();
        $students = Student::with('user')->where('status', 'active')->get();

        return Inertia::render('Tenant/Admin/ERP/Library/Index', [
            'books' => $books,
            'issues' => $issues,
            'students' => $students,
        ]);
    }

    /**
     * Store a new book in library catalogue.
     */
    public function storeBook(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:100|unique:library_books,isbn',
            'category' => 'nullable|string|max:100',
            'total_copies' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        $validated['available_copies'] = $validated['total_copies'];

        LibraryBook::create($validated);

        return redirect()->back()->with('success', 'Book added to catalog successfully.');
    }

    /**
     * Issue a book to a student.
     */
    public function issueBook(Request $request)
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:library_books,id',
            'student_id' => 'required|exists:students,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
        ]);

        $book = LibraryBook::findOrFail($validated['book_id']);

        if ($book->available_copies <= 0) {
            return redirect()->back()->with('error', 'Book is currently out of stock.');
        }

        DB::beginTransaction();
        try {
            BookIssue::create([
                'book_id' => $book->id,
                'student_id' => $validated['student_id'],
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'status' => 'issued',
            ]);

            $book->decrement('available_copies');

            DB::commit();
            return redirect()->back()->with('success', 'Book issued successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to issue book: ' . $e->getMessage());
        }
    }

    /**
     * Return an issued book.
     */
    public function returnBook(Request $request, int $id)
    {
        $validated = $request->validate([
            'return_date' => 'required|date',
            'fine_amount' => 'required|numeric|min:0',
        ]);

        $issue = BookIssue::findOrFail($id);
        if ($issue->status === 'returned') {
            return redirect()->back()->with('error', 'Book has already been returned.');
        }

        $book = LibraryBook::findOrFail($issue->book_id);

        DB::beginTransaction();
        try {
            $issue->update([
                'return_date' => $validated['return_date'],
                'fine_amount' => $validated['fine_amount'],
                'status' => 'returned',
            ]);

            $book->increment('available_copies');

            DB::commit();
            return redirect()->back()->with('success', 'Book returned successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to return book: ' . $e->getMessage());
        }
    }
}
