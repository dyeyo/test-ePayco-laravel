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

        Mail::to($person->email)->send(new PaymentConfirmationMail($token));

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

    public function checkBalance(Request $request)
    {
        // dd($request);
        $request->validate([
            'document' => 'required|string',
            'cellphone'    => 'required|string',
        ]);

        $person = Person::where('document', $request->document)
                        ->where('cellphone', $request->cellphone)
                        ->first();

        if (!$person) {
            return response()->json([
                'status'  => 'fail',
                'code'    => 404,
                'message' => 'Person not found or credentials do not match',
            ], 404);
        }

        $wallet = Wallet::where('person_id', $person->id)->first();

        if (!$wallet) {
            return response()->xml([
                'status'  => 'failure',
                'code'    => '400',
                'message' => 'Wallet not found'
            ], 400);
        }

        return response()->xml([
            'status'  => 'success',
            'code'    => '200',
            'message' => 'Balance retrieved successfully',
            'balance' => $wallet->balance,
        ], 200);
    }
}
