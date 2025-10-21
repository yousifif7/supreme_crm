<?php

namespace App\Http\Controllers\Docs;

use App\Models\Docs\DynamicInputs;
use App\Models\Docs\DigitalForm;
use App\Models\Docs\DynamicInputTranslation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DynamicInputsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {
        $form=DigitalForm::find($id);
        $dynamic=DynamicInputs::where('parent_id',$form->id)->where('child_id',0)->orderBy('order','Asc')->get();
        return view('docs.dynamicinput.index',compact('form','dynamic'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($id)
    {
        $create=DigitalForm::find($id);
        return view('docs.dynamicinput.create',compact('create'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
     
         $dynamic = new DynamicInputs();
         $dynamic->title = $request->input('title');
         $dynamic->info = $request->input('info');
         $dynamic->placeholder = $request->input('placeholder');
        $dynamic->desc = $request->input('desc');
                  
         $dynamic->value = $request->input('value');
         $dynamic->type = $request->input('type');
        $dynamic->min_limit_check = $request->has('min_limit_check');
        $dynamic->min_limit_input = (int) $request->input('min_limit_input', 0); 
        $dynamic->max_limit_check = $request->has('max_limit_check');
        $dynamic->max_limit_input = (int) $request->input('max_limit_input', 0);
        $dynamic->send_email = $request->has('send_email') ? 1 : 0; 
        $dynamic->required = $request->input('required') ? 1 : 0; 
        $dynamic->unique = $request->input('unique') ? 1 : 0; 
        $dynamic->options = $request->input('options');
        $dynamic->parent_id = $request->parent_id;
        $dynamic->order = DynamicInputs::count() + 1;
        $dynamic->label_status = $request->input('label_status') ? 1 : 0; 
        $dynamic->header_status = $request->input('header_status');
         $dynamic->set_design = $request->input('set_design');
         $dynamic->save();     
         return redirect()->route('digital.form.fields',$request->parent_id)->with('success', 'Dynamic input updated successfully.');

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DynamicInputs  $DynamicInputs
     * @return \Illuminate\Http\Response
     */
    public function show(DynamicInputs $dynamic_inputs)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DynamicInputs  $DynamicInputs
     * @return \Illuminate\Http\Response
     */
    function edit(Request $request, $id)
     {
         $dynamicInput = DynamicInputs::where('id', $id)->first();
         if ($dynamicInput != null) {
            return view('docs.dynamicinput.edit',compact('dynamicInput'));
         }
         abort(404);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\Request  $request
     * @param  \App\Models\DynamicInputs  $DynamicInputs
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DynamicInputs $dynamic_inputs)
    {
           
        $dynamic =DynamicInputs::findOrFail($request->id);

        if (count($dynamic->child) > 0 ) {
        
            if(in_array($dynamic->type, ['drop', 'radio', 'checkbox']))
            {

                    $dynamic->type = $dynamic->type;   
            }
            else{
                $dynamic->type = $request->input('type');            
            }
            
        } else {

            $dynamic->type = $request->input('type');
        }
        


         $dynamic->value = $request->input('value');
                     $dynamic->title = $request->input('title');
            $dynamic->info = $request->input('info');
            $dynamic->placeholder = $request->input('placeholder');
            $dynamic->desc = $request->input('desc');

            $dynamic->min_limit_check = $request->has('min_limit_check');
            $dynamic->min_limit_input = (int) $request->input('min_limit_input', 0); 
            $dynamic->max_limit_check = $request->has('max_limit_check');
            $dynamic->max_limit_input = (int) $request->input('max_limit_input', 0);
            $dynamic->send_email = $request->has('send_email') ? 1 : 0; 
            $dynamic->required = $request->input('required') ? 1 : 0; 
            $dynamic->unique = $request->input('unique') ? 1 : 0; 
            $dynamic->options = $request->input('options');
            $dynamic->parent_id = $request->parent_id;
            $dynamic->label_status = $request->input('label_status') ? 1 : 0; 
            $dynamic->header_status = $request->input('header_status');
            $dynamic->set_design = $request->input('set_design');
            $dynamic->save();
    
        return redirect()->route('digital.form.fields',$request->parent_id)->with('success', 'Dynamic input updated successfully.');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\dynamic_inputs  $dynamic_inputs
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $barnd = DynamicInputs::findOrFail($id);
        if(count($barnd->child)>0)
        {
            return back()->with('error','please deleted the child first');
        }
        if (DynamicInputs::destroy($id)) {
            return back()->with('success','Career Data has been deleted Successfully');
        }
    }
    
            public function updateOrderfield(Request $request)
    {
        foreach ($request->order as $key => $itemId) {
            DynamicInputs::where('id', $itemId)->update(['order' => $key + 1]);
        }
    
            return response()->json([
            'success'=> true,
            'message' => 'Digital form order updated successfully.',
        ]);
    }
    public function childindex(Request $request,$id)
     {
         $ids=DynamicInputs::find($request->id);
         $child = DynamicInputs::findorfail($id)->child()->orderBy('order', 'ASC')->get();
         $inputs = DynamicInputs::withCount(['child'])->where('child_id', $id)->orderBy('order', 'ASC')->get();
        $dynamic=DynamicInputs::where('child_id',$ids->id)->get();
         return view('docs.dynamicinput.child.index',compact('child','inputs','ids','dynamic'));
        
     }
     
     public function childcreate(Request $request)
     {
        $id=DynamicInputs::find($request->id);

        $port = DynamicInputs::where('child_id', '=', 0)->get();
        $allMenus = DynamicInputs::get();
         
         return view('docs.dynamicinput.child.create',compact('port','allMenus','id'));
     }
     
          public function childstore(Request $request)
     {
     
            // Save main dynamic input
            $dynamic = new dynamic_inputs();
            $dynamic->title = $request->input('title');
            $dynamic->type = $request->input('type');
            $dynamic->child_id = $request->input('child_id');
            $dynamic->parent_id = $request->input('parent_id');
            $dynamic->order = DynamicInputs::count() + 1;
     
            $dynamic->save();
     
                return redirect()->route('digital.form.fields.child', ['id' => $request->child_id])->with('success','Data has been added Successfully');
}


    public function childedit(Request $request, $id)
     {
         $menus = DynamicInputs::where('id', $id)->first();
         if ($menus != null) {
             return view('docs.dynamicinput.child.edit', compact('menus'));
         }
         abort(404);
    }
    
    
    public function childupdate(Request $request)
     {

        $dynamic =DynamicInputs::findOrFail($request->id);
        $dynamic->title = $request->input('title');
        $dynamic->type = $request->input('type');
        $dynamic->child_id = $request->input('child_id');
        $dynamic->parent_id = $request->input('parent_id');
        $dynamic->save();
          
        return redirect()->route('digital.form.fields.child', ['id' => $request->child_id])->with('success','Data has been added Successfully');

     }
     
         public function childdestroy($id)
    {
        $barnd = DynamicInputs::findOrFail($id);
        $barnd->delete();
            return back()->with('success','Career Data has been deleted Successfully');
    }

            public function updateOrderfieldchild(Request $request)
    {
        foreach ($request->order as $key => $itemId) {
            DynamicInputs::where('id', $itemId)->update(['order' => $key + 1]);
        }
    
            return response()->json([
            'success'=> true,
            'message' => 'Digital form order updated successfully.',
        ]);
    }
    public function updateStatus(Request $request)
{
    try {
        $item = DynamicInputs::findOrFail($request->id);
        $item->others = $request->status;
        $item->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Error updating status.']);
    }
}
    
}
