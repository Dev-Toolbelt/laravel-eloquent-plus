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

    /*
    |--------------------------------------------------------------------------
    | Blamable Strict Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, a MissingModelPropertyException will be thrown if a
    | blamable column (created_by, updated_by, deleted_by) does not exist
    | on the model. When disabled, missing columns are silently skipped.
    |
    */
    'blamable_strict_mode' => false,

    /*
    |--------------------------------------------------------------------------
    | Timestamps Strict Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, a MissingModelPropertyException will be thrown if a
    | timestamp column (created_at, updated_at) does not exist on the model.
    | When disabled, missing columns are silently skipped.
    |
    */
    'timestamps_strict_mode' => false,
];
