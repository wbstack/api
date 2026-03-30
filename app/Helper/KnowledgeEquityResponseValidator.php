<?php

namespace App\Helper;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class KnowledgeEquityResponseValidator{
    public function validate($knowledgeEquityResponse): \Illuminate\Validation\Validator {

        return Validator::make($knowledgeEquityResponse, [
            'selectedOption' => [ 'required', 'string', Rule::in(['yes', 'no', 'unsure', 'unsaid']), ]
        ]);
    }

}
