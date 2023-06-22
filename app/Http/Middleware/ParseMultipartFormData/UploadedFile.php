<?php

namespace App\Http\Middleware\ParseMultipartFormData;

use Illuminate\Http\UploadedFile as LaravelUploadedFile;

class UploadedFile extends LaravelUploadedFile
{
    private $nonNative = false;

    /**
     * Set file as being non-native PHP uploaded file.
     */
    public function setNonNative()
    {
        $this->nonNative = true;
    }

    /**
     * Returns whether the file was uploaded successfully.
     *
     * @return bool True if the file has been uploaded with HTTP and no error occurred
     */
    public function isValid()
    {
        return $this->nonNative || parent::isValid();
    }
}
