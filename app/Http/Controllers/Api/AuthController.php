<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ChangePasswordRequest;
use App\Http\Requests\Api\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\Api\UserResource;
use App\Services\Api\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $service
    ) {
    }

    /**
     * Handle user login.
     *
     * Authenticates a user and returns an access token.
     */
    public function login(LoginRequest $request)
    {
        $data = $request->validated();
        $deviceName = $data['device_name'] ?? 'api';

        $result = $this->service->login($data['login'], $data['password'], $deviceName);

        return ApiResponse::ok([
            'token' => $result['token'],
            'user' => new UserResource($result['user']),
        ], 'Login correcto', Response::HTTP_OK);
    }

    /**
     * Get the authenticated user's information.
     *
     * Returns details about the currently authenticated user.
     */
    public function me(Request $request)
    {
        return ApiResponse::ok(new UserResource($this->service->me($request->user())), 'Usuario autenticado', Response::HTTP_OK);
    }

    /**
     * Handle user logout.
     *
     * Revokes the current access token for the authenticated user.
     */
    public function logout(Request $request)
    {
        $this->service->logout($request->user());

        return ApiResponse::ok(null, 'Sesión cerrada', Response::HTTP_NO_CONTENT);
    }

    /**
     * Handle forgot password request.
     *
     * Sends a password reset link to the user's email.
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $status = $this->service->forgotPassword($request->validated()['email']);

        return ApiResponse::ok(['status' => $status], 'Si el correo existe, se envió el enlace de recuperación.', Response::HTTP_OK);
    }

    /**
     * Handle change password request.
     *
     * Changes the authenticated user's password.
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $data = $request->validated();

        $this->service->changePassword(
            $request->user(),
            $data['current_password'],
            $data['password']
        );

        return ApiResponse::ok(null, 'Contraseña actualizada', Response::HTTP_OK);
    }
}
