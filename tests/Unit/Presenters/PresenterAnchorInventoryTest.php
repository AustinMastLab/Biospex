<?php

namespace Tests\Unit\Presenters;

use Tests\TestCase;

uses(TestCase::class);

/**
 * Whitelist of presenter methods that return anchor tags and are already covered by tests.
 * Format: 'PresenterClass::methodName'
 */
$coveredMethods = CoveredPresenterAnchorMethods::all();

it('ensures all whitelisted methods actually exist', function () use ($coveredMethods) {
    $invalid = [];

    foreach ($coveredMethods as $entry) {
        [$className, $methodName] = explode('::', $entry);
        $file = base_path("app/Presenters/{$className}.php");

        if (! file_exists($file)) {
            $invalid[] = "{$entry} (Class file not found: {$file})";

            continue;
        }

        $content = file_get_contents($file);
        if (! preg_match("/public function {$methodName}\s*\(/i", $content)) {
            $invalid[] = "{$entry} (Method not found in {$file})";
        }
    }

    if (! empty($invalid)) {
        $this->fail(
            "The following whitelisted methods do not exist or are incorrect:\n".
            implode("\n", $invalid)
        );
    }

    expect(true)->toBeTrue();
});

it('ensures all presenter methods returning anchors are whitelisted', function () use ($coveredMethods) {
    $presenterPath = base_path('app/Presenters');
    $files = glob($presenterPath.'/*.php');
    $discovered = [];

    foreach ($files as $file) {
        $className = basename($file, '.php');
        if ($className === 'Presenter') {
            continue;
        }

        $content = file_get_contents($file);

        // Heuristic to find methods that return an anchor tag
        if (preg_match_all('/public function (\w+)\s*\(/', $content, $methodMatches)) {
            foreach ($methodMatches[1] as $methodName) {
                // Simplified method extraction for Pest closure
                $pos = strpos($content, "public function $methodName");
                $nextMethodPos = preg_match('/public function \w+/', $content, $m, PREG_OFFSET_CAPTURE, $pos + 1) ? $m[0][1] : strlen($content);
                $methodContent = substr($content, $pos, $nextMethodPos - $pos);

                if (preg_match('/return\s+.*[\'"]<a/is', $methodContent)) {
                    $discovered[] = "{$className}::{$methodName}";
                }
            }
        }
    }

    $discovered = array_unique($discovered);
    $missing = array_diff($discovered, $coveredMethods);

    if (! empty($missing)) {
        $this->fail(
            "The following presenter methods appear to return anchor tags but are not in the whitelist:\n".
            implode("\n", $missing)."\n\n".
            'Please add tests for these methods in the relevant Presenter test suite and update the whitelist in '.__FILE__
        );
    }

    expect(true)->toBeTrue();
});
