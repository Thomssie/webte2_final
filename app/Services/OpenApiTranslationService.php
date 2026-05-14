<?php

namespace App\Services;

class OpenApiTranslationService
{
    public function translate(array $specification, string $language): array
    {
        if ($language !== 'en') {
            return $specification;
        }

        $specification['info']['description'] = 'REST API for CAS calculations, dynamic system simulations, logs, and statistics.';

        if (isset($specification['components']['securitySchemes']['ApiKeyAuth'])) {
            $specification['components']['securitySchemes']['ApiKeyAuth']['description'] = 'API key defined in the application configuration.';
        }

        $operationTranslations = [
            'POST /cas/execute' => [
                'summary' => 'Execute an Octave command',
                'description' => 'Runs an Octave command with the current session history so helper variables can be reused.',
            ],
            'GET /cas/history' => [
                'summary' => 'Session command history',
                'description' => 'Returns commands stored under the selected session token.',
            ],
            'POST /cas/history/reset' => [
                'summary' => 'Reset session history',
                'description' => 'Deletes stored commands for the selected session token.',
            ],
            'GET /cas/logs' => [
                'summary' => 'CAS request logs',
                'description' => 'Returns logs of CAS and simulation requests.',
            ],
            'GET /cas/logs/export' => [
                'summary' => 'Export logs to CSV',
                'description' => 'Returns a CSV file with all stored CAS logs.',
            ],
            'POST /simulations/ball-beam' => [
                'summary' => 'Ball and beam simulation',
                'description' => 'Returns time, ball position, and beam angle data for the animation.',
            ],
            'POST /simulations/inverted-pendulum' => [
                'summary' => 'Inverted pendulum simulation',
                'description' => 'Returns time, cart position, and pendulum angle data for the animation.',
            ],
            'GET /statistics/animations' => [
                'summary' => 'Animation usage summary',
                'description' => 'Returns aggregated usage counts for each animation type.',
            ],
            'GET /statistics/animations/{animationType}' => [
                'summary' => 'Animation usage details',
                'description' => 'Returns detailed usage records for the selected animation type.',
            ],
        ];

        $parameterTranslations = [
            'header:Session-Token' => 'CAS session identifier.',
            'query:all' => 'When true, returns all logs without a limit.',
            'query:limit' => 'Maximum number of logs in the standard listing.',
            'path:animationType' => 'Animation type.',
        ];

        foreach (($specification['paths'] ?? []) as $path => $pathDefinition) {
            foreach ($pathDefinition as $method => $operation) {
                $operationKey = strtoupper($method) . ' ' . $path;

                if (isset($operationTranslations[$operationKey])) {
                    $specification['paths'][$path][$method]['summary'] = $operationTranslations[$operationKey]['summary'];
                    $specification['paths'][$path][$method]['description'] = $operationTranslations[$operationKey]['description'];
                }

                foreach (($operation['parameters'] ?? []) as $index => $parameter) {
                    $parameterKey = ($parameter['in'] ?? '') . ':' . ($parameter['name'] ?? '');

                    if (isset($parameterTranslations[$parameterKey])) {
                        $specification['paths'][$path][$method]['parameters'][$index]['description'] = $parameterTranslations[$parameterKey];
                    }
                }
            }
        }

        return $specification;
    }
}
