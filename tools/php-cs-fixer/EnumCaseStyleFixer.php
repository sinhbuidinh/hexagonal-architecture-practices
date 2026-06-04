<?php

declare(strict_types=1);

namespace HexagonPractise\Tools\PhpCsFixer;

use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

/**
 * Enforces UPPER_SNAKE_CASE enum case names and aligns "=" on consecutive enum case lines.
 * A blank line, comment-only line, or non-case statement starts a new alignment group.
 */
final class EnumCaseStyleFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Enum case names must be UPPER_SNAKE_CASE; consecutive enum case lines align "=".',
            [],
        );
    }

    public function getPriority(): int
    {
        return -64;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(\T_ENUM) || $tokens->isTokenKindFound(\T_DOUBLE_COLON);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        $this->fixEnumCaseReferences($tokens);
        $this->fixEnumCaseDeclarations($tokens);
    }

    private function fixEnumCaseReferences(Tokens $tokens): void
    {
        for ($index = 0, $count = $tokens->count(); $index < $count; ++$index) {
            if (!$tokens[$index]->isGivenKind(\T_DOUBLE_COLON)) {
                continue;
            }

            $nameIndex = $tokens->getNextMeaningfulToken($index);
            if ($nameIndex === null || !$tokens[$nameIndex]->isGivenKind(\T_STRING)) {
                continue;
            }

            $name = $tokens[$nameIndex]->getContent();
            if (!$this->shouldRenameCaseName($name)) {
                continue;
            }

            $tokens[$nameIndex] = new Token([\T_STRING, $this->toUpperSnakeCase($name)]);
        }
    }

    private function fixEnumCaseDeclarations(Tokens $tokens): void
    {
        $lineByIndex = $this->tokenLineNumbers($tokens);

        for ($index = 0, $count = $tokens->count(); $index < $count; ++$index) {
            if (!$tokens[$index]->isGivenKind(\T_ENUM)) {
                continue;
            }

            $openBrace = $tokens->getNextTokenOfKind($index, ['{']);
            if ($openBrace === null) {
                continue;
            }

            $closeBrace = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $openBrace);

            /** @var list<array{index: int, line: int, equalsIndex: int|null}> $cases */
            $cases = [];

            for ($bodyIndex = $openBrace + 1; $bodyIndex < $closeBrace; ++$bodyIndex) {
                if (!$tokens[$bodyIndex]->isGivenKind(\T_CASE)) {
                    continue;
                }

                $nameIndex = $tokens->getNextMeaningfulToken($bodyIndex);
                if ($nameIndex === null || !$tokens[$nameIndex]->isGivenKind(\T_STRING)) {
                    continue;
                }

                $name = $tokens[$nameIndex]->getContent();
                if ($this->shouldRenameCaseName($name)) {
                    $tokens[$nameIndex] = new Token([\T_STRING, $this->toUpperSnakeCase($name)]);
                }

                $equalsIndex = $this->findEnumCaseEqualsIndex($tokens, $nameIndex, $closeBrace);
                $cases[] = [
                    'index' => $bodyIndex,
                    'line' => $lineByIndex[$bodyIndex],
                    'equalsIndex' => $equalsIndex,
                ];
            }

            $this->alignEnumCaseEquals($tokens, $lineByIndex, $cases);
        }
    }

    /**
     * @param list<array{index: int, line: int, equalsIndex: int|null}> $cases
     * @param array<int, int> $lineByIndex
     */
    private function alignEnumCaseEquals(Tokens $tokens, array $lineByIndex, array $cases): void
    {
        $backedCases = array_values(array_filter(
            $cases,
            static fn (array $case): bool => $case['equalsIndex'] !== null,
        ));

        if ($backedCases === []) {
            return;
        }

        $lineNumbers = array_values(array_unique(array_column($backedCases, 'line')));
        sort($lineNumbers);

        foreach ($this->consecutiveLineGroups($lineNumbers) as $groupLines) {
            $group = array_values(array_filter(
                $backedCases,
                static fn (array $item): bool => \in_array($item['line'], $groupLines, true),
            ));

            if (\count($groupLines) < 2) {
                foreach ($group as $item) {
                    $this->setWhitespaceBeforeEquals($tokens, $item['equalsIndex'], 1);
                }

                continue;
            }

            $targetColumn = 0;
            foreach ($group as $item) {
                $targetColumn = max(
                    $targetColumn,
                    $this->equalsColumn($tokens, $lineByIndex, $item['equalsIndex'], $item['line']),
                );
            }

            foreach ($group as $item) {
                $beforeColumn = $this->columnBeforeEquals($tokens, $lineByIndex, $item['equalsIndex'], $item['line']);
                $this->setWhitespaceBeforeEquals($tokens, $item['equalsIndex'], max(1, $targetColumn - $beforeColumn));
            }
        }
    }

    private function findEnumCaseEqualsIndex(Tokens $tokens, int $nameIndex, int $closeBrace): ?int
    {
        for ($index = $nameIndex + 1; $index < $closeBrace; ++$index) {
            if ($tokens[$index]->equals(';')) {
                return null;
            }

            if ($tokens[$index]->equals('=')
                && !$tokens[$index - 1]->equals('=')
                && !$tokens[$index + 1]->equals('=')
            ) {
                return $index;
            }
        }

        return null;
    }

    private function shouldRenameCaseName(string $name): bool
    {
        if (preg_match('/^[A-Z][A-Z0-9_]*$/', $name) === 1) {
            return false;
        }

        if (preg_match('/^[A-Z][a-z0-9]*([A-Z][a-z0-9]*)*$/', $name) !== 1) {
            return false;
        }

        return $this->toUpperSnakeCase($name) !== $name;
    }

    private function toUpperSnakeCase(string $name): string
    {
        $snake = preg_replace('/(?<!^)[A-Z]/', '_$0', $name) ?? $name;

        return strtoupper(str_replace('-', '_', $snake));
    }

    /**
     * @param list<int> $lineNumbers
     *
     * @return list<list<int>>
     */
    private function consecutiveLineGroups(array $lineNumbers): array
    {
        $groups = [];
        $current = [];

        foreach ($lineNumbers as $line) {
            if ($current === [] || $line === $current[\count($current) - 1] + 1) {
                $current[] = $line;

                continue;
            }

            $groups[] = $current;
            $current = [$line];
        }

        if ($current !== []) {
            $groups[] = $current;
        }

        return $groups;
    }

    /**
     * @return array<int, int>
     */
    private function tokenLineNumbers(Tokens $tokens): array
    {
        $line = 1;
        $lineByIndex = [];

        for ($index = 0, $count = $tokens->count(); $index < $count; ++$index) {
            $lineByIndex[$index] = $line;
            $line += substr_count($tokens[$index]->getContent(), "\n");
        }

        return $lineByIndex;
    }

    /**
     * @param array<int, int> $lineByIndex
     */
    private function columnBeforeEquals(Tokens $tokens, array $lineByIndex, int $index, int $line): int
    {
        $column = 0;

        for ($i = 0; $i < $index; ++$i) {
            if ($lineByIndex[$i] !== $line || $tokens[$i]->isWhitespace()) {
                continue;
            }

            $column += \strlen($tokens[$i]->getContent());
        }

        return $column;
    }

    /**
     * @param array<int, int> $lineByIndex
     */
    private function equalsColumn(Tokens $tokens, array $lineByIndex, int $index, int $line): int
    {
        $column = $this->columnBeforeEquals($tokens, $lineByIndex, $index, $line);

        if ($index > 0 && $tokens[$index - 1]->isWhitespace() && !str_contains($tokens[$index - 1]->getContent(), "\n")) {
            $column += \strlen($tokens[$index - 1]->getContent());
        }

        return $column;
    }

    private function setWhitespaceBeforeEquals(Tokens $tokens, int $index, int $spaces): void
    {
        $content = str_repeat(' ', $spaces);

        if ($index > 0 && $tokens[$index - 1]->isWhitespace() && !str_contains($tokens[$index - 1]->getContent(), "\n")) {
            $tokens[$index - 1] = new Token([\T_WHITESPACE, $content]);

            return;
        }

        $tokens->insertAt($index, new Token([\T_WHITESPACE, $content]));
    }
}
