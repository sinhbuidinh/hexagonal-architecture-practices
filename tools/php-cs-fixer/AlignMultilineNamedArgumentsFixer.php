<?php

declare(strict_types=1);

namespace HexagonPractise\Tools\PhpCsFixer;

use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

/**
 * Vertically aligns ":" in multiline named-argument lists (PHP 8+).
 */
final class AlignMultilineNamedArgumentsFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition('Align colons in multiline named argument lists.');
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(CT::T_NAMED_ARGUMENT_COLON);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index >= 0; --$index) {
            if (!$tokens[$index]->equals('(')) {
                continue;
            }

            $close = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $index);
            if (!$this->isMultiline($tokens, $index, $close)) {
                continue;
            }

            $colonIndices = $this->collectNamedArgumentColons($tokens, $index, $close);
            if ($colonIndices === []) {
                continue;
            }

            $this->alignNamedArgumentColons($tokens, $colonIndices);
        }
    }

    private function isMultiline(Tokens $tokens, int $open, int $close): bool
    {
        for ($i = $open + 1; $i < $close; ++$i) {
            if ($tokens[$i]->isWhitespace() && str_contains($tokens[$i]->getContent(), "\n")) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<int>
     */
    private function collectNamedArgumentColons(Tokens $tokens, int $open, int $close): array
    {
        $colonIndices = [];

        for ($i = $open + 1; $i < $close; ++$i) {
            if ($tokens[$i]->isGivenKind(CT::T_NAMED_ARGUMENT_COLON)) {
                $colonIndices[] = $i;
            }
        }

        return $colonIndices;
    }

    /**
     * @param list<int> $colonIndices
     */
    private function alignNamedArgumentColons(Tokens $tokens, array $colonIndices): void
    {
        /** @var list<array{nameIndex: int, colonIndex: int, nameLength: int}> $items */
        $items = [];
        $maxNameLength = 0;

        foreach ($colonIndices as $colonIndex) {
            $nameIndex = $tokens->getPrevMeaningfulToken($colonIndex);
            if ($nameIndex === null || !$tokens[$nameIndex]->isGivenKind(CT::T_NAMED_ARGUMENT_NAME)) {
                continue;
            }

            $nameLength = \strlen($tokens[$nameIndex]->getContent());
            $maxNameLength = max($maxNameLength, $nameLength);
            $items[] = [
                'nameIndex' => $nameIndex,
                'colonIndex' => $colonIndex,
                'nameLength' => $nameLength,
            ];
        }

        if ($items === []) {
            return;
        }

        usort($items, static fn (array $a, array $b): int => $b['colonIndex'] <=> $a['colonIndex']);

        foreach ($items as $item) {
            $this->clearWhitespaceBetween($tokens, $item['nameIndex'], $item['colonIndex']);

            $colonIndex = $tokens->getNextMeaningfulToken($item['nameIndex']);
            if ($colonIndex === null || !$tokens[$colonIndex]->isGivenKind(CT::T_NAMED_ARGUMENT_COLON)) {
                continue;
            }

            $padding = $maxNameLength - $item['nameLength'];
            if ($padding > 0) {
                $tokens->insertAt($colonIndex, new Token([\T_WHITESPACE, str_repeat(' ', $padding)]));
            }
        }
    }

    private function clearWhitespaceBetween(Tokens $tokens, int $start, int $end): void
    {
        for ($i = $end - 1; $i > $start; --$i) {
            if ($tokens[$i]->isWhitespace()) {
                $tokens->clearAt($i);
            }
        }
    }
}
