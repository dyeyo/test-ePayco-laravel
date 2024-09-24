<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    public function rechargeWallet(Request $request)
    {
        // Validar los datos de entrada
        $validator = Validator::make($request->all(), [
            'document' => 'required|exists:persons,document',
            'cellphone' => 'required|exists:persons,cellphone',
            'value' => 'required|numeric|min:1'
        ]);

        if ($validator->fails()) {
            return response()->xml([
                'status'  => 'failure',
                'code'    => '400',
                'message' => $validator->errors()->first()
            ], 400);
        }

        // Buscar la persona por documento y número de celular
        $person = Person::where('document', $request->document)
                        ->where('cellphone', $request->cellphone)
                        ->first();

        if (!$person) {
            return response()->xml([
                'status'  => 'failure',
                'code'    => '404',
                'message' => 'Person not found'
            ], 404);
        }

        // Buscar o crear la billetera asociada a la persona
        $wallet = $person->wallet()->firstOrCreate([
            'person_id' => $person->id
        ]);

        $wallet->balance += $request->value;
        $wallet->save();

        return response()->xml([
            'status'  => 'success',
            'code'    => '200',
            'message' => 'Wallet recharged successfully',
            'new_balance' => $wallet->balance
        ], 200);
    }
}
