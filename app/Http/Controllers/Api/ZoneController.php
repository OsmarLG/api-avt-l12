<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Zone\StoreZoneRequest;
use App\Http\Requests\Api\Zone\UpdateZoneRequest;
use App\Http\Resources\Api\ZoneResource;
use App\Models\Zone;
use App\Services\Api\ZoneService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ZoneController extends Controller
{
    public function __construct(protected ZoneService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->service->paginate($request->all());

        return ApiResponse::ok([
            'items' => ZoneResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(StoreZoneRequest $request): JsonResponse
    {
        $zone = $this->service->create($request->validated());
        return ApiResponse::ok(new ZoneResource($zone), 'Zona creada', Response::HTTP_CREATED);
    }

    public function show(Zone $zone): JsonResponse
    {
        return ApiResponse::ok(new ZoneResource($zone));
    }

    public function update(UpdateZoneRequest $request, Zone $zone): JsonResponse
    {
        $zone = $this->service->update($zone, $request->validated());
        return ApiResponse::ok(new ZoneResource($zone), 'Zona actualizada');
    }

    public function destroy(Zone $zone): JsonResponse
    {
        $this->service->delete($zone);
        return ApiResponse::ok(null, 'Zona eliminada');
    }

    public function select(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);
        $search = $request->input('search');

        return ApiResponse::ok($this->service->selectList($search, $limit));
    }
}
