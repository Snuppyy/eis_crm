<?php

namespace App\Models\Traits;

trait UnescapedUnicodeJson
{
    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
