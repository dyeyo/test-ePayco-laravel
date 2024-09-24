<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\PaymentConfirmationMail;

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

    public function generatePaymentToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document' => 'required|exists:persons,document',
            'cellphone' => 'required|exists:persons,cellphone',
            'amount' => 'required|numeric|min:1'
        ]);

        if ($validator->fails()) {
            return response()->xml([
                'status'  => 'failure',
                'code'    => '400',
                'message' => $validator->errors()->first()
            ], 400);
        }

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

        $wallet = $person->wallet;
        if ($wallet->balance < $request->amount) {
            return response()->xml([
                'status'  => 'failure',
                'code'    => '400',
                'message' => 'Insufficient balance'
            ], 400);
        }

        // Generar el token de 6 dígitos y el ID de sesión
        $token = rand(100000, 999999);
        // Generar un ID único de sesión
        $sessionId = Str::uuid(); 

        $wallet->token = $token;
        $wallet->session_id = $sessionId;
        $wallet->save();

        // Enviar el token al correo del usuario
        Mail::to($person->email)->send(new PaymentConfirmationMail($token));

        // Respuesta con éxito
        return response()->xml([
            'status'  => 'success',
            'code'    => '200',
            'message' => 'Token generated and sent to email',
            'session_id' => $sessionId
        ], 200);
    }

    public function confirmPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|exists:wallets,session_id',
            'token' => 'required|numeric|digits:6',
            'amount' => 'required|numeric|min:1'
        ]);

        if ($validator->fails()) {
            return response()->xml([
                'status'  => 'failure',
                'code'    => '400',
                'message' => $validator->errors()->first()
            ], 400);
        }

        // Buscar la billetera con el session_id y token correctos
        $wallet = Wallet::where('session_id', $request->session_id)
                        ->where('token', $request->token)
                        ->first();

        if (!$wallet) {
            return response()->xml([
                'status'  => 'failure',
                'code'    => '404',
                'message' => 'Invalid session or token'
            ], 404);
        }

        if ($wallet->balance < $request->amount) {
            return response()->xml([
                'status'  => 'failure',
                'code'    => '400',
                'message' => 'Insufficient balance'
            ], 400);
        }

        $wallet->balance -= $request->amount;
        $wallet->token = null; 
        $wallet->session_id = null;
        $wallet->save();

        return response()->xml([
            'status'  => 'success',
            'code'    => '200',
            'message' => 'Payment confirmed successfully',
            'new_balance' => $wallet->balance
        ], 200);
    }
}
