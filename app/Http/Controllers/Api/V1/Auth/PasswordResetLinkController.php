<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\User;
use App\Models\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Notifications\PasswordResetMail;
use Symfony\Component\HttpFoundation\Response;

class PasswordResetLinkController extends BaseController
{
    private User $user;
    private PasswordReset $passwordReset;

    public function __construct()
    {
        $this->user = new User();
        $this->passwordReset = new PasswordReset();

    }
    /**
     * Handle an incoming password reset link request.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        //validate email
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        if ($validator->fails()) return $this->sendError('Validation Error.', $validator->messages());
        $validated = $validator->validated();

        //check if email exists in database
        $thisUser = $this->user::where('email', $validated['email'])->first();
        if (!$thisUser) return $this->sendError('Validation Error.', 'User not found');

        //generate random 6 digit code
        $code = rand(100000, 999999);
        //hash code
        $hashedCode = Hash::make($code);
        //save code in database
        $this->passwordReset::create([
            'email' => $validated['email'],
            'token' => $hashedCode,
        ]);

        //send code to email
        Notification::route('mail', $thisUser->email)->notify((new PasswordResetMail($thisUser->name, $code)));

        $message = "Password reset link sent to your email";
        return $this->sendResponse([], $message, Response::HTTP_CREATED);

    }
}
