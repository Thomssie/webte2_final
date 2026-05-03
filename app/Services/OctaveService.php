<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class OctaveService
{
    /**
     * @return array{success: bool, output: string, error: string|null, exit_code: int|null}
     */
    public function run(string $command): array
    {
        if (config('cas.slowdown_ms') > 0) {
            usleep(config('cas.slowdown_ms') * 1000);
        }

        $process = new Process([
            config('cas.octave_binary'),
            '--quiet',
            '--no-gui',
            '--no-window-system',
            '--eval',
            $command,
        ]);


        $process->setTimeout(config('cas.timeout_seconds'));
        $process->run();

        $success = $process->isSuccessful();
        $error = trim($process->getErrorOutput());

        return [
            'success' => $success,
            'output' => trim($process->getOutput()),
            'error' => $success ? null : ($error ?: null),
            'exit_code' => $process->getExitCode(),
        ];
    }
}
