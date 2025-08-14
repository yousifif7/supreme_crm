<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;

class DocumentController extends Controller
{
    

    public function report(Request $request)
{
    $startDate = $request->start_date;
    $endDate   = $request->end_date;

    $documents = collect(); // empty collection by default

    if ($startDate && $endDate) {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $documents = Document::with('user')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    return view('employees.doc_report', compact('documents', 'startDate', 'endDate'));
}

}
