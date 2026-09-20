<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('usuarios.gestionar');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $usuario = $this->route('usuario') ?? $this->route('user');
        $userId = $usuario instanceof User ? $usuario->id : $usuario;
        $esTesorero = $this->input('rol') === 'tesorero';

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'rol' => ['required', 'string', Rule::in(['admin', 'tesorero'])],
            'cajas' => [
                $esTesorero ? 'required' : 'nullable',
                'array',
                $esTesorero ? 'min:1' : 'nullable',
            ],
            'cajas.*' => ['integer', 'exists:cajas,id'],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['string', 'exists:permissions,name'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cajas.required' => 'Debe asignar al menos una caja cuando el rol es Tesorero.',
            'cajas.min' => 'Debe asignar al menos una caja cuando el rol es Tesorero.',
        ];
    }
}
