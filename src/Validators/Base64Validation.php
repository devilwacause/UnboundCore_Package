<?php

namespace Devilwacause\UnboundCore\Validators;

use Illuminate\Contracts\Validation\Validator;
use Symfony\Component\HttpFoundation\File\File;
use Illuminate\Validation\Concerns\ValidatesAttributes;

class Base64Validation
{
    use ValidatesAttributes;

    private $descriptor;

    public function validateBase64Image($attribute, $value, $parameters, $validator) {
        return !empty($value)
            ? $validator->validateFile($attribute, $this->convertToFile($value))
            : true;
    }

    protected function convertToFile($value) {
        if(strpos($value, ';base64') !== false) {
            [, $value] = explode(';', $value);
            [, $value] = explode(',', $value);
        }

        $fileData = base64_decode($value);
        $tmpFile = tmpfile();
        $this->descriptor = $tmpFile;
        $tmpFilePath = stream_get_meta_data($tmpFile)['uri'];

        file_put_contents($tmpFilePath, $fileData);

        return new File($tmpFilePath);
    }
}