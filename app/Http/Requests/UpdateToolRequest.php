<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateToolRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('update', $this->route('tool'));
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:http',
            'params' => 'required|array',
            'parameters_schema' => 'required|array',
            'is_active' => 'boolean',
        ];
    }

    protected function prepareForValidation()
    {
        // Parse JSON strings to arrays for processing
        if ($this->has('params') && is_string($this->params)) {
            $this->merge([
                'params' => json_decode($this->params, true) ?? [],
            ]);
        }

        if ($this->has('parameters_schema') && is_string($this->parameters_schema)) {
            $this->merge([
                'parameters_schema' => json_decode($this->parameters_schema, true) ?? [],
            ]);
        }
    }

    public function messages()
    {
        return [
            'type.in' => 'The tool only support http only for now.',
        ];
    }
}
