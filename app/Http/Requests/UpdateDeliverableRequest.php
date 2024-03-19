<?php

namespace App\Http\Requests;

use App\Http\Requests\JsonFormRequest;

class UpdateDeliverableRequest extends JsonFormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'date'     => [
                'required'
            ],
            'remarks'     => [
                'required'
            ],
            'store_id' => [
                'required'
            ],
            'branch_id' => [
                'required'
            ],
            'sr_number' => [
                'required',
                'unique:deliverables,sr_number,'.$this->id
            ],
            'products' => [
                'required',
                'array'
            ]
        ];
    }
}
