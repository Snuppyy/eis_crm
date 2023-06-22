<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as BaseModel;
use App\Models\Traits\UnescapedUnicodeJson;

abstract class Model extends BaseModel
{
    use UnescapedUnicodeJson;
}
