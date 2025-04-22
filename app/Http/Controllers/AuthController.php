<?php

namespace App\Http\Controllers;

use App\Http\Requests\CoachRegisterRequest;
use App\Http\Requests\DebaterRegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UserRegisterRequest;
use App\Http\Resources\CoachResource;
use App\Http\Resources\DebaterResource;
use App\Http\Resources\JudgeResource;
use App\Http\Resources\MobileUserResource;
use App\JSONResponseTrait;
use App\Models\Coach;
use App\Models\Debater;
use App\Models\Judge;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * @OA\SecurityScheme(
 *     securityScheme="BearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your JWT token in the format: Bearer {token}"
 * )
 */

class AuthController extends Controller
{
    use JSONResponseTrait;

    public function __construct()
    {
        //$this->middleware('auth:api', ['except' => ['login','register']]);
    }

    public function register($actor)
    {
        $registerMethods = [
            'debater' => ['registerDebater', DebaterRegisterRequest::class],
            'user' => ['registerUser', UserRegisterRequest::class],
            'coach' => ['registerCoach', CoachRegisterRequest::class],
            'judge' => ['registerJudge', CoachRegisterRequest::class],
        ];

        if (!array_key_exists($actor, $registerMethods)) {
            // return $this->errorResponse('Invalid registration type', $actor, [], 404);
            return response()->json(['error' => 'Invalid registration type'], 404);
        }

        [$method, $requestClass] = $registerMethods[$actor];

        $requestInstance = app($requestClass);

        return call_user_func([$this, $method], $requestInstance);
    }

    /**
     * @OA\Post(
     *     path="/api/register/user",
     *     tags={"Authentication"},
     *     summary="Add user",
     *     description="Add new mobile user to the application",
     *     @OA\RequestBody(
     *          required=true,
     *          description="User register credentials",
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  required={"first_name", "last_name", "email", "password", "password_confirmation", "profile_picture"},
     *                   @OA\Property(property="first_name", type="string", example="Jad"),
     *                   @OA\Property(property="last_name", type="string", example="Alhalabi"),
     *                   @OA\Property(property="email", type="string", example="jad@email.com"),
     *                   @OA\Property(property="password", type="string", example="12345678"),
     *                   @OA\Property(property="password_confirmation", type="string", example="12345678"),
     *                   @OA\Property(
     *                      property="profile_picture",
     *                       type="string",
     *                      format="binary",
     *                      description="Profile picture file upload"
     *                  )
     *              )
     *         )
     *      ),
     *     @OA\Response(
     *         response=201,
     *         description="User has been created!"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credientials"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Content"
     *     )
     * )
     */


    public function registerUser(UserRegisterRequest $request)
    {
        $user = User::create([
            'first_name' => $request->get('first_name'),
            'last_name' => $request->get('last_name'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
            'profile_picture' => "test"
        ]);

        $token = Auth::guard('user')->login($user);
        return $this->successResponse("User has been created!", [
            "token" => $token
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/register/coach",
     *     tags={"Authentication"},
     *     summary="Add coach",
     *     description="Add new coach to the application",
     *     @OA\RequestBody(
     *          required=true,
     *          description="Coach register credentials",
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  required={"first_name", "last_name", "email", "password", "password_confirmation", "profile_picture"},
     *                   @OA\Property(property="first_name", type="string", example="Coach first name"),
     *                   @OA\Property(property="last_name", type="string", example="Coach last name"),
     *                   @OA\Property(property="email", type="string", example="coach@email.com"),
     *                   @OA\Property(property="password", type="string", example="12345678"),
     *                   @OA\Property(property="password_confirmation", type="string", example="12345678"),
     *                   @OA\Property(
     *                      property="profile_picture",
     *                       type="string",
     *                      format="binary",
     *                      description="Profile picture file upload"
     *                  )
     *              )
     *         )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="User has been created!"
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Invalid credientials"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Content"
     *     )
     * )
     */

    public function registerCoach(CoachRegisterRequest $request)
    {
        try {
            $user = User::create([
                'first_name' => $request->get('first_name'),
                'last_name' => $request->get('last_name'),
                'email' => $request->get('email'),
                'password' => Hash::make($request->get('password')),
                'profile_picture' => "test"
            ]);

            $coach = Coach::create([
                'user_id' => $request->get('user_id')
            ]);

            $token = Auth::guard('coach')->login($user);

            return $this->successResponse("Coach has been created!", [
                "token" => $token
            ], 201);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/register/debater",
     *     tags={"Authentication"},
     *     summary="Add debater",
     *     description="Add new  debater to the application",
     *     @OA\RequestBody(
     *          required=true,
     *          description="Debater register credentials",
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  required={"first_name", "last_name", "email", "password", "password_confirmation", "profile_picture"},
     *                   @OA\Property(property="first_name", type="string", example="Debater first name"),
     *                   @OA\Property(property="last_name", type="string", example="Debater last name"),
     *                   @OA\Property(property="email", type="string", example="debater@email.com"),
     *                   @OA\Property(property="password", type="string", example="12345678"),
     *                   @OA\Property(property="password_confirmation", type="string", example="12345678"),
     *                   @OA\Property(property="coach_id", type="integer", description="current coach id", example= 1),
     *                   @OA\Property(
     *                      property="profile_picture",
     *                       type="string",
     *                      format="binary",
     *                      description="Profile picture file upload"
     *                  )
     *              )
     *         )
     *      ),
     *     @OA\Response(
     *         response=201,
     *         description="Coach has been created!"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credientials"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Content"
     *     )
     * )
     */

    public function registerDebater(DebaterRegisterRequest $request)
    {
        try {
            $user = User::create([
                'first_name' => $request->get('first_name'),
                'last_name' => $request->get('last_name'),
                'email' => $request->get('email'),
                'password' => Hash::make($request->get('password')),
                'profile_picture' => "test"
            ]);

            $debater = Debater::create([
                'user_id' => $user->id,
                'coach_id' => $request->get('coach_id'),
            ]);

            $token = Auth::guard('debater')->login($user);

            return $this->successResponse("Debater has been created!", [
                "token" => $token
            ], 201);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/register/judge",
     *     tags={"Authentication"},
     *     summary="Add judge",
     *     description="Add new judge to the application",
     *     @OA\RequestBody(
     *          required=true,
     *          description="Judge register credentials",
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  required={"first_name", "last_name", "email", "password", "password_confirmation", "profile_picture"},
     *                   @OA\Property(property="first_name", type="string", example="Judge first name"),
     *                   @OA\Property(property="last_name", type="string", example="Judge last name"),
     *                   @OA\Property(property="email", type="string", example="judge@email.com"),
     *                   @OA\Property(property="password", type="string", example="12345678"),
     *                   @OA\Property(property="password_confirmation", type="string", example="12345678"),
     *                   @OA\Property(
     *                      property="profile_picture",
     *                       type="string",
     *                      format="binary",
     *                      description="Profile picture file upload"
     *                  )
     *              )
     *         )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Judge has been created!"
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Invalid credientials"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Content"
     *     )
     * )
     */

    public function registerJudge(CoachRegisterRequest $request)
    {
        try {
            $user = User::create([
                'first_name' => $request->get('first_name'),
                'last_name' => $request->get('last_name'),
                'email' => $request->get('email'),
                'password' => Hash::make($request->get('password')),
                'profile_picture' => "test"
            ]);

            $judge = Judge::create([
                'user_id' => $user->id,
            ]);

            $token = Auth::guard('judge')->login($user);

            return $this->successResponse("Judge has been created!", [
                "token" => $token
            ], 201);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Authenticate user",
     *     tags={"Authentication"},
     * @OA\RequestBody(
     *         required=true,
     *         description="Login credentials",
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", example="jad@email.com"),
     *             @OA\Property(property="password", type="string", example="12345678")
     *         )
     * ),
     * @OA\Response(response="200", description="Login successful"),
     * @OA\Response(response="401", description="Invalid credentials")
     * )
     */

    public function login(LoginRequest $request)
    {
        try {
            $email = $request->get('email');
            $password = $request->get('password');

            if (!Auth::guard('user')->attempt(['email' => $email, 'password' => $password])) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $user = Auth::guard('user')->user();

            [$actor, $actorResource] = $this->getAuthenticatedActor($user->id);
            if (!$actorResource) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $token = Auth::guard($actor)->login($user);

            return $this->successResponse("LoggedIn successfully !", [
                "token" => $token,
                "guard" => $actor,
            ]);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/profile",
     *     summary="Get authenticated user profile",
     *     description="Returns profile details based on the token (User, Coach, Debater, Judge)",
     *     tags={"Authentication"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     schema="User",
     *                     required={"id", "first_name", "last_name", "email"},
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="first_name", type="string", example="Jad"),
     *                     @OA\Property(property="last_name", type="string", example="Alhalabi"),
     *                     @OA\Property(property="email", type="string", example="jadalhalabi88@gmail.com"),
     *                     @OA\Property(property="profile_picture", type="string", example="random link")
     *                 ),
     *                 @OA\Schema(
     *                     schema="Coach",
     *                     required={"id", "first_name", "last_name", "email", "team"},
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="first_name", type="string", example="Coach Name"),
     *                     @OA\Property(property="last_name", type="string", example="Last Name"),
     *                     @OA\Property(property="email", type="string", example="coach@email.com"),
     *                     @OA\Property(
     *                         property="team",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="first_name", type="string", example="Debater first name"),
     *                             @OA\Property(property="last_name", type="string", example="Debater last name"),
     *                             @OA\Property(property="email", type="string", example="debater@email.com"),
     *                             @OA\Property(property="profile_picture", type="string", example="random link"),
     *                         )
     *                     )
     *                 ),
     *                 @OA\Schema(
     *                     schema="Debater",
     *                     required={"id", "first_name", "last_name", "email", "coach_name"},
     *                     @OA\Property(property="id", type="integer", example=20),
     *                     @OA\Property(property="first_name", type="string", example="Debater Name"),
     *                     @OA\Property(property="last_name", type="string", example="Last Name"),
     *                     @OA\Property(property="email", type="string", example="debater@email.com"),
     *                     @OA\Property(property="profile_picture", type="string", example="random link"),
     *                     @OA\Property(property="coach_name", type="string", example="Coach first name + Coach last name"),
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing token"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     )
     * )
     */


    public function profile()
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['error' => 'User not found'], 404);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Invalid token'], 400);
        }

        [$actor, $actorResource] = $this->getAuthenticatedActor($user->id);

        return $this->successResponse("Here's your $actor profile", [
            $actor => $actorResource
        ]);
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return $this->successResponse("Successfully logged out", '');
    }

    private function getAuthenticatedActor($userId)
    {
        $actor = Coach::where('user_id', $userId)->first()
            ?? Judge::where('user_id', $userId)->first()
            ?? Debater::where('user_id', $userId)->first()
            ?? User::find($userId);

        $guard = match (get_class($actor)) {
            Coach::class => 'coach',
            Judge::class => 'judge',
            Debater::class => 'debater',
            default => 'user',
        };

        $resourceClass = match ($guard) {
            'coach' => CoachResource::class,
            'judge' => JudgeResource::class,
            'debater' => DebaterResource::class,
            default => MobileUserResource::class,
        };

        return [$guard, new $resourceClass($actor)];
    }
}
