<?php

namespace App\Http\Controllers\Docs;

use App\Http\Requests\Docs\StorePageRequest;
use App\Http\Requests\Docs\UpdatePageRequest;
use App\Models\Docs\DigitalForm;
use App\Models\Docs\Page;
use Illuminate\Http\Request;
use App\Models\Docs\DynamicInputs;
use App\Models\Docs\DigitalFormSubmit;
use App\Models\Docs\Incident;
use App\Http\Controllers\Controller;

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('docs.pages.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('docs.pages.create');
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'slug' => 'required',
            'select_form' => 'required',
        ], [
            'select_form.required' => 'Please select a form before submitting.',
            'slug.required' => 'link is required.',
        ]);
        $store = new Page();
        $store->title=$request->title;
        $store->desc=$request->desc;
        $store->slug=$request->slug;
        $store->select_form_id=$request->select_form;
        $store->longdesc=$request->longdesc;
        $store->save();

        return redirect()->route('page.form.index')->with('success','Page created Successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Page $page)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        $edit=Page::where('id',$request->id)->first();
        if($edit){
            return view('docs.pages.edit',compact('edit'));
        }
        abort('404');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'slug' => 'required',
            'select_form' => 'required',
        ], [
            'select_form.required' => 'Please select a form before submitting.',
            'slug.required' => 'link is required.',
        ]);
        $update=Page::findorfail($request->id);
        $update->title=$request->title;
        $update->desc=$request->desc;
        $update->select_form_id=$request->select_form;
        $update->slug=$request->slug;
        $update->longdesc=$request->longdesc;
        $update->save();

        return redirect()->route('page.form.index')->with('success','Page Updated Successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $delete=Page::find($id);
        $delete->delete();
        return redirect()->route('page.form.index')->with('success','Page Deleted Successfully');


    }

    public function form_design($id)
    {

        $data=Page::where('slug',$id)->first();
        if($data == null)
        {
            abort('404');
        }
        $digitalform=DigitalForm::where('id',$data->select_form_id)->first();
        $dynamicinput = null; 
        if($digitalform != null)
        {
            $dynamicinput =DynamicInputs::where('parent_id',$digitalform->id)->where('child_id',0)->orderBy('order','ASC')->get();
        }
        if($digitalform->header_status==0)
        {
            return view('docs.form.design',compact('dynamicinput','data','digitalform'));            
        }
        else{
            return view('docs.form.designtable',compact('dynamicinput','data','digitalform'));
        }

    }


public function form_design_submit(Request $request)
{
    $formInputs = DynamicInputs::where('parent_id', $request->form_id)
        ->where('child_id', 0)
        ->orderBy('order', 'asc')
        ->get();

    $rules = [];
    $messages = [];

    foreach ($formInputs as $input) {
        

        $inputNameKey = "name.{$input->id}";
        $digital=DigitalForm::where('id',$input->parent_id)->first();
        if ($input->header_status == 0) {
            // Handle 31 rows inputs
            for ($i = 0; $i < 31; $i++) {
                $loopedKey = "{$inputNameKey}.{$i}";

                if ($input->type === 'file') {
                    $rules[$loopedKey] = 'required|array';
                    $rules["$loopedKey.*"] = 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048';

                    $messages["$loopedKey.required"] = "The field {$input->title} in row " . ($i + 1) . " is required.";
                    $messages["$loopedKey.*.mimes"] = "The file type for {$input->title} in row " . ($i + 1) . " must be jpg, jpeg, png, pdf, doc, or docx.";
                    $messages["$loopedKey.*.max"] = "The file for {$input->title} in row " . ($i + 1) . " must not exceed 2MB.";
                } elseif ($input->type === 'checkbox') {
                    $rules[$loopedKey] = 'required';
                    $messages["$loopedKey.required"] = "Please select at least one option for {$input->title} in row " . ($i + 1) . ".";
                } else {
                    $rules[$loopedKey] = $input->required == 1 ? 'required|string' : 'nullable|string';
                    if ($input->required == 1) {
                        $messages["$loopedKey.required"] = "The field {$input->title} in row " . ($i + 1) . " is required.";
                    }

                    if (!empty($input->min_limit_input) && $input->min_limit_check == 1) {
                        $rules[$loopedKey] .= "|min:{$input->min_limit_input}";
                        $messages["$loopedKey.min"] = "The field {$input->title} in row " . ($i + 1) . " must be at least {$input->min_limit_input} characters.";
                    }

                    if (!empty($input->max_limit_input) && $input->max_limit_check == 1) {
                        $rules[$loopedKey] .= "|max:{$input->max_limit_input}";
                        $messages["$loopedKey.max"] = "The field {$input->title} in row " . ($i + 1) . " may not be greater than {$input->max_limit_input} characters.";
                    }
                }
            }
        } else {
            // Header/Footer input
            if ($input->type === 'file') {
                $rules[$inputNameKey] = 'required|array';
                $rules["{$inputNameKey}.*"] = 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048';

                $messages["{$inputNameKey}.required"] = "The field {$input->title} is required.";
                $messages["{$inputNameKey}.*.mimes"] = "The file type for {$input->title} must be jpg, jpeg, png, pdf, doc, or docx.";
                $messages["{$inputNameKey}.*.max"] = "The file for {$input->title} must not exceed 2MB.";
            } elseif ($input->type === 'checkbox') {
                $rules[$inputNameKey] = 'required';
                $messages["{$inputNameKey}.required"] = "Please select one option for {$input->title}.";
            } else {
                $rules[$inputNameKey] = $input->required == 1 ? 'required|string' : 'nullable|string';
                if ($input->required == 1) {
                    $messages["{$inputNameKey}.required"] = "The field {$input->title} is required.";
                }

                if (!empty($input->min_limit_input) && $input->min_limit_check == 1) {
                    $rules[$inputNameKey] .= "|min:{$input->min_limit_input}";
                    $messages["$inputNameKey.min"] = "The field {$input->title} must be at least {$input->min_limit_input} characters.";
                }

                if (!empty($input->max_limit_input) && $input->max_limit_check == 1) {
                    $rules[$inputNameKey] .= "|max:{$input->max_limit_input}";
                    $messages["$inputNameKey.max"] = "The field {$input->title} may not be greater than {$input->max_limit_input} characters.";
                }
            }
        }
    }

    $validatedData = $request->validate($rules, $messages);

    // Store data in structured format
    $storedData = [];

    foreach ($request->name as $inputId => $inputValue) {
        if (is_array($inputValue)) {
            // Handle 31 rows data
            foreach ($inputValue as $rowIndex => $rowValue) {
                if (is_array($rowValue) && $request->hasFile("name.$inputId.$rowIndex")) {
                    $uploadedFiles = [];

                    foreach ($request->file("name.$inputId.$rowIndex") as $file) {
                        $fileName = time() . '_' . $file->getClientOriginalName();
                        $destinationPath = public_path('uploads/clientform');
                        $file->move($destinationPath, $fileName);
                        $uploadedFiles[] = 'uploads/clientform/' . $fileName;
                    }

                    $storedData[$inputId][$rowIndex] = $uploadedFiles;
                } else {
                    $storedData[$inputId][$rowIndex] = $rowValue;
                }
            }
        } else {
            // Header/Footer input
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

    // Save into database
    $clientDetail = new Digital_form_submit();
    $clientDetail->name = json_encode($storedData);
    $clientDetail->form_id = $request->form_id;
    $clientDetail->page_id = $request->page_id;
    $clientDetail->save();

    return response()->json(['success' => true, 'message' => 'Client detail saved successfully'], 200);
}

public function incident()
{
    return view('docs.form.incident');
}

public function incident_form_submit(Request $request)
{
// Validation (basic example, aap apni zarurat k mutabiq strict kar sakte ho)
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email_address' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string|max:50',
            'event_photos' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048', // 👈 photo/file ke liye
        ]);

        $data = $request->except('event_photos');

        // File upload handling
        if ($request->hasFile('event_photos')) {
            $path = $request->file('event_photos')->store('incident_photos', 'public');
            $data['event_photos'] = $path;
        }

        // Save record
        $incident = Incident::create($data);

        return redirect()->back()->with('success', 'Incident saved successfully!');
}
    public function incident_data()
    {
        return view('docs.client_detail.incident_all_data');
    }
    
        public function incident_data_view($id)
    {
        $edit=Incident::find($id);
        return view('docs.client_detail.incident_edit',compact('edit'));
    }
}
