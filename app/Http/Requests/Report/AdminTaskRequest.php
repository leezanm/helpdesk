<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class AdminTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
            'tindakan' => 'required',
            'assigned_to_id' => 'bail|required_if:tindakan,1,2|exists:users,id',
            'peranan' => 'nullable|exists:roles,name',
            'kiv_start_at' => 'required_if:tindakan,3',
            'kiv_end_at' => 'required_if:tindakan,4'
        ];
    }

    public function messages()
    {
        return [
            //
            'assigned_to_id.required_if' => 'Ruangan kepada diperlukan.',
            'kiv_start_at.required_if' => 'Ruangan tarikh mula KIV diperlukan bila tindakan sama dengan KIV.',
            'kiv_end_at.required_if' => 'Ruangan tarikh tamat KIV diperlukan bila tindakan sama dengan tamat KIV.'
        ];
    }
}
