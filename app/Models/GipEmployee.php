<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class GipEmployee extends Model
{
    use HasFactory;

    protected $table = 'gip_employees';

    protected $fillable = [
        // Name
        'family_name', 'first_name', 'middle_name',
        // Contact
        'residential_address', 'telephone_no', 'mobile_no', 'email',
        // Birth
        'place_of_birth', 'date_of_birth',
        // Demographics
        'gender', 'civil_status',
        // JSON arrays ← these MUST be in fillable AND cast as 'array'
        'educational_attainment',
        'work_experience',
        // Vulnerable groups
        'is_pwd', 'is_ip', 'is_disaster_victim',
        'is_armed_conflict_victim', 'is_rebel_returnee', 'is_4ps_beneficiary',
        'other_vulnerable_group',
        // Contract
        'office_id', 'contract_start_date', 'contract_end_date',
        'original_start_date', 'previous_end_date', 'renewal_count', 'status',
        // Emergency
        'emergency_contact_name', 'emergency_contact_details', 'emergency_contact_address',
        // GSIS
        'gsis_beneficiary_name', 'gsis_beneficiary_relationship',
        // DOLE use
        'interviewed_by', 'date_accomplished',
        'doc_birth_cert', 'doc_transcript', 'doc_barangay_cert',
        'doc_form_137', 'doc_diploma', 'doc_school_cert', 'doc_other',
        // PSOC & photo
        'psoc_code', 'photo_path',
    ];

    protected $casts = [
        // ✅ THE FIX — cast array fields so Laravel auto JSON-encodes on save
        'educational_attainment'   => 'array',
        'work_experience'          => 'array',

        // Dates
        'date_of_birth'            => 'date',
        'contract_start_date'      => 'date',
        'contract_end_date'        => 'date',
        'original_start_date'      => 'date',
        'previous_end_date'        => 'date',
        'date_accomplished'        => 'date',

        // Booleans
        'is_pwd'                   => 'boolean',
        'is_ip'                    => 'boolean',
        'is_disaster_victim'       => 'boolean',
        'is_armed_conflict_victim' => 'boolean',
        'is_rebel_returnee'        => 'boolean',
        'is_4ps_beneficiary'       => 'boolean',
        'doc_birth_cert'           => 'boolean',
        'doc_transcript'           => 'boolean',
        'doc_barangay_cert'        => 'boolean',
        'doc_form_137'             => 'boolean',
        'doc_diploma'              => 'boolean',
        'doc_school_cert'          => 'boolean',
    ];

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function getRemainingMonthsAttribute(): int
    {
        $start = $this->original_start_date ?? $this->contract_start_date;
        if (!$start || !$this->contract_end_date) return 12;
        return max(0, 12 - Carbon::parse($start)->diffInMonths($this->contract_end_date));
    }

    public function getTotalMonthsUsedAttribute(): int
    {
        $start = $this->original_start_date ?? $this->contract_start_date;
        if (!$start || !$this->contract_end_date) return 0;
        return Carbon::parse($start)->diffInMonths($this->contract_end_date);
    }
}