<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\Attributes\FailOnUnknownFields;
use Illuminate\Foundation\Http\FormRequest;
use JsonException;

#[FailOnUnknownFields]
class NormalizeExampleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'value' => ['bail', 'required', 'string', 'max:1000'],
        ];
    }

    /**
     * Use the raw JSON payload so global trimming middleware cannot alter the
     * original value or make a too-long input appear valid.
     *
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        if (! $this->isJson()) {
            return parent::validationData();
        }

        try {
            $payload = json_decode($this->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($payload) ? $payload : [];
    }
}
