<?php

namespace App\Http\Controllers;

use Dedoc\Scramble\Generator;
use Dedoc\Scramble\Scramble;
use Illuminate\Http\JsonResponse;

class OpenApiController extends Controller
{
    // Vytvori JSON odpoved so specifikaciou, ktoru Scramble vygeneruje z Laravel API rout.
    // Pouziva sa ako zdroj pre Swagger UI na stranke /api-docs.
    public function __invoke(Generator $generator): JsonResponse
    {
        return response()->json($this->specification($generator));
    }

    // Vrati aktualnu OpenAPI specifikaciu ako pole bez rucne udrziavaneho zoznamu endpointov.
    // Pouziva ju JSON endpoint aj dynamicky PDF export API dokumentacie.
    public function specification(Generator $generator): array
    {
        return $generator(Scramble::getGeneratorConfig(Scramble::DEFAULT_API));
    }
}
