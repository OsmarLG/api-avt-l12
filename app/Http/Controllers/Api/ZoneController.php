<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreZoneRequest;
use App\Http\Requests\UpdateZoneRequest;
use App\Models\Zone;
use App\Services\Api\ZoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    public function __construct(protected ZoneService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->paginate($request->all()));
    }

    public function store(StoreZoneRequest $request): JsonResponse
    {
        $zone = $this->service->create($request->validated());
        return response()->json($zone, 201);
    }

    public function show(Zone $zone): JsonResponse
    {
        return response()->json($zone);
    }

    public function update(UpdateZoneRequest $request, Zone $zone): JsonResponse
    {
        $zone = $this->service->update($zone, $request->validated());
        return response()->json($zone);
    }

    public function destroy(Zone $zone): JsonResponse
    {
        $this->service->delete($zone);
        return response()->json(null, 204);
    }

    public function select(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);
        $search = $request->input('search');

        return response()->json($this->service->selectList($search, $limit));
    }
}
