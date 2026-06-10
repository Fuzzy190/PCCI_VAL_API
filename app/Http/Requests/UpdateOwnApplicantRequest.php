<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOwnApplicantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('member');
    }

    protected function prepareForValidation()
    {
        $flattened = [];

        // 1. Basic Profile
        if ($this->has('basic_profile') && is_array($this->basic_profile)) {
            $flattened = array_merge($flattened, $this->basic_profile);
            if (isset($this->basic_profile['business_location']) && is_array($this->basic_profile['business_location'])) {
                $flattened = array_merge($flattened, $this->basic_profile['business_location']);
            }
        }

        // 2. Official Representative (Extracting Designation & Contact)
        if ($this->has('official_representative') && is_array($this->official_representative)) {
            $rep = $this->official_representative;
            if (isset($rep['first_name']))  $flattened['rep_first_name'] = $rep['first_name'];
            if (isset($rep['surname']))     $flattened['rep_surname'] = $rep['surname'];
            if (isset($rep['contact_no']))  $flattened['rep_contact_no'] = $rep['contact_no'];
            if (isset($rep['designation'])) $flattened['rep_designation'] = $rep['designation'];
        }

        // 3. Organization Membership (Extracting Ownership Type)
        if ($this->has('organization_membership') && is_array($this->organization_membership)) {
            $org = $this->organization_membership;
            if (isset($org['type_of_company'])) $flattened['type_of_company'] = $org['type_of_company'];
        }

        // 4. Business Additional Data (Extracting Industry, Tagline, About, Hours)
        if ($this->has('business_additional_data') && is_array($this->business_additional_data)) {
            $flattened = array_merge($flattened, $this->business_additional_data);
        }

        if (!empty($flattened)) {
            $this->merge($flattened);
        }
    }

    public function rules(): array
    {
        return [
            'registered_business_name' => ['nullable', 'string', 'max:255'],
            'trade_name'               => ['nullable', 'string', 'max:255'],
            'business_tagline'         => ['nullable', 'string', 'max:255'],
            'about_description'        => ['nullable', 'string'],
            'industry'                 => ['nullable', 'string', 'max:255'],
            'type_of_company'          => ['nullable', 'string', 'max:255'], // Ownership Type
            'rep_designation'          => ['nullable', 'string', 'max:255'], // Designation

            'telephone_no'             => ['nullable', 'string', 'max:255'],
            'rep_contact_no'           => ['nullable', 'string', 'max:255'],
            'website_socmed'           => ['nullable', 'string', 'max:255'],
            'business_address'         => ['nullable', 'string', 'max:255'],
            'city_municipality'        => ['nullable', 'string', 'max:255'],
            'province'                 => ['nullable', 'string', 'max:255'],

            'business_hours'           => ['nullable', 'array'],
            'tags'                     => ['nullable', 'array'],

            'photo'                    => ['nullable'],
            'mayors_permit'            => ['nullable'],
            'dti_sec'                  => ['nullable'],
        ];
    }
}
