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
                'status'  => 'failure',
                'code'    => '400',
                'message' => $validator->errors()->first()
            ], 400);
        }

        $person = Person::create($request->all());

        return response()->xml([
            'status'  => 'success',
            'code'    => '200',
            'message' => 'Person registered successfully'
        ], 200);
    }
}
