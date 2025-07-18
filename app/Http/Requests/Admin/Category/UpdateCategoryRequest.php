<?php

namespace App\Http\Requests\Admin\Category;

use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    use ApiResponse;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $categoryId = $this->route('id');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($categoryId)->whereNull('deleted_at'),
            ],
            'description' => 'sometimes|nullable|string|max:1000',
            'slug'        => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($categoryId)->whereNull('deleted_at'),
            ],
            'parent_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($categoryId) {
                    if ($value) {
                        if ($value == $categoryId) {
                            $fail('A category cannot be its own parent.');
                        }

                        $parent = Category::find($value);
                        if ($parent) {
                            if ($parent->level >= 2) {
                                $fail('Categories can only be nested up to 3 levels deep.');
                            }
                        }

                        $category = Category::find($categoryId);
                        if ($category && $category->wouldCreateCircularReference($parent)) {
                            $fail('This would create a circular reference in the category hierarchy.');
                        }
                    }
                },
            ],
            'status'                      => 'sometimes|string|in:active,inactive,pending',
            'icon'                        => 'sometimes|nullable|string|max:255',
            'seo_metadata'                => 'sometimes|nullable|array',
            'seo_metadata.title'          => 'nullable|string|max:255',
            'seo_metadata.description'    => 'nullable|string|max:1000',
            'seo_metadata.keywords'       => 'nullable|string|max:500',
            'seo_metadata.og_title'       => 'nullable|string|max:255',
            'seo_metadata.og_description' => 'nullable|string|max:500',
            'seo_metadata.og_image'       => 'nullable|string|max:500',
            'image_file'                  => 'sometimes|file|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'icon_file'                   => 'sometimes|file|image|mimes:jpeg,png,jpg,gif,svg|max:1024',
            'remove_image'                => 'sometimes|boolean',
            'remove_icon'                 => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required'                      => 'Category name is required.',
            'name.string'                        => 'Category name must be a string.',
            'name.max'                           => 'Category name cannot exceed 255 characters.',
            'name.unique'                        => 'A category with this name already exists.',
            'description.string'                 => 'Description must be a string.',
            'description.max'                    => 'Description cannot exceed 1000 characters.',
            'slug.string'                        => 'Slug must be a string.',
            'slug.max'                           => 'Slug cannot exceed 255 characters.',
            'slug.unique'                        => 'A category with this slug already exists.',
            'parent_id.integer'                  => 'Parent ID must be an integer.',
            'parent_id.exists'                   => 'Selected parent category does not exist.',
            'status.in'                          => 'Status must be one of: active, inactive, pending.',
            'icon.string'                        => 'Icon must be a string.',
            'icon.max'                           => 'Icon cannot exceed 255 characters.',
            'seo_metadata.array'                 => 'SEO metadata must be an array.',
            'seo_metadata.title.string'          => 'SEO title must be a string.',
            'seo_metadata.title.max'             => 'SEO title cannot exceed 255 characters.',
            'seo_metadata.description.string'    => 'SEO description must be a string.',
            'seo_metadata.description.max'       => 'SEO description cannot exceed 1000 characters.',
            'seo_metadata.keywords.string'       => 'SEO keywords must be a string.',
            'seo_metadata.keywords.max'          => 'SEO keywords cannot exceed 500 characters.',
            'seo_metadata.og_title.string'       => 'Open Graph title must be a string.',
            'seo_metadata.og_title.max'          => 'Open Graph title cannot exceed 255 characters.',
            'seo_metadata.og_description.string' => 'Open Graph description must be a string.',
            'seo_metadata.og_description.max'    => 'Open Graph description cannot exceed 500 characters.',
            'seo_metadata.og_image.string'       => 'Open Graph image must be a string.',
            'seo_metadata.og_image.max'          => 'Open Graph image cannot exceed 500 characters.',
            'image_file.file'                    => 'Image must be a file.',
            'image_file.image'                   => 'Image must be a valid image file.',
            'image_file.mimes'                   => 'Image must be a file of type: jpeg, png, jpg, gif, webp.',
            'image_file.max'                     => 'Image file size cannot exceed 2MB.',
            'icon_file.file'                     => 'Icon must be a file.',
            'icon_file.image'                    => 'Icon must be a valid image file.',
            'icon_file.mimes'                    => 'Icon must be a file of type: jpeg, png, jpg, gif, svg.',
            'icon_file.max'                      => 'Icon file size cannot exceed 1MB.',
            'remove_image.boolean'               => 'Remove image must be true or false.',
            'remove_icon.boolean'                => 'Remove icon must be true or false.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name'                        => 'category name',
            'description'                 => 'description',
            'slug'                        => 'slug',
            'parent_id'                   => 'parent category',
            'status'                      => 'status',
            'icon'                        => 'icon',
            'seo_metadata.title'          => 'SEO title',
            'seo_metadata.description'    => 'SEO description',
            'seo_metadata.keywords'       => 'SEO keywords',
            'seo_metadata.og_title'       => 'Open Graph title',
            'seo_metadata.og_description' => 'Open Graph description',
            'seo_metadata.og_image'       => 'Open Graph image',
            'image_file'                  => 'image',
            'icon_file'                   => 'icon file',
            'remove_image'                => 'remove image',
            'remove_icon'                 => 'remove icon',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('parent_id') && empty($this->parent_id)) {
            $this->merge(['parent_id' => null]);
        }

        if ($this->has('remove_image')) {
            $this->merge([
                'remove_image' => filter_var($this->remove_image, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }

        if ($this->has('remove_icon')) {
            $this->merge([
                'remove_icon' => filter_var($this->remove_icon, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }

    /**
     * Get the validated data from the request.
     */
    public function validated($key = null, $default = null)
    {
        return parent::validated($key, $default);
    }

    /**
     * Handle a failed validation attempt.
     *
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->apiResponseErrors(
                'Validation failed',
                $validator->errors()->toArray(),
                422
            )
        );
    }
}
