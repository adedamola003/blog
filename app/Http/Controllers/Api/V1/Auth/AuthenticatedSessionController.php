<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\V1\BaseController;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class AuthenticatedSessionController extends BaseController
{
    /**
     * Handle an incoming authentication request.
     *
     * @param LoginRequest $request
     * @return array|JsonResponse
     * @throws ValidationException
     */

    /**
     * @OA\Post(
     * path="/api/v1/auth/login",
     * summary="Sign in",
     * description="Login by email, password",
     * operationId="authLogin",
     * tags={"Login"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass user credentials",
     *    @OA\JsonContent(
     *       required={"email","password"},
     *       @OA\Property(property="email", type="string", format="email", example="user1@mail.com"),
     *       @OA\Property(property="password", type="string", format="password", example="PassWord12345"),
     *    ),
     * ),
     * @OA\Response(
     *    response=401,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Invalid username and/or password"),
     *       @OA\Property(property="success", type="boolean", example="false"),
     *       @OA\Property(property="data", type="object",
     *          @OA\Property(property="error", type="string", example="Invalid username and/or password"),
     *        ),
     *    )
     *  ),
     *  @OA\Response(
     *    response=200,
     *    description="Returns after a succesful login",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Login successful"),
     *       @OA\Property(property="success", type="boolean", example="true"),
     *       @OA\Property(property="data", type="object",
     *          @OA\Property(property="accessToken", type="string", example="1|vYyNOAhaS6ZjNnbJo6RnLg5pl40vEmEV97iObp3B"),
     *          @OA\Property(property="name", type="boolean", example="Adedamola"),
     *          @OA\Property(property="email", type="boolean", example="adedamola@mail.com"),
     *        ),
     *    )
     *  ),
     * )
     */
    public function store(LoginRequest $request): JsonResponse|array
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if ($validator->fails()){
            return $this->sendError('Validation Error.', $validator->messages());
        }

        $validated = $validator->validated();

        if (Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
            $user = Auth::user();
            // Revoke all tokens...
            $user->tokens()->delete();
            // Create a new token...
            $loginToken = $user->createToken('user')->plainTextToken;

            //todo: send email notification
            //Notification::route('mail', $user->email)->notify((new LoginNotification($user->user_tag, now(), $requestArr))->delay(now()->addSeconds(5)));
            $data['accessToken'] = $loginToken;
            $data['name'] = $user->name;
            $data['email'] = $user->email;
            $message = 'Login successful';

            return $this->sendResponse($data, $message, ResponseAlias::HTTP_CREATED);

        }
        //failed login request
        return $this->sendError('Your email or password is incorrect', ['error' => ['Your email or password is incorrect']]);

    }

    /**
     * Destroy an authenticated session.
     *
     * @return JsonResponse
     */
    public function destroy(): JsonResponse
    {
        $user = Auth::user();
        if($user){
            $user->tokens()->delete();
            return $this->sendResponse([],  'Logged out successfully', ResponseAlias::HTTP_CREATED);
        }
        return $this->sendError('User not found', ['error' => ['User not found']]);
    }
}
