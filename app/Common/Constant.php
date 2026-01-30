<?php

namespace App\Common;

class Constant
{
    // Flags
    const PRODUCTION_FLAG = 'production';

    // Common fields
    const CREATED_BY = 'created_by';
    const UPDATED_BY = 'updated_by';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const IS_ACTIVE = 'is_active';
    const TOKEN_NAME = 'access_token';
    const TOKEN_EXPIRES_IN = 60*24; // in minutes
}
