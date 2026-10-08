<?php

namespace App\Services;

use App\Exceptions\FaceChallengeFailed;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class FaceRecognitionService
{
    private const MIN_CONFIDENCE = 0.6;

    private const MIN_SIMILARITY = 0.5;

    /**
     * Extract face descriptors and liveness signals on the local server.
     *
     * @param  list<string>  $imagePaths
     * @return list<array{embedding: list<float>, confidence: float, real: float, live: float, gestures: list<string>}>
     */
    public function analyze(array $imagePaths): array
    {
        if (count($imagePaths) !== 4) {
            throw new FaceChallengeFailed('Capture all four requested camera frames.');
        }

        $process = new Process([
            config('services.face_recognition.node_binary', 'node'),
            base_path('scripts/face-frames.mjs'),
            ...$imagePaths,
        ], base_path());
        $process->setTimeout(90);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::error('Local face recognition process failed.', [
                'exit_code' => $process->getExitCode(),
                'error_output' => trim($process->getErrorOutput()),
            ]);

            throw new \RuntimeException('Local face recognition process failed.');
        }

        $result = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        $frames = $result['frames'] ?? null;
        if (! is_array($frames) || count($frames) !== 4) {
            throw new \RuntimeException('Local face recognition returned an invalid result.');
        }

        foreach ($frames as $frame) {
            if (
                ! is_array($frame)
                || ! is_array($frame['embedding'] ?? null)
                || count($frame['embedding']) < 64
                || ! is_numeric($frame['confidence'] ?? null)
                || ! is_numeric($frame['real'] ?? null)
                || ! is_numeric($frame['live'] ?? null)
                || ! is_array($frame['gestures'] ?? null)
            ) {
                throw new \RuntimeException('Local face recognition returned an invalid frame result.');
            }
        }

        return $frames;
    }

    /**
     * Verify all challenge actions and return the frontal face template.
     *
     * @param  list<array>  $frames
     * @param  list<string>  $actions
     * @return list<float>
     */
    public function validatedTemplate(array $frames, array $actions): array
    {
        $this->validateChallenge($frames, $actions);

        $centerFrameIndex = array_search('face_center', $actions, true);

        return $frames[$centerFrameIndex]['embedding'];
    }

    /**
     * Verify a login challenge against the encrypted template.
     *
     * @param  list<float>  $template
     * @param  list<array>  $frames
     * @param  list<string>  $actions
     */
    public function matches(array $template, array $frames, array $actions): bool
    {
        $this->validateChallenge($frames, $actions);

        foreach ($frames as $frame) {
            if (self::similarity($template, $frame['embedding']) < self::MIN_SIMILARITY) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<float>  $first
     * @param  list<float>  $second
     */
    private static function similarity(array $first, array $second): float
    {
        if (count($first) !== count($second)) {
            return 0;
        }

        $sum = 0.0;
        foreach ($first as $index => $value) {
            if (! is_numeric($value) || ! is_numeric($second[$index])) {
                return 0;
            }

            $difference = (float) $value - (float) $second[$index];
            $sum += $difference * $difference;
        }

        $distance = round(100 * 25 * $sum) / 100;
        if ($distance === 0.0) {
            return 1;
        }

        $normalized = (1 - sqrt($distance) / 100 - 0.2) / (0.8 - 0.2);

        return round(100 * max(min($normalized, 1), 0)) / 100;
    }

    /**
     * @param  list<array>  $frames
     * @param  list<string>  $actions
     */
    private function validateChallenge(array $frames, array $actions): void
    {
        if (count($frames) !== 4 || count($actions) !== 4) {
            throw new FaceChallengeFailed('Capture all four requested camera frames.');
        }

        foreach ($frames as $index => $frame) {
            if (
                $frame['confidence'] < self::MIN_CONFIDENCE
                || $frame['real'] < self::MIN_CONFIDENCE
                || $frame['live'] < self::MIN_CONFIDENCE
            ) {
                throw new FaceChallengeFailed('The camera could not confirm a clear live face. Improve the lighting and try again.');
            }

            $gestures = $frame['gestures'];
            $validAction = match ($actions[$index]) {
                'face_left' => in_array('facing left', $gestures, true),
                'blink' => in_array('blink left eye', $gestures, true)
                    || in_array('blink right eye', $gestures, true),
                'face_right' => in_array('facing right', $gestures, true),
                'face_center' => in_array('facing center', $gestures, true)
                    && in_array('looking center', $gestures, true),
                default => false,
            };

            if (! $validAction) {
                throw new FaceChallengeFailed('The camera did not detect the requested action. Follow the prompt and capture that frame again.');
            }
        }
    }
}
