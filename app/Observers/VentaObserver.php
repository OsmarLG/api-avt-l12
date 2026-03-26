<?php

namespace App\Observers;

use App\Models\Venta;
use App\Services\Api\VentaDocumentService;
use App\Services\Api\PagareService;

class VentaObserver
{
    public $afterCommit = true;

    public function __construct(
        protected VentaDocumentService $documentService,
        protected PagareService $pagareService
    ) {
    }

    /**
     * Handle the Venta "created" event.
     */
    public function created(Venta $venta): void
    {
        $this->documentService->generateContract($venta);
        $this->documentService->generateReceipt($venta);
        $this->pagareService->generate($venta);
    }
}
