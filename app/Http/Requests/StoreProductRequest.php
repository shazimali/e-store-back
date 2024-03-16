<?php

namespace App\Http\Requests;

use App\Http\Requests\JsonFormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends JsonFormRequest
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
            'name'     => [
                'required',
            ],
            'store_id'     => [
                'required',
            ],
            'code' => [
                'required',
                // 'unique:products,code'
                Rule::unique('products','code')->where('store_id',$this->store_id),
            ],
            'sku' => [
                'required',
                // 'unique:products,sku'
                Rule::unique('products','sku')->where('store_id',$this->store_id),
            ],
            'sale_price' => [
                'required',
            ],
            'status' => [
                'required',
            ],
        ];   
    }
}
