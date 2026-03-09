<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Venta\UpdateLetraRequest;
use App\Http\Resources\Api\LetraResource;
use App\Models\Letra;
use App\Services\Api\LetraService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class LetraController extends Controller
{
    public function __construct(
        private readonly LetraService $service
    ) {}

    /**
     * Get a listing of installments.
     */
    public function index(Request $request)
    {
        $paginator = $this->service->paginate($request->all());

        return ApiResponse::ok([
            'items' => LetraResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'links' => $paginator->linkCollection(),
            ],
        ]);
    }

    /**
     * Display the specified installment.
     */
    public function show(Letra $letra)
    {
        return ApiResponse::ok(new LetraResource($this->service->find($letra)));
    }

    /**
     * Update an installment.
     *
     * Allows changing basic data like description, amount, or due date.
     */
    public function update(UpdateLetraRequest $request, Letra $letra)
    {
        $letra = $this->service->update($letra, $request->validated());

        return ApiResponse::ok(
            new LetraResource($letra),
            'Letra actualizada correctamente'
        );
    }
}
