<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TrainingMaterial;
use App\Models\TrainingAcknowledgement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainingController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $materials = TrainingMaterial::with(['acknowledgements' => function ($query) use ($userId) {
            $query->where('user_id', $userId);
        }])->get();

        $response = $materials->map(function ($material) use ($userId) {
            $ack = $material->acknowledgements->first();
            return [
                'id' => $material->id,
                'title' => $material->title,
                'type' => $material->type,
                'content_url' => $material->content_url,
                'pdf_url' => $material->pdf_url,
                'required' => $material->required,
                'acknowledged' => $ack ? true : false,
                'expiry_date' => $material->expiry_date,
                'created_at' => $material->created_at,
            ];
        });

        return response()->json(['materials' => $response]);
    }

    public function acknowledge(Request $request, $id)
    {
        $request->validate([
            'acknowledged_at' => 'required|date',
            'completion_time_seconds' => 'required|integer|min:1',
        ]);

        $material = TrainingMaterial::findOrFail($id);

        $ack = TrainingAcknowledgement::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'training_material_id' => $id,
            ],
            [
                'acknowledged_at' => $request->acknowledged_at,
                'completion_time_seconds' => $request->completion_time_seconds,
            ]
        );

        return response()->json(['message' => 'Acknowledged successfully.']);
    }
}
