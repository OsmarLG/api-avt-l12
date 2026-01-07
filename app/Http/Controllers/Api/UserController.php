<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Users\IndexUsersRequest;
use App\Http\Requests\Api\Users\StoreUserRequest;
use App\Http\Requests\Api\Users\UpdateUserRequest;
use App\Http\Resources\Api\UserResource;
use App\Http\Resources\Api\UserSelectResource;
use App\Models\User;
use App\Services\Api\UserService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $service
    ) {}

    /**
     * Get a listing of the resource.
     * 
     * Get a paginated list of users with optional filters and sorting.
     */
    public function index(IndexUsersRequest $request)
    {
        $filters = $request->validated();
        $paginator = $this->service->paginate($filters);

        return ApiResponse::ok([
            'items' => UserResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Get a select list of users.
     * 
     * Provides a list of users formatted for select inputs, with optional search and limit.
     */
    public function select(Request $request)
    {
        $search = $request->string('search')->toString();
        $limit = (int) ($request->input('limit', 20));

        $items = $this->service->selectList($search ?: null, $limit);

        return ApiResponse::ok(\App\Http\Resources\Api\UserSelectResource::collection($items));
    }

    /**
     * Display the specified resource.
     * 
     * Get detailed information about a specific user by ID.
     */
    public function show(User $user)
    {
        return ApiResponse::ok(new UserResource($this->service->find($user)));
    }

    /**
     * Store a newly created resource in storage.
     * 
     * Create a new user with the provided data.
     */
    public function store(StoreUserRequest $request)
    {
        $user = $this->service->create($request->validated());

        return ApiResponse::ok(new UserResource($user), 'Usuario creado', 201);
    }

    /**
     * Update the specified resource in storage.
     * 
     * Update an existing user's information.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user = $this->service->update($user, $request->validated());

        return ApiResponse::ok(new UserResource($user), 'Usuario actualizado');
    }

    /**
     * Remove the specified resource from storage.
     * 
     * Delete a user by ID.
     */
    public function destroy(User $user)
    {
        $this->service->delete($user);

        return ApiResponse::ok(null, 'Usuario eliminado');
    }

    /**
     * Get options for select inputs.
     * 
     * Alias for the select method to provide options for select inputs.
     */
    public function options(Request $request)
    {
        return $this->select($request);
    }
}
