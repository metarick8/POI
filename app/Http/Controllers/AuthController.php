<?php

    namespace App\Http\Controllers;

    use App\Http\Requests\CoachRegisterRequest;
    use App\Http\Requests\CoachRequest;
    use App\Http\Requests\LoginRequest;
    use App\Http\Requests\UserRegisterRequest;
    use App\Models\Coach;
    use App\Models\Debater;
    use App\Models\Judge;
    use App\Models\User;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Hash;
    use Tymon\JWTAuth\Facades\JWTAuth;
    use Tymon\JWTAuth\Exceptions\JWTException;

    /**
     * @OA\Get(
     *     path="/api/register",
     *     tags={"Users"},
     *     summary="Register users",
     *     description="Add a new user to the application",
     *     @OA\Response(
     *         response=200,
     *         description="successful operation"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credientials"
     *     )
     * )
     */


    class AuthController extends Controller
    {
        public function __construct()
        {
            //$this->middleware('auth:api', ['except' => ['login','register']]);
        }

        // public function register($actor)
        // {
        //     $registerMethods = [
        //         'user' => ['registerUser', app(UserRegisterRequest::class)],
        //         'coach' => ['registerCoach', app(CoachRegisterRequest::class)],
        //         // 'debater' => ['registerDebater', ],
        //         // 'judge' => ['registerJudge', ],
        //         // 'admin' => ['registerAdmin', ],
        //     ];

        //     if (!array_key_exists($actor, $registerMethods)) {
        //         return response()->json(['error' => 'Invalid registration type'], 404);
        //     }

        //     return $this->{$registerMethods[$actor][0]}($registerMethods[$actor][1]);
        // }
        public function register($actor)
        {
            $registerMethods = [
                'user' => ['registerUser', UserRegisterRequest::class],
                'coach' => ['registerCoach', CoachRegisterRequest::class],
            ];

            if (!array_key_exists($actor, $registerMethods)) {
                return response()->json(['error' => 'Invalid registration type'], 404);
            }

            [$method, $requestClass] = $registerMethods[$actor];

            $requestInstance = app($requestClass);

            return call_user_func([$this, $method], $requestInstance);
        }

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
            //$token = JWTAuth::fromUser($user);
            return response()->json(compact('user', 'token'), 201);
        }

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

                $token = Auth::guard('coach')->login($coach);

                return response()->json(compact('coach', 'token'), 201);
            } catch (JWTException $e) {
                return response()->json(['error' => 'Could not create token'], 500);
            }
        }
        public function registerDebater(UserRegisterRequest $request)
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
                    'user_id' => $request->get('user_id'),
                    'coach_id' => $request->get('coach_id'),
                ]);

                $token = Auth::guard('debater')->login($user);

                return response()->json(compact('user', 'token'), 201);
            } catch (JWTException $e) {
                return response()->json(['error' => 'Could not create token'], 500);
            }
        }

        public function login(LoginRequest $request)
        {
            try {
                $email = $request->get('email');
                $password = $request->get('password');

                // First, authenticate as User
                if (!Auth::guard('user')->attempt(['email' => $email, 'password' => $password])) {
                    return response()->json(['error' => 'Unauthorized'], 401);
                }

                // Retrieve the authenticated User instance
                $user = Auth::guard('user')->user();

                // Get the correct guard & actor
                [$guard, $actor] = $this->getAuthenticatedActor($user->id);

                if (!$actor) {
                    return response()->json(['error' => 'Unauthorized'], 401);
                }

                // Generate token for the correct actor
                $token = Auth::guard($guard)->login($actor);

                return response()->json([
                    'token' => $token,
                    'actor' => $actor,
                    'guard' => $guard
                ]);
            } catch (JWTException $e) {
                return response()->json(['error' => 'Could not create token'], 500);
            }
        }


        // public function getUser()
        // {
        //     try {
        //         if (! $user = JWTAuth::parseToken()->authenticate())
        //             return response()->json(['error' => 'User not found'], 404);
        //     } catch (JWTException $e) {
        //         return response()->json(['error' => 'Invalid token'], 400);
        //     }
        //     return response()->json([
        //         "user" => $user,
        //     ]);
        // }

        public function logout()
        {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json(['message' => 'Successfully logged out']);
        }


        // private function detectGuard(int $userId)
        // {
        //     if (Coach::where('user_id', $userId)->exists()) {
        //         return 'coach';
        //     } elseif (Judge::where('user_id', $userId)->exists()) {
        //         return 'judge';
        //     } elseif (Debater::where('user_id', $userId)->exists()) {
        //         return 'debater';
        //     }
        //     return 'user';
        // }

        // private function getActorModel($guard, $userId)
        // {
        //     return match ($guard) {
        //         'coach' => Coach::where('user_id', $userId)->first(),
        //         'judge' => Judge::where('user_id', $userId)->first(),
        //         'debater' => Debater::where('user_id', $userId)->first(),
        //         default => User::find($userId),
        //     };
        // }

        private function getAuthenticatedActor($userId)
        {
            // Determine actor type (Coach, Judge, Debater)
            $actor = Coach::where('user_id', $userId)->with('user')->first()
                ?? Judge::where('user_id', $userId)->first()
                ?? Debater::where('user_id', $userId)->first()
                ?? User::find($userId); // Default to User if no role is found

            // Determine the appropriate guard
            $guard = match (get_class($actor)) {
                Coach::class => 'coach',
                Judge::class => 'judge',
                Debater::class => 'debater',
                default => 'user',
            };

            return [$guard, $actor]; // Return guard + authenticated model
        }
    }
