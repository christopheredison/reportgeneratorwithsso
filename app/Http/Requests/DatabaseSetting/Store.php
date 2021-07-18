<?php

namespace App\Http\Requests\DatabaseSetting;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Database;

class Store extends FormRequest
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
            'name' => 'sometimes|nullable|string',
            'database_driver' => 'required|string|in:' . implode(',', Database::DATABASE_DRIVER),
            'database_name' => 'required|string',
            'database_host' => 'required|string',
            'database_port' => 'required|numeric',
            'database_username' => 'required|string',
            'database_password' => 'sometimes|nullable|string',
        ];
    }
}
