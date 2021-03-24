<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SettingWikibaseManifestEquivEntities implements Rule
{

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $value = json_decode( $value, true );
        if($value===null) {
            return false;
        }
        foreach ( $value as $key => $value ) {
            if (
                // Make sure that we have a single array mapping some property to some value
                !preg_match( '/^(Q|P)\d+$/', $key ) || !is_string($value) || !preg_match( '/^(Q|P)\d+$/', $value ) ||
                // And make sure that we map the same entity types together
                ( substr( $key, 0, 1 ) !== substr( $value, 0, 1 ) )
                ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Value must be a JSON string mapping Wikidata Item or Property Ids to local Item or Property Ids';
    }
}
