<?php

namespace App\Http\Controllers\Docs;


use App\Models\Docs\DigitalForm;
use App\Models\Docs\DigitalFormTranslation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DigitalFormController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
        return view('docs.digitalform.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('docs.digitalform.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {      
        $add = new digitalform();
        $add->title = $request->title;
        $add->desc = $request->desc;
        $add->success_message = $request->success_message;
        $add->failure_message = $request->failure_message;
        $add->receiver_mail = $request->receiver_mail;
        $add->mail_desc = $request->mail_desc;
    
        // Save header toggle
        $add->header_status = $request->has('header_status') ? 1 : 0;
    
        // If header is ON, save extra invoice fields
        if ($request->has('header_status')) {
            $add->invoice_to = $request->invoice_to;
            $add->invoice_from = $request->invoice_from;
            $add->sia = $request->sia;
            $add->vat = $request->vat;
            $add->tax_date = $request->tax_date;
            $add->invoice_number = $request->invoice_number;
            $add->terms = $request->terms;
            $add->due_date = $request->due_date;
            $add->invoice_date = $request->invoice_date;
        }
    
        $add->order = digitalform::count() + 1;
        $add->save();
    
        return redirect()->route('digital.form.index')->with('success', 'Digital Form has been added successfully');
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\digitalform  $digitalform
     * @return \Illuminate\Http\Response
     */
    public function show(digitalform $digitalform)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\digitalform  $digitalform
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $career = digitalform::where('id', $id)->first();
        if ($career != null) {
            return view('docs.digitalform.edit', compact('career'));
        }
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\Request  $request
     * @param  \App\Models\digitalform  $digitalform
     * @return \Illuminate\Http\Response
     */
public function update(Request $request)
{
    $form = digitalform::findOrFail($request->id);

    $form->title = $request->title;
    $form->desc = $request->desc;
    $form->success_message = $request->success_message;
    $form->failure_message = $request->failure_message;
    $form->receiver_mail = $request->receiver_mail;
    $form->mail_desc = $request->mail_desc;


    $form->header_status = $request->has('header_status') ? 1 : 0;

    if ($request->has('header_status')) {
        $form->invoice_to = $request->invoice_to;
        $form->invoice_from = $request->invoice_from;
        $form->sia = $request->sia;
        $form->vat = $request->vat;
        $form->tax_date = $request->tax_date;
        $form->invoice_number = $request->invoice_number;
        $form->terms = $request->terms;
        $form->due_date = $request->due_date;
        $form->invoice_date = $request->invoice_date;
    } else {
        $form->invoice_to = null;
        $form->invoice_from = null;
        $form->sia = null;
        $form->vat = null;
        $form->tax_date = null;
        $form->invoice_number = null;
        $form->terms = null;
        $form->due_date = null;
        $form->invoice_date = null;
    }

    $form->save();

    return redirect()->route('digital.form.index')->with('success', 'Digital Form has been updated successfully');
}


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\digitalform  $digitalform
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $barnd = digitalform::findOrFail($id);
        if(count($barnd->parent_digi)>0)
        {
                        return back()->with('error','please deleted the child first');
        }
        if (digitalform::destroy($id)) {
            return back()->with('success','Career Data has been deleted Successfully');
        }
    }
    
        public function updateOrder(Request $request)
    {
        foreach ($request->order as $key => $itemId) {
            digitalform::where('id', $itemId)->update(['order' => $key + 1]);
        }
    
            return response()->json([
            'success'=> true,
            'message' => 'Digital form order updated successfully.',
        ]);
    }

    public function updateStatus(Request $request)
{
    try {
        $item = digitalform::findOrFail($request->id);
        $item->paginate_status = $request->status;
        $item->save();

        return response()->json(['success' => true, 'message' => 'pagination Status updated successfully.']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Error updating status.']);
    }
}
}
