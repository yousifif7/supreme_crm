<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Restriction;

class SettingController extends Controller
{
    // Show all restrictions with toggle switch
    public function index()
    {
        $restrictions = Restriction::all();
        return view('settings.restrictions', compact('restrictions'));
    }

    // Toggle is_active status
    public function toggle($id)
    {
        $restriction = Restriction::findOrFail($id);
        $restriction->is_active = !$restriction->is_active;
        $restriction->save();

        return redirect()->back()->with('success', 'Restriction status updated.');
    }
}
