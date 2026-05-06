<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\QualityControl;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;

class QualityControlController extends Controller
{
    use BroadcastsModelChanges;

    private function gradeRule(): string
    {
        return 'nullable|in:extra,a,b,non_admis';
    }

    /**
     * Get all QC records for a specific production.
     */
    public function show(Production $production)
    {
        $qcs = $production->qualityControls()->with('creator', 'approver')->get();
        return response()->json(['quality_controls' => $qcs]);
    }

    /**
     * Create a QC record for a production (one per species).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'production_id' => 'required|exists:productions,id',
            'lot_id' => 'nullable|string|max:255',
            'species' => 'nullable|string|max:255',
            'quantity_kg' => 'nullable|numeric|min:0',
            'catch_date' => 'nullable|date',
            'storage_type' => 'nullable|in:fresh,chilled,frozen',
            'inspection_type' => 'nullable|in:reception,pre_freezing,post_freezing,recheck,export_release',
            'inspector_name' => 'nullable|string|max:255',
            'inspection_date' => 'required|date',
            'temperature' => 'nullable|numeric',
            'skin_grade' => $this->gradeRule(),
            'eye_grade' => $this->gradeRule(),
            'gills_grade' => $this->gradeRule(),
            'abdomen_grade' => $this->gradeRule(),
            'backbone_grade' => $this->gradeRule(),
            'organs_grade' => $this->gradeRule(),
            'texture_grade' => $this->gradeRule(),
            'odor_grade' => $this->gradeRule(),
            'notes' => 'nullable|string|max:2000',
            'corrective_action' => 'nullable|string|max:2000',
            'reinspection_required' => 'nullable|boolean',
        ]);

        $data['created_by'] = $request->user()?->id;
        $data['approval_status'] = 'pending';

        $qc = QualityControl::create($data);

        $result = $qc->computeGrade();
        $qc->update($result);

        $qc->load('creator', 'approver');
        $this->broadcastChange('QualityControl', 'created', $qc->id, $qc->toArray());

        return response()->json(['quality_control' => $qc], 201);
    }

    /**
     * Update an existing QC record.
     */
    public function update(Request $request, QualityControl $qualityControl)
    {
        $data = $request->validate([
            'lot_id' => 'nullable|string|max:255',
            'species' => 'nullable|string|max:255',
            'quantity_kg' => 'nullable|numeric|min:0',
            'catch_date' => 'nullable|date',
            'storage_type' => 'nullable|in:fresh,chilled,frozen',
            'inspection_type' => 'nullable|in:reception,pre_freezing,post_freezing,recheck,export_release',
            'inspector_name' => 'nullable|string|max:255',
            'inspection_date' => 'required|date',
            'temperature' => 'nullable|numeric',
            'skin_grade' => $this->gradeRule(),
            'eye_grade' => $this->gradeRule(),
            'gills_grade' => $this->gradeRule(),
            'abdomen_grade' => $this->gradeRule(),
            'backbone_grade' => $this->gradeRule(),
            'organs_grade' => $this->gradeRule(),
            'texture_grade' => $this->gradeRule(),
            'odor_grade' => $this->gradeRule(),
            'notes' => 'nullable|string|max:2000',
            'corrective_action' => 'nullable|string|max:2000',
            'reinspection_required' => 'nullable|boolean',
        ]);

        $qualityControl->update($data);

        $result = $qualityControl->computeGrade();
        $qualityControl->update($result);

        $qualityControl->load('creator', 'approver');
        $this->broadcastChange('QualityControl', 'updated', $qualityControl->id, $qualityControl->toArray());

        return response()->json(['quality_control' => $qualityControl]);
    }

    /**
     * Approve / reject a QC record.
     */
    public function approve(Request $request, QualityControl $qualityControl)
    {
        $data = $request->validate([
            'approval_status' => 'required|in:approved,rejected,needs_reinspection',
        ]);

        $qualityControl->update([
            'approval_status' => $data['approval_status'],
            'approved_by' => $request->user()?->id,
        ]);

        $qualityControl->load('creator', 'approver');
        $this->broadcastChange('QualityControl', 'updated', $qualityControl->id, $qualityControl->toArray());

        return response()->json(['quality_control' => $qualityControl]);
    }

    /**
     * Delete a QC record.
     */
    public function destroy(QualityControl $qualityControl)
    {
        $id = $qualityControl->id;
        $productionId = $qualityControl->production_id;
        $qualityControl->delete();
        $this->broadcastChange('QualityControl', 'deleted', $id, ['production_id' => $productionId]);

        return response()->json(['message' => 'Quality control record deleted']);
    }
}
