<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PersonResource;
use App\Models\Email;
use App\Models\Person;
use App\Models\Phone;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class PersonContactController extends Controller
{
    /**
     * Add a phone to a person.
     * 
     */
    public function addPhone(Request $request, Person $person)
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['celular', 'casa', 'trabajo'])],
        ]);

        $person->phones()->create($data);

        return ApiResponse::ok(new PersonResource($person->load(['phones', 'emails', 'references'])), 'Teléfono agregado', Response::HTTP_OK);
    }

    /**
     * Update a phone belonging to a person.
     */
    public function updatePhone(Request $request, Person $person, Phone $phone)
    {
        abort_unless($phone->phoneable_type === Person::class && $phone->phoneable_id === $person->id, 404);

        $data = $request->validate([
            'number' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', Rule::in(['celular', 'casa', 'trabajo'])],
        ]);

        $phone->update($data);

        return ApiResponse::ok(new PersonResource($person->load(['phones', 'emails', 'references'])), 'Teléfono actualizado', Response::HTTP_OK);
    }

    /**
     * Delete a phone belonging to a person.
     */
    public function deletePhone(Person $person, Phone $phone)
    {
        abort_unless($phone->phoneable_type === Person::class && $phone->phoneable_id === $person->id, 404);

        $phone->delete();

        return ApiResponse::ok(new PersonResource($person->load(['phones', 'emails', 'references'])), 'Teléfono eliminado', Response::HTTP_OK);
    }

    /**
     * Add an email to a person.
     */
    public function addEmail(Request $request, Person $person)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'type' => ['required', Rule::in(['personal', 'trabajo'])],
        ]);

        $person->emails()->create($data);

        return ApiResponse::ok(new PersonResource($person->load(['phones', 'emails', 'references'])), 'Correo agregado', Response::HTTP_OK);
    }

    /**
     * Update an email belonging to a person.
     */
    public function updateEmail(Request $request, Person $person, Email $email)
    {
        abort_unless($email->emailable_type === Person::class && $email->emailable_id === $person->id, 404);

        $data = $request->validate([
            'email' => ['sometimes', 'required', 'email', 'max:255'],
            'type' => ['sometimes', 'required', Rule::in(['personal', 'trabajo'])],
        ]);

        $email->update($data);

        return ApiResponse::ok(new PersonResource($person->load(['phones', 'emails', 'references'])), 'Correo actualizado', Response::HTTP_OK);
    }

    /**
     * Delete an email belonging to a person.
     */
    public function deleteEmail(Person $person, Email $email)
    {
        abort_unless($email->emailable_type === Person::class && $email->emailable_id === $person->id, 404);

        $email->delete();

        return ApiResponse::ok(new PersonResource($person->load(['phones', 'emails', 'references'])), 'Correo eliminado', Response::HTTP_OK);
    }
}
