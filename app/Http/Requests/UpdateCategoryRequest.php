<?php

namespace App\Http\Requests;

use App\Models\Category;

class UpdateCategoryRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $categoryId = $this->route('category');

        if ($categoryId instanceof Category) {
            $categoryId = $categoryId->id;
        }

        return [
            'name'             => 'required|string|max:255|unique:categories,name,'.$categoryId,
            'description'      => 'nullable|string|max:1000',
            'parent_id'        => 'nullable|exists:categories,id|different:id',
            'status'           => 'nullable|in:active,pending,inactive',
            'icon'             => 'nullable|string|max:255',
            'image_file'       => 'nullable|image|mimes:jpeg,png,gif,webp|max:2048',
            'icon_file'        => 'nullable|image|mimes:svg,png,jpeg|max:1024',
            'remove_image'     => 'nullable|boolean',
            'remove_icon_file' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'Category name is required',
            'name.unique'         => 'Category name must be unique',
            'parent_id.exists'    => 'Parent category does not exist',
            'parent_id.different' => 'Category cannot be its own parent',
            'status.in'           => 'Status must be active, pending, or inactive',
            'image_file.image'    => 'Image must be a valid image file',
            'image_file.max'      => 'Image size cannot exceed 2MB',
            'icon_file.max'       => 'Icon file size cannot exceed 1MB',
            'icon_file.mimes'     => 'Icon file must be svg, png, or jpeg format',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('remove_image')) {
            $this->merge([
                'remove_image' => filter_var($this->remove_image, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('remove_icon_file')) {
            $this->merge([
                'remove_icon_file' => filter_var($this->remove_icon_file, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
