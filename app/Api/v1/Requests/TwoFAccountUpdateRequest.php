<?php

namespace App\Api\v1\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Validator;

class TwoFAccountUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $isAdmin = Auth::user()?->isAdministrator();

        $rules = [
            'service'  => 'present|nullable|string|regex:/^[^:]+$/i',
            'account'  => 'required|string|regex:/^[^:]+$/i',
            'icon'     => 'present|nullable|string',
            'group_id' => 'sometimes|nullable|integer|min:0',
        ];

        if ($isAdmin) {
            $rules = array_merge($rules, [
                'otp_type'  => 'required|string|in:totp,hotp,steamtotp',
                'secret'    => ['present', 'string', 'bail', new \App\Rules\IsBase32Encoded],
                'digits'    => 'present|integer|between:5,10',
                'algorithm' => 'present|string|in:sha1,sha256,sha512,md5',
                'period'    => 'nullable|integer|min:1',
                'counter'   => 'nullable|integer|min:0',
            ]);
        } else {
            $rules = array_merge($rules, [
                'otp_type'  => 'prohibited',
                'secret'    => 'prohibited',
                'digits'    => 'prohibited',
                'algorithm' => 'prohibited',
                'period'    => 'prohibited',
                'counter'   => 'prohibited',
            ]);
        }

        return $rules;
    }

    /**
     * Get the "withValidator" validation callables for the request.
     */
    public function withValidator(Validator $validator) : void
    {
        // The account may have to be assign to a specific group.
        // If so, we check if the provided group exists.
        $validator->sometimes('group_id', 'exists:groups,id', function (Fluent $input) {
            return $input['group_id'] > 0;
        });
    }

    /**
     * Prepare the data for validation.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $toMerge = [];

        if ($this->has('otp_type') && is_string($this->otp_type)) {
            $toMerge['otp_type'] = strtolower($this->otp_type);
        }

        if ($this->has('algorithm') && is_string($this->algorithm)) {
            $toMerge['algorithm'] = strtolower($this->algorithm);
        }

        if (! empty($toMerge)) {
            $this->merge($toMerge);
        }
    }
}
