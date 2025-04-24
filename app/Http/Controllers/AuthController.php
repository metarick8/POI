<?php

namespace App\Http\Controllers;

use App\Http\Requests\CoachRegisterRequest;
use App\Http\Requests\DebaterRegisterRequest;
use App\Http\Requests\FileUploadRequest;
use App\Http\Requests\JudgeRegisterRequest;
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
use App\Services\AuthService;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Cloudinary\Api\Upload\UploadApi;

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

    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        //$this->middleware('auth:api', ['except' => ['login','register']]);
    }

    public function register($actor)
    {
        $registerMethods = [
            'debater' => ['registerDebater', DebaterRegisterRequest::class],
            'user' => ['registerUser', UserRegisterRequest::class],
            'coach' => ['registerCoach', CoachRegisterRequest::class],
            'judge' => ['registerJudge', JudgeRegisterRequest::class],
        ];

        if (!array_key_exists($actor, $registerMethods)) {
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
     *         required=true,
     *         description="User registration credentials",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"first_name", "last_name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="first_name", type="string", example="Jad"),
     *             @OA\Property(property="last_name", type="string", example="Alhalabi"),
     *             @OA\Property(property="email", type="string", format="email", example="jadalhalabi88@gmail.com"),
     *             @OA\Property(property="password", type="string", example="12345678"),
     *             @OA\Property(property="password_confirmation", type="string", example="12345678"),
     *             @OA\Property(property="profile_picture_url",
     *                 type="string", format="url",
     *                 example="https://res.cloudinary.com/dts4tnvo4/image/upload/v1745503749/Profile%20picture/user/example.jpg",
     *                 description="Obtain this URL from `/api/upload/image`."
     *             ),
     *             @OA\Property(
     *                 property="public_id",
     *                 type="string",
     *                 example="Profile picture/user/example",
     *                 description="Required only if profile_picture_url is provided."
     *             )
     *         )
     *     ),
     *     *     @OA\Response(
     *         response=201,
     *         description="User added to database",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User has been created!"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="token",
     *                     type="string",
     *                     example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
     *                 )
     *             ),
     *             @OA\Property(property="errors", type="string", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=422, description="Unprocessable Content")
     * )
     */

    public function registerUser(UserRegisterRequest $request)
    {
        if ($this->authService->createUser($request) != null)
            return $this->successResponse("User has been created!", '', 201);
        return $this->errorResponse("Something went wrong!", '');
    }

    /**
     * @OA\Post(
     *     path="/api/register/coach",
     *     tags={"Authentication"},
     *     summary="Add coach",
     *     description="Add new coach to the application",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Coach registration credentials",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"first_name", "last_name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="first_name", type="string", example="Coach"),
     *             @OA\Property(property="last_name", type="string", example="cc"),
     *             @OA\Property(property="email", type="string", format="email", example="coach@email.com"),
     *             @OA\Property(property="password", type="string", example="12345678"),
     *             @OA\Property(property="password_confirmation", type="string", example="12345678"),
     *             @OA\Property(property="profile_picture_url",
     *                 type="string", format="url",
     *                 example="https://res.cloudinary.com/dts4tnvo4/image/upload/v1745503749/Profile%20picture/coach/example.jpg",
     *                 description="Obtain this URL from `/api/upload/image`."
     *             ),
     *             @OA\Property(
     *                 property="public_id",
     *                 type="string",
     *                 example="Profile picture/coach/example",
     *                 description="Required only if profile_picture_url is provided."
     *             )
     *         )
     *     ),
     *     *     @OA\Response(
     *         response=201,
     *         description="Coach added to database",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Coach has been created!"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="token",
     *                     type="string",
     *                     example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
     *                 )
     *             ),
     *             @OA\Property(property="errors", type="string", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=422, description="Unprocessable Content")
     * )
     */

    public function registerCoach(CoachRegisterRequest $request)
    {
        if ($this->authService->createCoach($request) != null)
            return $this->successResponse("Coach has been created!", '', 201);
        return $this->errorResponse("Something went wrong!", '');
    }

    /**
     * @OA\Post(
     *     path="/api/register/debater",
     *     tags={"Authentication"},
     *     summary="Add debater",
     *     description="Add new  debater to the application",
     *     *     @OA\RequestBody(
     *         required=true,
     *         description="Debater registration credentials",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"first_name", "last_name", "email", "password", "password_confirmation", "coach_id"},
     *             @OA\Property(property="first_name", type="string", example="Debater"),
     *             @OA\Property(property="last_name", type="string", example="dd"),
     *             @OA\Property(property="email", type="string", format="email", example="debater@email.com"),
     *             @OA\Property(property="password", type="string", example="12345678"),
     *             @OA\Property(property="coach_id", type="integer", description="current coach id", example= 1),
     *             @OA\Property(property="password_confirmation", type="string", example="12345678"),
     *             @OA\Property(property="profile_picture_url",
     *                 type="string", format="url",
     *                 example="https://res.cloudinary.com/dts4tnvo4/image/upload/v1745503749/Profile%20picture/debater/example.jpg",
     *                 description="Obtain this URL from `/api/upload/image`."
     *             ),
     *             @OA\Property(
     *                 property="public_id",
     *                 type="string",
     *                 example="Profile picture/debater/example",
     *                 description="Required only if profile_picture_url is provided."
     *             )
     *         )
     *     ),
     *     *     @OA\Response(
     *         response=201,
     *         description="Debater added to database",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Debater has been created!"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="token",
     *                     type="string",
     *                     example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
     *                 )
     *             ),
     *             @OA\Property(property="errors", type="string", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=422, description="Unprocessable Content")
     * )
     */

    public function registerDebater(DebaterRegisterRequest $request)
    {
        if ($this->authService->createDebater($request) != null)
            return $this->successResponse("Debater has been created!", '', 201);
        return $this->errorResponse("Something went wrong!", '');
    }

    /**
     * @OA\Post(
     *     path="/api/register/judge",
     *     tags={"Authentication"},
     *     summary="Add judge",
     *     description="Add new judge to the application",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Judge registration credentials",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"first_name", "last_name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="first_name", type="string", example="Judge"),
     *             @OA\Property(property="last_name", type="string", example="jj"),
     *             @OA\Property(property="email", type="string", format="email", example="judge@email.com"),
     *             @OA\Property(property="password", type="string", example="12345678"),
     *             @OA\Property(property="password_confirmation", type="string", example="12345678"),
     *             @OA\Property(property="profile_picture_url",
     *                 type="string", format="url",
     *                 example="https://res.cloudinary.com/dts4tnvo4/image/upload/v1745503749/Profile%20picture/judge/example.jpg",
     *                 description="Obtain this URL from `/api/upload/image`."
     *             ),
     *             @OA\Property(
     *                 property="public_id",
     *                 type="string",
     *                 example="Profile picture/judge/example",
     *                 description="Required only if profile_picture_url is provided."
     *             )
     *         )
     *     ),
     *     *     @OA\Response(
     *         response=201,
     *         description="Judge added to database",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Judge has been created!"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="token",
     *                     type="string",
     *                     example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
     *                 )
     *             ),
     *             @OA\Property(property="errors", type="string", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=422, description="Unprocessable Content")
     * )
     */

    public function registerJudge(JudgeRegisterRequest $request)
    {
        if ($this->authService->createJudge($request) != null)
            return $this->successResponse("Judge has been created!", '', 201);
        return $this->errorResponse("Something went wrong!", '');
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
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="LoggedIn successfully!"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="token",
     *                     type="string",
     *                     example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
     *                 ),
     *                 @OA\Property(
     *                     property="guard",
     *                     type="string",
     *                     example="user",
     *                     description="for front-end to know which widget should be directed"
     *                 )
     *             ),
     *             @OA\Property(property="errors", type="string", nullable=true, example=null)
     *         )
     *     ),
     * @OA\Response(response="401", description="Invalid credentials")
     * )
     */

    public function login(LoginRequest $request)
    {
        $email = $request->get('email');
        $password = $request->get('password');
        if (!Auth::guard('user')->attempt(['email' => $email, 'password' => $password]))
            return response()->json(['error' => 'Unauthorized'], 401);
        $user = Auth::guard('user')->user();
        [$actor, $actorResource] = $this->getAuthenticatedActor($user->id);
        if (!$actorResource)
            return response()->json(['error' => 'Unauthorized'], 401);

        $token = Auth::guard($actor)->login($user);

        return $this->successResponse("LoggedIn successfully !", [
            "token" => $token,
            "guard" => $actor,
        ]);
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
     *                     @OA\Property(property="profile_picture_url", type="string", example="https://res.cloudinary.com/dts4tnvo4/image/upload/v1745503749/Profile%20picture/user/example.jpg")
     *                 ),
     *                 @OA\Schema(
     *                     schema="Coach",
     *                     required={"id", "first_name", "last_name", "email", "team"},
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="first_name", type="string", example="Coach Name"),
     *                     @OA\Property(property="last_name", type="string", example="Last Name"),
     *                     @OA\Property(property="email", type="string", example="coach@email.com"),
     *                     @OA\Property(property="profile_picture_url", type="string", example="https://res.cloudinary.com/dts4tnvo4/image/upload/v1745503749/Profile%20picture/coach/example.jpg"),
     *                     @OA\Property(
     *                         property="team",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="first_name", type="string", example="Debater first name"),
     *                             @OA\Property(property="last_name", type="string", example="Debater last name"),
     *                             @OA\Property(property="email", type="string", example="debater@email.com"),
     *                             @OA\Property(property="profile_picture_url", type="string", example="https://res.cloudinary.com/dts4tnvo4/image/upload/v1745503749/Profile%20picture/debater/example.jpg")
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
     *                     @OA\Property(property="profile_picture_url", type="string", example="https://res.cloudinary.com/dts4tnvo4/image/upload/v1745503749/Profile%20picture/debater/example.jpg"),
     *                     @OA\Property(property="coach_name", type="string", example="Coach first name + Coach last name")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Invalid token",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Invalid token")
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
        if (!$user = JWTAuth::parseToken()->authenticate())
            return response()->json(['error' => 'User not found'], 404);
        [$actor, $actorResource] = $this->getAuthenticatedActor($user->id);
        return $this->successResponse("Here's your $actor profile", [
            $actor => $actorResource
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Logout user",
     *     description="Logs out the authenticated user and invalidates the JWT token.",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token is invalid",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Successfully logged out"),
     *             @OA\Property(property="data", type="string", example=""),
     *             @OA\Property(property="errors", type="string", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized - Invalid or missing token"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */

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


    /**
     * @OA\Post(
     *     path="/api/upload/image",
     *     summary="Upload profile picture",
     *     description="Upload the image to cloud storage and get the url (with public id) from response to apply it to the registration credentials",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *          required=true,
     *          description="Upload image credentials",
     *           @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"profile_picture", "actor"},
     *                 type="object",
     *                 @OA\Property(
     *                     property="profile_picture",
     *                     type="string",
     *                     format="binary"
     *                 ),
     *                 @OA\Property(
     *                     property="actor",
     *                      description="Role type (Must be 'user', 'debater', 'coach' or 'judge')",
     *                     type="string",
     *                     enum={"user", "debater", "coach", "judge"}
     *                 )
     *             )
     *         )
     *     ),
     *    @OA\Response(
     *         response=200,
     *         description="Image upload successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Uploaded Profile picture successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="url", type="string", format="url", example="https://res.cloudinary.com/dts4tnvo4/image/upload/v1745489184/Profile%20picture/user/iiabghd42424ezj4b50s.jpg"),
     *                 @OA\Property(property="public_id", type="string", example="Profile picture/user/iiabghd42424ezj4b50s")
     *             ),
     *             @OA\Property(property="errors", type="string", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Invalid token",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Invalid token")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized access")
     * )
     */

    public function uploadImage(FileUploadRequest $request)
    {

        $uploadedimage = $request->file('profile_picture');

        $imagePath = $uploadedimage->getRealPath();
        $actor = $request->get('actor');

        $cloudinary = new UploadApi();
        $image = $cloudinary->upload($imagePath, [
            'folder' =>  "Profile picture/$actor",

        ]);

        return $this->successResponse(
            "Uploaded Profile pciture successfully",
            [
                'url' => $image['secure_url'],
                'public_id' => $image['public_id'],
            ]
        );
    }

    public function destroyImage($public_id)
    {
        $public_id = '';
        (new UploadApi())->destroy($public_id);
        return $this->successResponse("Deleted successfully!", "");
    }
}
