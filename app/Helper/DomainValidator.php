<?php
namespace App\Helper;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\WikiController;

class DomainValidator
{
    public array $subdomainRules;
    public string $subDomainSuffix;

    /**
     * @param  string  $subDomainSuffix
     * @param array $subdomainRules
     */
    public function __construct( string $subDomainSuffix, array $subdomainRules ) {
        $this->subDomainSuffix = $subDomainSuffix;
        $this->subdomainRules = $subdomainRules;
    }

    public function validate( $domain ) {

        $isSubdomain = WikiController::isSubDomain( $domain, $this->subDomainSuffix );

        if ($isSubdomain) {
            $subDomainSuffixLength = strlen($this->subDomainSuffix);
            $requiredSubdomainPrefixChars = 5;
            // We want at least 5 chars for the site sub domain
            // This also stops things like mail. www. pop. ETC...
            $requiredLength = $requiredSubdomainPrefixChars + $subDomainSuffixLength;
            $domainRequirements = array_merge(
                [                
                'required',
                'unique:wikis',
                'unique:wiki_domains',
                'min:' . $requiredLength,
                'regex:/^[a-z0-9-]+' . preg_quote( $this->subDomainSuffix ) . '$/',
                ],
                $this->subdomainRules
            );
        } else {
            $domainRequirements = 'required|unique:wikis|unique:wiki_domains|min:4|regex:/[a-z0-9-]+\.[a-z]+$/';
        }

        return Validator::make(['domain' => $domain ], [
            'domain' => $domainRequirements,
        ]);
    }

}
