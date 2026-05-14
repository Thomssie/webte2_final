<?php

namespace App\Http\Controllers;

use App\Services\OpenApiTranslationService;
use Dedoc\Scramble\Generator;
use Dedoc\Scramble\Scramble;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpenApiController extends Controller
{
    // Vytvori JSON odpoved so specifikaciou, ktoru Scramble vygeneruje z Laravel API rout.
    // Pouziva sa ako zdroj pre Swagger UI na stranke /api-docs.
    public function __invoke(
        Request $request,
        Generator $generator,
        OpenApiTranslationService $translator
    ): JsonResponse
    {
        $language = $request->query('lang') === 'en' ? 'en' : 'sk';

        return response()->json(
            $translator->translate($this->specification($generator), $language)
        );
    }

    // Vrati aktualnu OpenAPI specifikaciu ako pole bez rucne udrziavaneho zoznamu endpointov.
    // Pouziva ju JSON endpoint aj dynamicky PDF export API dokumentacie.
    public function specification(Generator $generator): array
    {
        return $generator(Scramble::getGeneratorConfig(Scramble::DEFAULT_API));
    }
}
