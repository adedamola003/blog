<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\BaseController;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\PasswordReset;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class NewPasswordController extends BaseController
{
    private PasswordReset $passwordReset;

    public function __construct()
    {
        $this->passwordReset = new PasswordReset();
    }
    /**
     * Handle an incoming new password request.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $messages = [
            'password.regex' => 'Password must be more than 8 characters long, should contain at least 1 Uppercase, 1 Lowercase and  1 number',
        ];
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'numeric', 'digits:6'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9]).{8,}$/'],
        ], $messages);

        if ($validator->fails()) return $this->sendError('Validation Error.', $validator->messages());
        $validated = $validator->validated();

        $passwordReset = $this->passwordReset::with('user')->where(['email' => $validated['email']])->orderByDesc('created_at')->first();

        if(!$passwordReset || !$passwordReset->user){
            return $this->sendError('Invalid Token or Email', ['error' => ['Invalid Token or Email']], 422);
        }

        if (!Hash::check($validated['token'], $passwordReset->token)) {
            return $this->sendError('Invalid Token', ['error' => ['Invalid Token']], 422);
        }

        //check if token has been used
        if($passwordReset->used == 'yes'){
            return $this->sendError('Token has been used', ['error' => ['Token has been used']], 422);
        }

        //check if token is expired
        if(Carbon::now()->diffInMinutes(Carbon::parse($passwordReset->created_at)) > config('settings.passwordResetTokenExpiry')){
            return $this->sendError('Token Expired', ['error' => ['Token Expired']], 422);
        }
        //update new password
        $passwordReset->user->forceFill(['password' => Hash::make($validated['password'])])->save();

        //mark token as used
        $passwordReset->update(['used' => 'yes']);

        $message = 'Password reset successful';
        $data['email'] = $passwordReset->user->email;

        return $this->sendResponse($data, $message, ResponseAlias::HTTP_CREATED);
    }
}
