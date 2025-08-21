<?php

namespace App\Helper;

use Illuminate\Support\Facades\Validator;

class ProfileValidator {
    public function validate($profile): \Illuminate\Validation\Validator {

        return Validator::make($profile, [
            'purpose' => 'in:data_hub,data_lab,tool_lab,test_drive,decide_later,other',
            'purpose_other' => 'string|required_if:purpose,other|missing_unless:purpose,other',
            'audience' => 'in:narrow,wide,other|required_if:purpose,data_hub|missing_unless:purpose,data_hub',
            'audience_other' => 'string|required_if:audience,other|missing_unless:audience,other',
            'temporality' => 'in:permanent,temporary,decide_later,other',
            'temporality_other' => 'string|required_if:temporality,other|missing_unless:temporality,other',
        ]);
    }
}
