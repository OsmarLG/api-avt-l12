<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AbonoResource;
use App\Models\Abono;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AbonoController extends Controller
{
    /**
     * Get a listing of allocations (abonos).
     */
    public function index(Request $request)
    {
        $query = Abono::query();

        if ($request->has('pago_id')) {
            $query->where('pago_id', $request->pago_id);
        }

        if ($request->has('letra_id')) {
            $query->where('letra_id', $request->letra_id);
        }

        $items = $query->paginate($request->input('per_page', 15));

        return ApiResponse::ok([
            'items' => AbonoResource::collection($items),
            'meta' => [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

    /**
     * Display the specified allocation.
     */
    public function show(Abono $abono)
    {
        return ApiResponse::ok(new AbonoResource($abono));
    }
}
