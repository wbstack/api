<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use enshrined\svgSanitize\Sanitizer;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Wikimedia\CSS\Objects\Token;
use Wikimedia\CSS\Parser\Parser;

class SvgLogo {
    public function sanitize(string $contents): string {
        $this->parseSvg($contents);
        $sanitizer = new Sanitizer();
        $sanitizer->removeRemoteReferences(true);
        $sanitized = $sanitizer->sanitize($contents);
        if (!$sanitized) {
            throw ValidationException::withMessages(['logo' => 'The SVG logo must be a valid SVG document.']);
        }

        $document = $this->parseSvg($sanitized);

        foreach ($document->getElementsByTagName('*') as $element) {
            if (!$element instanceof DOMElement) {
                continue;
            }
            if ($element->localName === 'style') {
                $this->checkCss($element->textContent);
            }
            foreach ($element->attributes as $attribute) {
                if ($attribute->localName === 'href' && $attribute->value !== '' &&
                    !str_starts_with($attribute->value, '#') &&
                    !preg_match('~^data:image/(png|jpeg|gif);base64,~i', $attribute->value)) {
                    throw ValidationException::withMessages(['logo' => 'SVG logos must not reference external resources.']);
                }
                if (in_array($attribute->localName, [
                    'style', 'font', 'clip-path', 'fill', 'filter', 'marker',
                    'marker-end', 'marker-mid', 'marker-start', 'mask', 'stroke', 'cursor',
                ], true)) {
                    $this->checkCss($attribute->value);
                }
            }
        }

        return $sanitized;
    }

    private function parseSvg(string $contents): DOMDocument {
        $document = new DOMDocument();
        $loaded = $contents !== '' && $document->loadXML($contents, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        $root = $document->documentElement;
        if (!$loaded || $root === null || $root->localName !== 'svg' || $root->namespaceURI !== 'http://www.w3.org/2000/svg') {
            throw ValidationException::withMessages(['logo' => 'The SVG logo must be a valid SVG document.']);
        }

        return $document;
    }

    private function checkCss(string $css): void {
        // Like MediaWiki's SVGCSSChecker, inspect parsed tokens, not URL-matching regexes.
        if (preg_match('/[\x00-\x08\x0b\x0e-\x1f\x7f]/', $css)) {
            throw ValidationException::withMessages(['logo' => 'The SVG logo contains invalid CSS.']);
        }
        $parser = Parser::newFromString($css);
        $tokens = $parser->parseComponentValueList()->toTokenArray();
        if ($parser->getParseErrors()) {
            throw ValidationException::withMessages(['logo' => 'The SVG logo contains invalid CSS.']);
        }
        foreach ($tokens as $index => $token) {
            $type = $token->type();
            $value = strtolower((string) $token->value());
            if ($type === Token::T_URL && str_starts_with($value, '#')) {
                continue;
            }
            if ($type === Token::T_FUNCTION && $value === 'url') {
                $next = $index + 1;
                while (isset($tokens[$next]) && $tokens[$next]->type() === Token::T_WHITESPACE) {
                    $next++;
                }
                if (isset($tokens[$next]) && $tokens[$next]->type() === Token::T_STRING &&
                    str_starts_with($tokens[$next]->value(), '#')) {
                    continue;
                }
            } elseif (!in_array($type, [Token::T_URL, Token::T_BAD_URL], true) &&
                !($type === Token::T_AT_KEYWORD && in_array($value, ['import', 'charset'], true)) &&
                !($type === Token::T_FUNCTION && in_array($value, [
                    'src', 'image', 'image-set', '-webkit-image-set', 'expression',
                ], true))) {
                continue;
            }
            throw ValidationException::withMessages(['logo' => 'SVG logos must not reference external resources.']);
        }
    }

    public function rasterize(string $sanitized): string {
        // Standard input leaves librsvg without a base URL, disabling external file access.
        $process = new Process([
            'rsvg-convert', '--format=png', '--width=135', '--height=135', '--keep-aspect-ratio',
        ]);
        $process->setInput($sanitized);
        $process->setTimeout(10);
        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            if ($process->getExitCode() !== 1) {
                throw $e;
            }
            throw ValidationException::withMessages([
                'logo' => 'The SVG logo could not be rendered. Please upload a self-contained SVG with a valid viewBox or dimensions.',
            ]);
        }

        return $process->getOutput();
    }
}
