<?php

declare(strict_types=1);

namespace HexagonPractise\Tools\PhpCsFixer;

use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

/**
 * Aligns "=" for variable and class-constant assignments on consecutive lines (line N and N+1).
 * A blank line or a line without assignment "=" starts a new run; single-line runs use one space.
 */
final class AlignConsecutiveAssignmentEqualsFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Align assignment "=" for variables and class constants on consecutive lines; blank or other statements break the run.',
            [],
        );
    }

    public function getPriority(): int
    {
        return -64;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound('=');
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        $lineByIndex = $this->tokenLineNumbers($tokens);

        /** @var list<array{index: int, line: int}> $assignments */
        $assignments = [];

        for ($index = 0, $count = $tokens->count(); $index < $count; ++$index) {
            if (!$this->isAssignmentEquals($tokens, $index)) {
                continue;
            }

            $assignments[] = ['index' => $index, 'line' => $lineByIndex[$index]];
        }

        if ($assignments === []) {
            return;
        }

        $lineNumbers = array_values(array_unique(array_column($assignments, 'line')));
        sort($lineNumbers);

        foreach ($this->consecutiveLineGroups($lineNumbers) as $groupLines) {
            $group = array_values(array_filter(
                $assignments,
                static fn (array $item): bool => \in_array($item['line'], $groupLines, true),
            ));

            if (\count($groupLines) < 2) {
                foreach ($group as $item) {
                    $this->setWhitespaceBeforeEquals($tokens, $item['index'], 1);
                }

                continue;
            }

            $targetColumn = 0;
            foreach ($group as $item) {
                $targetColumn = max($targetColumn, $this->equalsColumn($tokens, $lineByIndex, $item['index'], $item['line']));
            }

            foreach ($group as $item) {
                $beforeColumn = $this->columnBeforeEquals($tokens, $lineByIndex, $item['index'], $item['line']);
                $this->setWhitespaceBeforeEquals($tokens, $item['index'], max(1, $targetColumn - $beforeColumn));
            }
        }
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

    private function isAssignmentEquals(Tokens $tokens, int $index): bool
    {
        if (!$tokens[$index]->equals('=')) {
            return false;
        }

        if ($tokens[$index - 1]->equals('=')
            || $tokens[$index + 1]->equals('=')
            || $tokens[$index - 1]->equals('<')
            || $tokens[$index - 1]->equals('>')
            || $tokens[$index - 1]->equals('!')
            || $tokens[$index + 1]->equals('>')
            || $tokens[$index - 1]->equals('.')
        ) {
            return false;
        }

        $previous = $tokens->getPrevMeaningfulToken($index);

        if ($previous === null) {
            return false;
        }

        if ($tokens[$previous]->isGivenKind(\T_VARIABLE)) {
            return !$this->isParameterDefaultEquals($tokens, $index);
        }

        return $tokens[$previous]->isGivenKind(\T_STRING)
            && $this->isClassConstantName($tokens, $previous);
    }

    private function isParameterDefaultEquals(Tokens $tokens, int $index): bool
    {
        $depth = 0;

        for ($i = $index - 1; $i >= 0; --$i) {
            if ($tokens[$i]->equals(')')) {
                ++$depth;

                continue;
            }

            if (!$tokens[$i]->equals('(')) {
                continue;
            }

            if ($depth > 0) {
                --$depth;

                continue;
            }

            $previous = $tokens->getPrevMeaningfulToken($i);

            if ($previous === null) {
                return false;
            }

            if ($tokens[$previous]->isGivenKind(\T_FN)) {
                return true;
            }

            if ($tokens[$previous]->equals('use')) {
                return true;
            }

            if (!$tokens[$previous]->isGivenKind(\T_STRING)) {
                return false;
            }

            $beforeName = $tokens->getPrevMeaningfulToken($previous);

            return $beforeName !== null && $tokens[$beforeName]->isGivenKind(\T_FUNCTION);
        }

        return false;
    }

    private function isClassConstantName(Tokens $tokens, int $nameIndex): bool
    {
        $index = $nameIndex;

        while (($previous = $tokens->getPrevMeaningfulToken($index)) !== null) {
            if ($tokens[$previous]->isGivenKind(\T_CONST)) {
                return true;
            }

            if ($tokens[$previous]->isGivenKind([
                \T_PUBLIC,
                \T_PROTECTED,
                \T_PRIVATE,
                \T_READONLY,
                \T_STRING,
                \T_NS_SEPARATOR,
                \T_NAME_QUALIFIED,
                \T_NAME_FULLY_QUALIFIED,
            ])) {
                $index = $previous;

                continue;
            }

            return false;
        }

        return false;
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
