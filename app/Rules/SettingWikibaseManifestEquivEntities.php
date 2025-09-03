<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SettingWikibaseManifestEquivEntities implements Rule {
    public static $entityTypes = ['properties', 'items'];

    public static $entityTypeValidation = [
        'properties' => '/^(P)\d+$/',
        'items' => '/^(Q)\d+$/',
    ];

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value) {
        $value = json_decode($value, true);

        if ($value === null) {
            return false;
        }

        foreach (self::$entityTypes as $entityType) {
            if (!array_key_exists($entityType, $value)) {
                return false;
            }

            $validationRule = self::$entityTypeValidation[$entityType];

            foreach ($value[$entityType] as $local => $wikidata) {
                // Make sure that we have a single array mapping some property to some value
                if (!preg_match($validationRule, $local) || !is_string($wikidata) || !preg_match($validationRule, $wikidata)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message() {
        return 'Value must be a JSON string mapping Wikidata Item or Property Ids to local Item or Property Ids';
    }
}
