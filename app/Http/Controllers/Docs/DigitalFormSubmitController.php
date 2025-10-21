<?php

namespace App\Http\Controllers\Docs;

use App\Http\Requests\Docs\StoreDigitalFormSubmitRequest;
use App\Http\Requests\Docs\UpdateDigitalFormSubmitRequest;
use App\Models\Docs\DigitalFormSubmit;
use App\Models\Docs\DigitalForm;
use App\Models\Docs\DynamicInputs;
use App\Models\Docs\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\Docs\ClientDetailMail;
use App\Http\Controllers\Controller;

class DigitalFormSubmitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $inputs = DigitalFormSubmit::get();

        return view('docs.client_detail.client', compact('inputs'));
    }
public function form_detail(Request $request, $id)
{
    $digi = DigitalForm::where('id', $id)->first();
    $perPage = $request->input('per_page', 10);
    $searchQuery = $request->input('search');
    $createdAt = $request->input('created_at');

    // Base query
    $baseQuery = DigitalFormSubmit::where('form_id', $digi->id)->orderBy('created_at', 'desc');

    // Fetch all records from base query
    $records = $baseQuery->get();

    // Filter by search query if provided
    if (!empty($searchQuery)) {
        $records = $records->filter(function ($item) use ($searchQuery) {
            $data = json_decode($item->name, true); // Decode JSON to array

            return collect($data)->contains(function ($value) use ($searchQuery) {
                // Convert array values to string before using stripos
                $value = is_array($value) ? json_encode($value) : $value;
                return stripos($value, $searchQuery) !== false;
            });
        });
    }

    // Filter by created_at date if provided
    if (!empty($createdAt)) {
        $records = $records->filter(function ($item) use ($createdAt) {
            return \Carbon\Carbon::parse($item->created_at)->toDateString() === $createdAt;
        });
    }

    // Paginate the filtered results
    $page = $request->get('page', 1);
    $items = $records->forPage($page, $perPage);
    $inputs = new \Illuminate\Pagination\LengthAwarePaginator(
        $items,
        $records->count(),
        $perPage,
        $page,
        [
            'path' => $request->url(),
            'query' => $request->query(),
        ]
    );

    // Choose view based on header_status
    if ($digi->header_status == 1) {
        return view('docs.client_detail.client_detail_table', compact('inputs', 'digi', 'perPage', 'searchQuery', 'createdAt'));
    } else {
        return view('docs.client_detail.client_detail', compact('inputs', 'digi', 'perPage', 'searchQuery', 'createdAt'));
    }
}








    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function form_detail_edit($id)
    {
        $client = DigitalFormSubmit::findOrFail($id);
        return response()->json($client);
    }

    /**
     * Display the specified resource.
     */
    public function show(Digital_form_submit $digital_form_submit)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Digital_form_submit $digital_form_submit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function form_detail_update(Request $request, $id)
    {

        $formDetail = DigitalFormSubmit::findOrFail($id);

        $request->validate([
            'created_at' => 'required|date_format:Y-m-d H:i'
        ]);

        // Input jo front-end se aaya use bina timezone modify kiye save karein
        $formDetail->created_at = Carbon::createFromFormat('Y-m-d H:i', $request->created_at);

        $formDetail->save();

        return response()->json([
            'message' => 'Created At updated successfully!',
            'created_at' => $formDetail->created_at->format('Y-m-d H:i:s')
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
public function destroy($id)
{
    $clientDetail = DigitalFormSubmit::findOrFail($id);

    // Decode the stored JSON data
    $storedData = json_decode($clientDetail->name, true);

    // Check if any files exist
    if (!empty($storedData)) {
        foreach ($storedData as $files) {
            if (is_array($files)) {
                foreach ($files as $filePath) {
                    $fullPath = public_path($filePath);
                    if (file_exists($fullPath) && is_file($fullPath)) {
                        unlink($fullPath);
                    }
                }
            } else {
                $fullPath = public_path($files);
                if (file_exists($fullPath) && is_file($fullPath)) {
                    unlink($fullPath);
                }
            }
        }
    }

    $clientDetail->delete();

    return redirect()->back()->with('success', 'Client detail and associated files deleted successfully.');
}



    public function client_detail($id)
    {
        $dynamicinput = DigitalFormSubmit::where('id', $id)->first();
        $decodedData = json_decode($dynamicinput->name, true) ?? [];
        $data = Page::where('select_form_id', $dynamicinput->form_id)->first();

        if ($data == null) {
            abort('404');
        }
        $digitalform = DigitalForm::where('id', $data->select_form_id)->first();
        $dynamicinput = null;
        if ($digitalform != null) {
            $dynamicinput = DynamicInputs::where('parent_id', $digitalform->id)->where('child_id', 0)->orderBy('order', 'ASC')->get();
        }
        if($digitalform->header_status ==1)
        {
        return view('docs.client_detail.completed_detail_edittable', compact('dynamicinput', 'decodedData', 'digitalform', 'data', 'id'));
        }
        return view('docs.client_detail.completed_detail_edit', compact('dynamicinput', 'decodedData', 'digitalform', 'data', 'id'));
    }

public function update_form(Request $request, $id)
{
    $clientDetail = DigitalFormSubmit::findOrFail($id);

    $formInputs = DynamicInputs::where('parent_id', $request->form_id)
        ->where('child_id', 0)
        ->orderBy('order', 'asc')
        ->get();

    $rules = [];
    $messages = [];

    // Build validation rules (handle 31 rows if header_status == 1)
    foreach ($formInputs as $input) {
        $inputNameKey = "name.{$input->id}";
        $digital = DigitalForm::where('id', $input->parent_id)->first();

        if ($input->header_status == 0) {
            // For 31 rows, create rules per row
            for ($i = 0; $i < 31; $i++) {
                $loopedKey = "{$inputNameKey}.{$i}";

                if ($input->type === 'file') {
                    $rules[$loopedKey] = 'nullable|array';
                    $rules["{$loopedKey}.*"] = 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048';

                    $messages["{$loopedKey}.*.mimes"] = "The file type for {$input->title} in row " . ($i + 1) . " must be jpg, jpeg, png, pdf, doc, or docx.";
                    $messages["{$loopedKey}.*.max"] = "The file for {$input->title} in row " . ($i + 1) . " must not exceed 2MB.";
                } elseif ($input->type === 'checkbox') {
                    $rules[$loopedKey] = 'nullable|array';
                } else {
                    $ruleString = $input->required == 1 ? 'required|string' : 'nullable|string';

                    if (!empty($input->min_limit_input) && $input->min_limit_check == 1) {
                        $ruleString .= "|min:{$input->min_limit_input}";
                        $messages["{$loopedKey}.min"] = "The field {$input->title} in row " . ($i + 1) . " must be at least {$input->min_limit_input} characters.";
                    }

                    if (!empty($input->max_limit_input) && $input->max_limit_check == 1) {
                        $ruleString .= "|max:{$input->max_limit_input}";
                        $messages["{$loopedKey}.max"] = "The field {$input->title} in row " . ($i + 1) . " may not be greater than {$input->max_limit_input} characters.";
                    }

                    $rules[$loopedKey] = $ruleString;

                    if ($input->required == 1) {
                        $messages["{$loopedKey}.required"] = "The field {$input->title} in row " . ($i + 1) . " is required.";
                    }
                }
            }
        } else {
            // Normal inputs (header/footer)
            if ($input->type === 'file') {
                $rules[$inputNameKey] = 'nullable|array';
                $rules["{$inputNameKey}.*"] = 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048';

                $messages["{$inputNameKey}.*.mimes"] = "The file type for {$input->title} must be jpg, jpeg, png, pdf, doc, or docx.";
                $messages["{$inputNameKey}.*.max"] = "The file for {$input->title} must not exceed 2MB.";
            } elseif ($input->type === 'checkbox') {
                $rules[$inputNameKey] = 'nullable|array';
            } else {
                $ruleString = $input->required == 1 ? 'required|string' : 'nullable|string';

                if (!empty($input->min_limit_input) && $input->min_limit_check == 1) {
                    $ruleString .= "|min:{$input->min_limit_input}";
                    $messages["{$inputNameKey}.min"] = "The field {$input->title} must be at least {$input->min_limit_input} characters.";
                }

                if (!empty($input->max_limit_input) && $input->max_limit_check == 1) {
                    $ruleString .= "|max:{$input->max_limit_input}";
                    $messages["{$inputNameKey}.max"] = "The field {$input->title} may not be greater than {$input->max_limit_input} characters.";
                }

                $rules[$inputNameKey] = $ruleString;

                if ($input->required == 1) {
                    $messages["{$inputNameKey}.required"] = "The field {$input->title} is required.";
                }
            }
        }
    }

    $validatedData = $request->validate($rules, $messages);

    // Decode existing stored data or start fresh array
    $storedData = json_decode($clientDetail->name, true) ?? [];

    // Process the updated input data with 31 rows logic
    foreach ($request->name as $inputId => $inputValue) {
        $digital = DigitalForm::where('id', $request->form_id)->first();

        if ($digital && $digital->header_status == 1 && is_array($inputValue)) {
            // For inputs with 31 rows
            for ($i = 0; $i < 31; $i++) {
                if (isset($inputValue[$i])) {
                    if (is_array($inputValue[$i]) && $request->hasFile("name.$inputId.$i")) {
                        $uploadedFiles = [];

                        foreach ($request->file("name.$inputId.$i") as $file) {
                            $fileName = time() . '_' . $file->getClientOriginalName();
                            $destinationPath = public_path('uploads/clientform');
                            $file->move($destinationPath, $fileName);
                            $uploadedFiles[] = 'uploads/clientform/' . $fileName;
                        }

                        $storedData[$inputId][$i] = $uploadedFiles;
                    } else {
                        $storedData[$inputId][$i] = $inputValue[$i];
                    }
                } else {
                    // In case some rows are missing from request, keep old or empty
                    $storedData[$inputId][$i] = $storedData[$inputId][$i] ?? null;
                }
            }
        } else {
            // Normal header/footer inputs
            if ($request->hasFile("name.$inputId")) {
                $uploadedFiles = [];

                foreach ($request->file("name.$inputId") as $file) {
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $destinationPath = public_path('uploads/clientform');
                    $file->move($destinationPath, $fileName);
                    $uploadedFiles[] = 'uploads/clientform/' . $fileName;
                }

                $storedData[$inputId] = $uploadedFiles;
            } else {
                $storedData[$inputId] = $inputValue;
            }
        }
    }

    // Save updated data
    $clientDetail->name = json_encode($storedData);
    $clientDetail->form_id = $request->form_id;
    $clientDetail->page_id = $request->page_id;
    $clientDetail->save();

    return response()->json(['success' => true, 'message' => 'Client detail updated successfully'], 200);
}

    
    
    public function deleteFile(Request $request)
{
    $inputId = $request->input_id;
    $index = $request->index;
    $filePath = $request->file;

    // Find the form entry
    $clientDetail = DigitalFormSubmit::whereJsonContains('name->' . $inputId, $filePath)->first();

    if (!$clientDetail) {
        return response()->json(['success' => false, 'message' => 'File not found'], 404);
    }

    // Decode stored JSON data
    $storedData = json_decode($clientDetail->name, true);

    if (isset($storedData[$inputId]) && is_array($storedData[$inputId])) {
        // Remove file from array
        unset($storedData[$inputId][$index]);
        $storedData[$inputId] = array_values($storedData[$inputId]); // Re-index array
    } else {
        unset($storedData[$inputId]);
    }

    // Delete from folder
    if (file_exists(public_path($filePath))) {
        unlink(public_path($filePath));
    }

    // Update database
    $clientDetail->name = json_encode($storedData);
    $clientDetail->save();

    return response()->json(['success' => true, 'message' => 'File deleted successfully']);
}


public function mail_send(Request $request)
{

    $selectedIds = json_decode($request->selected_ids, true);


    if (!$selectedIds || !is_array($selectedIds)) {
        return back()->with('error', 'No clients selected.');
    }

  
    $clients = DigitalFormSubmit::whereIn('id', $selectedIds)->get();
        $form_id = DigitalForm::where('id', $request->form_id)->first();


    foreach ($clients as $client) {

        $data = json_decode($client->name, true);


        if (is_array($data)) {

            foreach ($data as $key => $value) {
         
                $dynamicEmailField = DynamicInputs::where('id', $key)->where('type', 'email')->first();

                if ($dynamicEmailField) {
                    $email = $value;

                    if ($email) {

        
                        Mail::to($email)->send(new ClientDetailMail($client, $form_id));
                    }   
                }
            }
        }
    }


    return redirect()->back()->with('success', 'Emails sent successfully.');
}

}
