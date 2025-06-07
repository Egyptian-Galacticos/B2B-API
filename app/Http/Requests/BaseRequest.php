<?php

namespace App\Http\Requests;

use App\Traits\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseRequest extends FormRequest
{
    use ApiResponse;

    /**
     * Handle a failed validation attempt.
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();
        $formattedErrors = $this->formatValidationErrors($errors);

        throw new HttpResponseException(
            $this->apiResponseErrors(
                'Validation failed',
                $formattedErrors,
                422

            )
        );
    }

    /**
     * Format validation errors to handle nested fields properly.
     */
    protected function formatValidationErrors(array $errors): array
    {
        $formattedErrors = [];

        foreach ($errors as $key => $messages) {
            $parts = explode('.', $key);

            if (count($parts) > 1) {
                // Handle nested validation errors (e.g., items.0.name)
                $this->setNestedError($formattedErrors, $parts, $messages);
            } else {
                $formattedErrors[$key] = $messages;
            }
        }

        return $formattedErrors;
    }

    /**
     * Set nested error in the formatted errors array.
     */
    private function setNestedError(array &$formattedErrors, array $parts, array $messages): void
    {
        $current = &$formattedErrors;

        for ($i = 0; $i < count($parts) - 1; $i++) {
            $part = $parts[$i];

            if (! isset($current[$part])) {
                $current[$part] = [];
            }

            $current = &$current[$part];
        }

        $current[$parts[count($parts) - 1]] = $messages;
    }

    /**
     * Get the validated data from the request with type casting.
     *
     * @param array|null $key
     * @param mixed $default
     */
    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        // Apply any custom data transformations here if needed
        return $this->transformValidatedData($validated);
    }

    /**
     * Transform validated data if needed.
     * Override this method in child classes for custom transformations.
     */
    protected function transformValidatedData(mixed $data): mixed
    {
        return $data;
    }
}
