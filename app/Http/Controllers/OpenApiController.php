<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpenApiController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $language = $request->query('lang') === 'en' ? 'en' : 'sk';

        // OpenAPI specifikácia je držaná na jednom mieste, aby sa dala jednoducho aktualizovať pri zmene API.
        $specification = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'TomTib Lab API',
                'version' => '1.0.0',
                'description' => 'REST API pre CAS výpočty, simulácie dynamických systémov, logy a štatistiky.',
            ],
            'servers' => [
                [
                    'url' => url('/'),
                    'description' => 'Aktuálna inštalácia aplikácie',
                ],
            ],
            'tags' => [
                ['name' => 'CAS', 'description' => 'CAS príkazy v Octave, história a logy'],
                ['name' => 'Simulations', 'description' => 'Výpočty dát pre synchronizované animácie'],
                ['name' => 'Statistics', 'description' => 'Štatistiky používania animácií'],
            ],
            'paths' => $this->paths(),
            'components' => $this->components(),
        ];

        return response()->json($this->translateSpecification($specification, $language));
    }

    private function paths(): array
    {
        return [
            '/api/cas/ping' => [
                'get' => [
                    'tags' => ['CAS'],
                    'summary' => 'Overenie dostupnosti CAS API',
                    'operationId' => 'casPing',
                    'security' => [['ApiKeyAuth' => []]],
                    'responses' => [
                        '200' => [
                            'description' => 'CAS API je dostupné',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/PingResponse'],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/InvalidApiKey'],
                    ],
                ],
            ],
            '/api/cas/execute' => [
                'post' => [
                    'tags' => ['CAS'],
                    'summary' => 'Spustenie príkazu v Octave',
                    'description' => 'Príkaz sa spustí spolu s históriou príkazov aktuálnej relácie, aby bolo možné používať pomocné premenné.',
                    'operationId' => 'casExecute',
                    'security' => [['ApiKeyAuth' => []]],
                    'parameters' => [
                        ['$ref' => '#/components/parameters/SessionToken'],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/CasExecuteRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Príkaz bol úspešne vykonaný',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/CasExecuteResponse'],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/InvalidApiKey'],
                        '422' => [
                            'description' => 'Validačná chyba alebo chyba príkazu v Octave',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/CasExecuteResponse'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/api/cas/history' => [
                'get' => [
                    'tags' => ['CAS'],
                    'summary' => 'Zoznam príkazov v histórii relácie',
                    'operationId' => 'casHistory',
                    'security' => [['ApiKeyAuth' => []]],
                    'parameters' => [
                        ['$ref' => '#/components/parameters/SessionToken'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'História príkazov',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/CasHistoryResponse'],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/InvalidApiKey'],
                    ],
                ],
            ],
            '/api/cas/history/reset' => [
                'post' => [
                    'tags' => ['CAS'],
                    'summary' => 'Vymazanie histórie aktuálnej relácie',
                    'operationId' => 'casResetHistory',
                    'security' => [['ApiKeyAuth' => []]],
                    'parameters' => [
                        ['$ref' => '#/components/parameters/SessionToken'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'História bola vymazaná',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/CasResetHistoryResponse'],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/InvalidApiKey'],
                    ],
                ],
            ],
            '/api/cas/logs' => [
                'get' => [
                    'tags' => ['CAS'],
                    'summary' => 'Zoznam logov CAS požiadaviek',
                    'operationId' => 'casLogs',
                    'security' => [['ApiKeyAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'all',
                            'in' => 'query',
                            'required' => false,
                            'schema' => ['type' => 'boolean'],
                            'description' => 'Ak je hodnota true, vráti všetky logy bez limitu.',
                        ],
                        [
                            'name' => 'limit',
                            'in' => 'query',
                            'required' => false,
                            'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 200, 'default' => 50],
                            'description' => 'Maximálny počet logov pri bežnom výpise.',
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Zoznam logov',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/CasLogsResponse'],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/InvalidApiKey'],
                    ],
                ],
            ],
            '/api/cas/logs/export' => [
                'get' => [
                    'tags' => ['CAS'],
                    'summary' => 'Export logov do CSV',
                    'operationId' => 'casExportLogs',
                    'security' => [['ApiKeyAuth' => []]],
                    'responses' => [
                        '200' => [
                            'description' => 'CSV súbor s logmi',
                            'content' => [
                                'text/csv' => [
                                    'schema' => [
                                        'type' => 'string',
                                        'format' => 'binary',
                                    ],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/InvalidApiKey'],
                    ],
                ],
            ],
            '/api/simulations/ball-beam' => [
                'post' => [
                    'tags' => ['Simulations'],
                    'summary' => 'Výpočet simulácie guličky na tyči',
                    'operationId' => 'simulateBallBeam',
                    'security' => [['ApiKeyAuth' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/BallBeamRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Dáta pre animáciu a graf',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/SimulationResponse'],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/InvalidApiKey'],
                        '422' => ['$ref' => '#/components/responses/SimulationError'],
                    ],
                ],
            ],
            '/api/simulations/inverted-pendulum' => [
                'post' => [
                    'tags' => ['Simulations'],
                    'summary' => 'Výpočet simulácie inverzného kyvadla',
                    'operationId' => 'simulateInvertedPendulum',
                    'security' => [['ApiKeyAuth' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/InvertedPendulumRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Dáta pre animáciu a graf',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/SimulationResponse'],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/InvalidApiKey'],
                        '422' => ['$ref' => '#/components/responses/SimulationError'],
                    ],
                ],
            ],
            '/api/statistics/animations' => [
                'get' => [
                    'tags' => ['Statistics'],
                    'summary' => 'Súhrn použití animácií',
                    'operationId' => 'animationStatisticsSummary',
                    'security' => [['ApiKeyAuth' => []]],
                    'responses' => [
                        '200' => [
                            'description' => 'Počty použití jednotlivých animácií',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/StatisticsSummaryResponse'],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/InvalidApiKey'],
                    ],
                ],
            ],
            '/api/statistics/animations/{animationType}' => [
                'get' => [
                    'tags' => ['Statistics'],
                    'summary' => 'Detail použitia konkrétnej animácie',
                    'operationId' => 'animationStatisticsDetails',
                    'security' => [['ApiKeyAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'animationType',
                            'in' => 'path',
                            'required' => true,
                            'schema' => [
                                'type' => 'string',
                                'enum' => ['ball_beam', 'inverted_pendulum'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Detailné záznamy o použití animácie',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/StatisticsDetailsResponse'],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/InvalidApiKey'],
                    ],
                ],
            ],
        ];
    }

    private function components(): array
    {
        return [
            'securitySchemes' => [
                'ApiKeyAuth' => [
                    'type' => 'apiKey',
                    'in' => 'header',
                    'name' => 'X-API-Key',
                    'description' => 'API kľúč definovaný v konfigurácii aplikácie.',
                ],
            ],
            'parameters' => [
                'SessionToken' => [
                    'name' => 'X-Session-Token',
                    'in' => 'header',
                    'required' => false,
                    'schema' => ['type' => 'string', 'default' => 'default-session'],
                    'description' => 'Identifikátor CAS relácie na uchovanie pomocných premenných.',
                ],
            ],
            'responses' => [
                'InvalidApiKey' => [
                    'description' => 'Neplatný alebo chýbajúci API kľúč',
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                        ],
                    ],
                ],
                'SimulationError' => [
                    'description' => 'Validačná chyba alebo chyba pri výpočte simulácie',
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/SimulationErrorResponse'],
                        ],
                    ],
                ],
            ],
            'schemas' => [
                'PingResponse' => [
                    'type' => 'object',
                    'properties' => [
                        'ok' => ['type' => 'boolean', 'example' => true],
                        'message' => ['type' => 'string', 'example' => 'CAS API is ready'],
                    ],
                ],
                'CasExecuteRequest' => [
                    'type' => 'object',
                    'required' => ['command'],
                    'properties' => [
                        'command' => ['type' => 'string', 'example' => 'a=1+1'],
                        'source' => ['type' => 'string', 'maxLength' => 50, 'example' => 'form'],
                    ],
                ],
                'CasExecuteResponse' => [
                    'type' => 'object',
                    'properties' => [
                        'ok' => ['type' => 'boolean'],
                        'command' => ['type' => 'string'],
                        'output' => ['type' => 'string'],
                        'error' => ['type' => 'string', 'nullable' => true],
                        'exit_code' => ['type' => 'integer', 'nullable' => true],
                    ],
                ],
                'CasHistoryResponse' => [
                    'type' => 'object',
                    'properties' => [
                        'ok' => ['type' => 'boolean'],
                        'commands' => [
                            'type' => 'array',
                            'items' => ['$ref' => '#/components/schemas/CasHistoryItem'],
                        ],
                    ],
                ],
                'CasHistoryItem' => [
                    'type' => 'object',
                    'properties' => [
                        'sequence' => ['type' => 'integer', 'example' => 1],
                        'command' => ['type' => 'string', 'example' => 'a=1+1'],
                        'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    ],
                ],
                'CasResetHistoryResponse' => [
                    'type' => 'object',
                    'properties' => [
                        'ok' => ['type' => 'boolean', 'example' => true],
                        'deleted_count' => ['type' => 'integer', 'example' => 2],
                    ],
                ],
                'CasLogsResponse' => [
                    'type' => 'object',
                    'properties' => [
                        'ok' => ['type' => 'boolean'],
                        'logs' => [
                            'type' => 'array',
                            'items' => ['$ref' => '#/components/schemas/CasLog'],
                        ],
                    ],
                ],
                'CasLog' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'source' => ['type' => 'string'],
                        'command' => ['type' => 'string'],
                        'success' => ['type' => 'boolean'],
                        'output' => ['type' => 'string', 'nullable' => true],
                        'error_message' => ['type' => 'string', 'nullable' => true],
                        'ip_address' => ['type' => 'string', 'nullable' => true],
                        'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    ],
                ],
                'BallBeamRequest' => [
                    'type' => 'object',
                    'required' => ['initial_position', 'target_position', 'duration'],
                    'properties' => [
                        'initial_position' => ['type' => 'number', 'minimum' => -0.5, 'maximum' => 0.5, 'example' => 0],
                        'initial_velocity' => ['type' => 'number', 'example' => 0],
                        'initial_angle' => ['type' => 'number', 'description' => 'Uhol tyče v stupňoch.', 'example' => 0],
                        'target_position' => ['type' => 'number', 'minimum' => -0.5, 'maximum' => 0.5, 'example' => 0.25],
                        'duration' => ['type' => 'number', 'minimum' => 1, 'maximum' => 30, 'example' => 5],
                    ],
                ],
                'InvertedPendulumRequest' => [
                    'type' => 'object',
                    'required' => ['initial_position', 'initial_angle', 'target_position', 'duration'],
                    'properties' => [
                        'initial_position' => ['type' => 'number', 'minimum' => -2, 'maximum' => 2, 'example' => 0],
                        'initial_angle' => ['type' => 'number', 'minimum' => -45, 'maximum' => 45, 'example' => 0],
                        'target_position' => ['type' => 'number', 'minimum' => -2, 'maximum' => 2, 'example' => 0.2],
                        'duration' => ['type' => 'number', 'minimum' => 1, 'maximum' => 30, 'example' => 10],
                    ],
                ],
                'SimulationResponse' => [
                    'type' => 'object',
                    'properties' => [
                        'ok' => ['type' => 'boolean', 'example' => true],
                        'time' => ['type' => 'array', 'items' => ['type' => 'number']],
                        'position' => ['type' => 'array', 'items' => ['type' => 'number']],
                        'angle' => ['type' => 'array', 'items' => ['type' => 'number']],
                    ],
                ],
                'SimulationErrorResponse' => [
                    'type' => 'object',
                    'properties' => [
                        'ok' => ['type' => 'boolean', 'example' => false],
                        'error' => ['type' => 'string', 'nullable' => true],
                        'output' => ['type' => 'string', 'nullable' => true],
                    ],
                ],
                'StatisticsSummaryResponse' => [
                    'type' => 'object',
                    'properties' => [
                        'ok' => ['type' => 'boolean'],
                        'summary' => [
                            'type' => 'array',
                            'items' => ['$ref' => '#/components/schemas/StatisticsSummaryItem'],
                        ],
                    ],
                ],
                'StatisticsSummaryItem' => [
                    'type' => 'object',
                    'properties' => [
                        'animation_type' => ['type' => 'string', 'example' => 'ball_beam'],
                        'count' => ['type' => 'integer', 'example' => 3],
                    ],
                ],
                'StatisticsDetailsResponse' => [
                    'type' => 'object',
                    'properties' => [
                        'ok' => ['type' => 'boolean'],
                        'animation_type' => ['type' => 'string', 'example' => 'ball_beam'],
                        'usages' => [
                            'type' => 'array',
                            'items' => ['$ref' => '#/components/schemas/AnimationUsage'],
                        ],
                    ],
                ],
                'AnimationUsage' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'animation_type' => ['type' => 'string'],
                        'city' => ['type' => 'string', 'nullable' => true],
                        'country' => ['type' => 'string', 'nullable' => true],
                        'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    ],
                ],
                'ErrorResponse' => [
                    'type' => 'object',
                    'properties' => [
                        'message' => ['type' => 'string', 'example' => 'Invalid API key'],
                    ],
                ],
            ],
        ];
    }

    private function translateSpecification(array $specification, string $language): array
    {
        if ($language === 'sk') {
            return $specification;
        }

        return $this->translateValue($specification, $this->englishTranslations());
    }

    private function translateValue(mixed $value, array $translations): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->translateValue($item, $translations);
            }

            return $value;
        }

        if (is_string($value)) {
            return $translations[$value] ?? $value;
        }

        return $value;
    }

    private function englishTranslations(): array
    {
        return [
            'REST API pre CAS výpočty, simulácie dynamických systémov, logy a štatistiky.' => 'REST API for CAS calculations, dynamic system simulations, logs, and statistics.',
            'Aktuálna inštalácia aplikácie' => 'Current application installation',
            'CAS príkazy v Octave, história a logy' => 'CAS commands in Octave, history, and logs',
            'Výpočty dát pre synchronizované animácie' => 'Data calculations for synchronized animations',
            'Štatistiky používania animácií' => 'Animation usage statistics',
            'Overenie dostupnosti CAS API' => 'Check CAS API availability',
            'CAS API je dostupné' => 'CAS API is available',
            'Spustenie príkazu v Octave' => 'Run an Octave command',
            'Príkaz sa spustí spolu s históriou príkazov aktuálnej relácie, aby bolo možné používať pomocné premenné.' => 'The command runs together with the current session command history so helper variables can be reused.',
            'Príkaz bol úspešne vykonaný' => 'The command was executed successfully',
            'Validačná chyba alebo chyba príkazu v Octave' => 'Validation error or Octave command error',
            'Zoznam príkazov v histórii relácie' => 'List commands in the session history',
            'História príkazov' => 'Command history',
            'Vymazanie histórie aktuálnej relácie' => 'Reset the current session history',
            'História bola vymazaná' => 'History was deleted',
            'Zoznam logov CAS požiadaviek' => 'List CAS request logs',
            'Ak je hodnota true, vráti všetky logy bez limitu.' => 'If true, returns all logs without a limit.',
            'Maximálny počet logov pri bežnom výpise.' => 'Maximum number of logs in the standard listing.',
            'Zoznam logov' => 'Log list',
            'Export logov do CSV' => 'Export logs to CSV',
            'CSV súbor s logmi' => 'CSV file with logs',
            'Výpočet simulácie guličky na tyči' => 'Calculate the ball and beam simulation',
            'Dáta pre animáciu a graf' => 'Data for the animation and chart',
            'Výpočet simulácie inverzného kyvadla' => 'Calculate the inverted pendulum simulation',
            'Súhrn použití animácií' => 'Animation usage summary',
            'Počty použití jednotlivých animácií' => 'Usage counts for individual animations',
            'Detail použitia konkrétnej animácie' => 'Usage detail for a selected animation',
            'Detailné záznamy o použití animácie' => 'Detailed animation usage records',
            'API kľúč definovaný v konfigurácii aplikácie.' => 'API key defined in the application configuration.',
            'Identifikátor CAS relácie na uchovanie pomocných premenných.' => 'CAS session identifier used to preserve helper variables.',
            'Neplatný alebo chýbajúci API kľúč' => 'Invalid or missing API key',
            'Validačná chyba alebo chyba pri výpočte simulácie' => 'Validation error or simulation calculation error',
            'Uhol tyče v stupňoch.' => 'Beam angle in degrees.',
        ];
    }
}
