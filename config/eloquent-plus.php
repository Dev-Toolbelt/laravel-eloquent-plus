<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Blamable Field Type
    |--------------------------------------------------------------------------
    |
    | This option defines the type of the blamable fields (created_by,
    | updated_by, deleted_by). The available options are:
    |
    | - 'integer': Uses integer validation with exists rule (default)
    | - 'string': Uses string validation without exists rule
    |
    */
    'blamable_field_type' => 'integer',

    /*
    |--------------------------------------------------------------------------
    | Blamable Field Value
    |--------------------------------------------------------------------------
    |
    | This option allows you to customize how the user identifier is retrieved
    | for blamable fields. Only used when 'blamable_field_type' is 'string'.
    |
    | Set this to a callable that receives the authenticated user and returns
    | the value to be stored in blamable fields.
    |
    | Example:
    | 'blamable_field_value' => fn($user) => $user->external_id,
    |
    */
    'blamable_field_value' => null,
];
