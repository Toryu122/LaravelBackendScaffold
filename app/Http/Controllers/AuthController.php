<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Common\Helper;
use App\Common\Constant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Create User
     * @param Request $request
     * @return Response
     */
    public function createUser(Request $request): Response
    {
        $tableNames = config('permission.table_names');
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'email' => 'required|email|unique:' . User::TABLE_NAME . ',email',
                    'password' => 'required'
                ]
            );

            if ($validateUser->fails()) {
                return Helper::getResponse(null, $validateUser->errors(), 401);
            }

            $user = User::create([
                'name' => $request['name'],
                'email' => $request['email'],
                'password' => Hash::make($request['password'])
            ]);

            $newUserId = DB::table(User::TABLE_NAME)->where('email', '=', $request['email'])->get('id');
            $guestRoleId = DB::table(Role::retrieveTableName())->where('name', '=', 'guest')->get('id');

            DB::table($tableNames['model_has_roles'])
                ->insert([
                    'role_id' => $guestRoleId[0]->id,
                    'model_type' => User::class,
                    'model_id' => $newUserId[0]->id
                ]);

            return Helper::getResponse([
                'access_token' => $this->getAccessToken($user, 'guest'),
                'refresh_token' => $this->getRefreshToken($user, 'guest')
            ]);
        } catch (\Throwable $th) {
            return Helper::getResponse(null, $th->getMessage());
        }
    }

    /**
     * Login The User
     * @param Request $request
     * @return Response
     */
    public function loginUser(Request $request): Response
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'email' => 'required|email',
                    'password' => 'required'
                ]
            );

            if ($validateUser->fails()) {
                return Helper::getResponse(null, $validateUser->errors(), 401);
            }

            if (!Auth::attempt($request->only(['email', 'password']))) {
                return Helper::getResponse(null, 'Credential not correct', 403);
            }

            $user = User::where('email', $request['email'])->first();
            $user->tokens()->delete(); // Delete all previous tokens, maybe this should be 'delete expired tokens only'?

            return Helper::getResponse([
                'access_token' => $this->getAccessToken($user, 'guest'),
                'refresh_token' => $this->getRefreshToken($user, 'guest'),
                'user' => $user
            ]);
        } catch (\Throwable $th) {
            return Helper::getResponse(null, $th->getMessage());
        }
    }

    /**
     * Refresh Access Token
     * @param Request $request
     * @return Response
     */
    public function refreshToken(Request $request): Response
    {
        try {
            /** @var User $user */
            $user = $request->user();

            if (!$user->tokenCan('refresh')) {
                return Helper::getResponse(null, 'Invalid token type. Please use your refresh token.', 403);
            }

            // Recover the role from the database via Spatie instead.
            $userRole = $user->getRoleNames()->first();
            $role = isset(User::ROLES[$userRole]) ? $userRole : 'guest';

            // Revoke all old tokens (access + refresh) before issuing new ones
            $user->tokens()->delete();

            return Helper::getResponse([
                'access_token'  => $this->getAccessToken($user, $role),
                'refresh_token' => $this->getRefreshToken($user, $role),
            ]);
        } catch (\Throwable $th) {
            return Helper::getResponse(null, $th->getMessage());
        }
    }

    /**
     * @param User $user
     * @param string $role
     * @return mixed|string
     */
    private function getAccessToken(User $user, string $role)
    {
        return explode(
            '|',
            $user->createAuthToken(
                Constant::ACCESS_TOKEN_NAME,
                Carbon::now()->addMinutes(Constant::ACCESS_TOKEN_EXPIRES_IN), // This will set the token expiration time (in minutes) in expired_at column
                User::ROLES[$role]
            )->plainTextToken
        )[1];
    }
    /**
     * @param User $user
     * @param string $role
     * @return mixed|string
     */
    private function getRefreshToken(User $user, string $role)
    {
        return explode(
            '|',
            $user->createRefreshToken(
                Constant::REFRESH_TOKEN_NAME,
                Carbon::now()->addDays(Constant::REFRESH_TOKEN_EXPIRES_IN), // Refresh token valid for 30 days
                User::ROLES[$role]
            )->plainTextToken
        )[1];
    }
}
