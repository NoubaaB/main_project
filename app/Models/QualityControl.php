<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class QualityControl extends Model
{
    use HasFactory, SoftDeletes;

    const GRADE_EXTRA = 'extra';
    const GRADE_A = 'a';
    const GRADE_B = 'b';
    const GRADE_NON_ADMIS = 'non_admis';

    const GRADE_SCORES = [
        'extra' => 3,
        'a' => 2,
        'b' => 1,
        'non_admis' => 0,
    ];

    const CRITERIA = [
        'skin_grade', 'eye_grade', 'gills_grade', 'abdomen_grade',
        'backbone_grade', 'organs_grade', 'texture_grade', 'odor_grade',
    ];

    protected $fillable = [
        'production_id',
        'lot_id',
        'species',
        'quantity_kg',
        'catch_date',
        'storage_type',
        'inspection_type',
        'inspector_name',
        'inspection_date',
        'temperature',
        'skin_grade',
        'eye_grade',
        'gills_grade',
        'abdomen_grade',
        'backbone_grade',
        'organs_grade',
        'texture_grade',
        'odor_grade',
        'overall_grade',
        'decision',
        'notes',
        'corrective_action',
        'reinspection_required',
        'approved_by',
        'approval_status',
        'created_by',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'catch_date' => 'date',
        'temperature' => 'decimal:2',
        'quantity_kg' => 'decimal:2',
        'reinspection_required' => 'boolean',
    ];

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Compute overall grade and decision from the 8 criteria.
     * Rule: overall = MIN score of all filled criteria.
     * If any criterion = NON_ADMIS -> rejected.
     */
    public function computeGrade(): array
    {
        $grades = [];
        foreach (self::CRITERIA as $criterion) {
            $val = $this->{$criterion};
            if ($val !== null) {
                $grades[] = self::GRADE_SCORES[$val] ?? null;
            }
        }

        if (empty($grades)) {
            return ['overall_grade' => null, 'decision' => null];
        }

        $minScore = min($grades);

        $overallGrade = match ($minScore) {
            3 => self::GRADE_EXTRA,
            2 => self::GRADE_A,
            1 => self::GRADE_B,
            0 => self::GRADE_NON_ADMIS,
            default => null,
        };

        $decision = match ($overallGrade) {
            self::GRADE_EXTRA => 'accepted_premium',
            self::GRADE_A => 'accepted',
            self::GRADE_B => 'accepted_restricted',
            self::GRADE_NON_ADMIS => 'rejected',
            default => null,
        };

        return ['overall_grade' => $overallGrade, 'decision' => $decision];
    }
}
