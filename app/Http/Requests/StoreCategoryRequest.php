<?php

namespace App\Http\Requests;

class StoreCategoryRequest extends BaseRequest
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
        return [
            'name'        => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string|max:1000',
            'parent_id'   => 'nullable|exists:categories,id',
            'icon'        => 'nullable|string|max:255',
            'image_file'  => 'sometimes|file|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'icon_file'   => 'sometimes|file|image|mimes:jpeg,png,jpg,gif,svg|max:1024',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'Category name is required',
            'name.unique'      => 'Category name must be unique',
            'parent_id.exists' => 'Parent category does not exist',
            'image_file.image' => 'Image must be a valid image file',
            'image_file.max'   => 'Image size cannot exceed 2MB',
            'icon_file.max'    => 'Icon file size cannot exceed 1MB',
            'icon_file.mimes'  => 'Icon file must be svg, png, or jpeg format',
        ];
    }
}
