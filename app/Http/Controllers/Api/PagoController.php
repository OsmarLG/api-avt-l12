<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Venta\StorePagoRequest;
use App\Http\Resources\Api\PagoResource;
use App\Models\Pago;
use App\Services\Api\PagoService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PagoController extends Controller
{
    public function __construct(
        private readonly PagoService $service
    ) {}

    /**
     * Get a listing of payments.
     */
    public function index(Request $request)
    {
        $paginator = $this->service->paginate($request->all());

        return ApiResponse::ok([
            'items' => PagoResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_pago_id' => $paginator->last_pago_id,
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'links' => $paginator->linkCollection(),
            ],
        ]);
    }

    /**
     * Register a new payment.
     *
     * Stores a payment and allocates it to one or more installments (letras).
     */
    public function store(StorePagoRequest $request)
    {
        $pago = $this->service->create($request->validated(), $request->user()->id);

        return ApiResponse::ok(
            new PagoResource($pago),
            'Pago registrado correctamente',
            Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified payment.
     */
    public function show(Pago $pago)
    {
        return ApiResponse::ok(new PagoResource($this->service->find($pago)));
    }

    /**
     * Cancel a payment.
     *
     * Marks a payment as cancelled and reverts the status of affected installments.
     */
    public function cancel(Request $request, Pago $pago)
    {
        $request->validate(['comentario_cancelacion' => 'required|string']);

        $pago = $this->service->cancel($pago, $request->comentario_cancelacion, $request->user()->id);

        return ApiResponse::ok(
            new PagoResource($pago),
            'Pago cancelado correctamente'
        );
    }

    /**
     * Filter payments without pagination.
     */
    public function pagosFilterWithoutPagination(Request $request)
    {
        $pagos = $this->service->filterForPagosDuenos($request->all());

        return ApiResponse::ok([
            'items' => PagoResource::collection($pagos),
            'total' => count($pagos),
        ]);
    }
}
