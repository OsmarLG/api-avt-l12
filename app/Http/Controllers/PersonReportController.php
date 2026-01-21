<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Services\PersonReportService;
use App\Http\Requests\PersonReportRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
class PersonReportController extends Controller
{
    public function __construct(protected PersonReportService $reportService)
    {
    }

    /**
     * Generate Person PDF Report.
     *
     * Generates a PDF report for the specified person.
     * Can return a JSON with the URL or download the file directly.
     *
     * @param PersonReportRequest $request
     * @param Person $person
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function show(PersonReportRequest $request, Person $person)
    {
        $path = $this->reportService->generate($person);

        if ($request->boolean('download')) {
            return Storage::disk('public')->download($path);
        }

        $url = Storage::url($path);

        return response()->json([
            'url' => asset($url),
            'path' => $url
        ]);
    }
}
