<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Person;
use Illuminate\Support\Facades\Validator;

class PersonController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document' => 'required|unique:persons',
            'name'     => 'required',
            'email'    => 'required|email|unique:persons',
            'cellphone' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->xml([
                'success'  => false,
                'cod_error' => 400,
                'message_error' => $validator->errors()->first()
            ], 400);
        }

        $person = Person::create($request->all());

        return response()->xml([
            'success'  => true,
            'code'    => 200,
            'message' => 'Person registered successfully'
        ], 200);
    }
}
