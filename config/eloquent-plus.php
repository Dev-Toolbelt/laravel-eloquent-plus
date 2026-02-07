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
];
