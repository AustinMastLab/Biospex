<?php

namespace Tests\Unit\Presenters;

trait PresenterTestHelpers
{
    /**
     * Extract the first <a ...>...</a> tag from HTML.
     */
    protected function extractFirstAnchor(string $html): ?string
    {
        if ($html === '') {
            return null;
        }

        if (! preg_match('/<a\b[^>]*>.*?<\/a>/si', $html, $m)) {
            return null;
        }

        return $m[0] ?? null;
    }

    /**
     * Extract a specific attribute value from the given anchor tag.
     */
    protected function getAttr(?string $anchor, string $attr): ?string
    {
        if (! $anchor) {
            return null;
        }

        $pattern = '/\s'.preg_quote($attr, '/').'\s*=\s*([\'"])(.*?)\1/is';
        if (preg_match($pattern, $anchor, $m)) {
            return $m[2];
        }

        return null;
    }

    /**
     * Count suspicious empty attributes on the anchor tag (attribute explosion indicator).
     */
    protected function countEmptyAttributes(?string $anchor): int
    {
        if (! $anchor) {
            return 0;
        }

        return preg_match_all('/\s[a-zA-Z0-9_-]+=""/m', $anchor) ?: 0;
    }

    /**
     * Assert that the HTML output is a valid and safe link.
     * Fails if output is empty.
     */
    public function assertLinkRenderedAndSafe(string $html, array $requiredTokens = [], bool $requireAriaLabel = true): void
    {
        $this->assertNotEmpty($html, 'Link output should not be empty');
        $this->performLinkSafetyChecks($html, $requiredTokens, true, $requireAriaLabel);
    }

    /**
     * Assert that the HTML output is either empty OR a valid and safe link.
     */
    public function assertLinkEmptyOrSafe(string $html, array $requiredTokens = [], bool $requireAriaLabel = true): void
    {
        if ($html === '') {
            $this->assertTrue(true);

            return;
        }

        $this->performLinkSafetyChecks($html, $requiredTokens, false, $requireAriaLabel);
    }

    /**
     * Internal implementation of link safety checks.
     */
    private function performLinkSafetyChecks(string $output, array $requiredTokens = [], bool $mustRender = false, bool $requireAriaLabel = true): void
    {
        $this->assertStringContainsString('<a', $output, 'Output should contain an <a> tag');
        $this->assertStringContainsString('</a>', $output, 'Output should contain closing </a>');

        $anchor = $this->extractFirstAnchor($output);
        $this->assertNotNull($anchor, 'Could not extract <a> tag from output');

        // aria-label checks
        $aria = $this->getAttr($anchor, 'aria-label');
        if ($requireAriaLabel) {
            $this->assertNotNull($aria, 'aria-label is required for this link');
        }

        if ($aria !== null) {
            $this->assertNotEmpty($aria, 'aria-label must not be empty if present');
            $this->assertIsString($aria);
            $this->assertDoesNotMatchRegularExpression('/\"/', $aria, 'aria-label value should not contain raw double quotes');

            // Truncation heuristics: Only run when tokens are required or must render
            if (! empty($requiredTokens) || $mustRender) {
                $this->assertDoesNotMatchRegularExpression('/:\\s*$/', trim((string) $aria), 'aria-label appears truncated (ends with :)');
                $this->assertDoesNotMatchRegularExpression('/\\s$/', (string) $aria, 'aria-label appears truncated (trailing space)');
            }
        }

        // Accessible name strategy enforcement (when tokens are required)
        if (! empty($requiredTokens)) {
            // Extract visible text (strip tags)
            if (preg_match('/<a\b[^>]*>(.*?)<\/a>/si', $anchor, $innerMatches)) {
                $visibleText = strip_tags($innerMatches[1]);
                $visibleText = trim(preg_replace('/\s+/', ' ', $visibleText));
            } else {
                $visibleText = '';
            }

            foreach ($requiredTokens as $token) {
                $this->assertNotEmpty($token, 'Required token was empty');

                $foundInAccessibleName = false;
                $decodedAria = $aria !== null ? html_entity_decode((string) $aria) : '';
                $decodedVisibleText = html_entity_decode((string) $visibleText);

                if ($aria !== null && str_contains($decodedAria, (string) $token)) {
                    $foundInAccessibleName = true;
                }

                if (! $foundInAccessibleName && str_contains($decodedVisibleText, (string) $token)) {
                    $foundInAccessibleName = true;
                }

                // If token not found in accessible name sources, check if it's trapped in destination only
                if (! $foundInAccessibleName) {
                    $foundInDestination = false;
                    foreach (['href', 'data-href', 'data-url', 'data-clipboard-text'] as $destAttr) {
                        $destVal = $this->getAttr($anchor, $destAttr);
                        if ($destVal !== null && str_contains((string) $destVal, (string) $token)) {
                            $foundInDestination = true;
                            break;
                        }
                    }

                    if ($foundInDestination) {
                        $this->fail(sprintf(
                            'Token "%s" found in destination (href/data-*) but not in aria-label or visible link text. This can indicate truncation or non-unique accessible names.',
                            $token
                        ));
                    }

                    // If not even in destination, it's a general failure (token missing entirely)
                    $this->assertTrue($foundInAccessibleName, sprintf('Required token "%s" not found in aria-label or visible text.', $token));
                }
            }
        }

        // Attribute explosion detection
        $emptyAttrCount = $this->countEmptyAttributes($anchor);
        $this->assertTrue($emptyAttrCount <= 3, 'Suspicious number of empty attributes on <a>: '.$emptyAttrCount.' (possible broken quoting)');

        // Better explosion detector: check for word-like attributes with empty values
        if (preg_match_all('/\b[a-zA-Z]{3,}=""/i', $anchor, $matches)) {
            $wordLikeCount = count($matches[0]);
            $this->assertTrue($wordLikeCount <= 2, 'Possible attribute explosion: found too many word-like empty attributes: '.implode(', ', $matches[0]));
        }

        // Other dynamic attributes that may embed user content
        foreach (['title', 'data-title', 'data-content', 'data-success', 'data-error', 'data-clipboard-text'] as $attr) {
            $val = $this->getAttr($anchor, $attr);
            if ($val !== null) {
                $this->assertDoesNotMatchRegularExpression('/\"/', $val, sprintf('%s should not contain raw double quotes', $attr));
                $this->assertDoesNotMatchRegularExpression('/[<>]/', $val, sprintf('%s should not contain raw HTML tags (< or >)', $attr));

                // Truncation heuristics for other attributes when tokens are present
                if (! empty($requiredTokens)) {
                    $this->assertDoesNotMatchRegularExpression('/:\\s*$/', trim((string) $val), sprintf('%s appears truncated (ends with :)', $attr));
                }
            }
        }

        // Balanced quotes for aria-label specifically
        if ($aria !== null) {
            $this->assertMatchesRegularExpression('/aria-label="[^"]*"/i', $anchor, 'aria-label should have balanced quotes');
        }
    }

    /**
     * Backward-compatible helper used by existing tests; delegates to safe check.
     * Deprecated: use assertLinkRenderedAndSafe or assertLinkEmptyOrSafe.
     */
    public function assertStrongLinkSafety(string $output, array $requiredTokens = []): void
    {
        $this->assertLinkEmptyOrSafe($output, $requiredTokens);
    }

    /**
     * Backward-compatible helper used by existing tests; delegates to safe check.
     * Deprecated: use assertLinkRenderedAndSafe or assertLinkEmptyOrSafe.
     */
    public function assertValidLink(string $output): void
    {
        $this->assertStrongLinkSafety($output);
    }
}
