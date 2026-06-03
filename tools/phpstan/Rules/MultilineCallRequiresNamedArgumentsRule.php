<?php

declare(strict_types=1);

namespace HexagonPractise\Tools\PhpStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Multiline function/method calls must use named arguments for every parameter.
 *
 * @implements Rule<CallLike>
 */
final class MultilineCallRequiresNamedArgumentsRule implements Rule
{
    public function getNodeType(): string
    {
        return CallLike::class;
    }

    /**
     * @param CallLike $node
     *
     * @return list<\PHPStan\Rules\RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node instanceof New_) {
            return [];
        }

        $args = $node->getArgs();
        if ($args === []) {
            return [];
        }

        if (!$this->isMultilineArgumentList($node, $args)) {
            return [];
        }

        foreach ($args as $arg) {
            if ($arg->name === null) {
                return [
                    RuleErrorBuilder::message(
                        'Multiline call must use named arguments for every parameter.',
                    )->identifier('hexagon.multilineCallRequiresNamedArguments')->build(),
                ];
            }
        }

        return [];
    }

    /**
     * @param list<Arg> $args
     */
    private function isMultilineArgumentList(CallLike $node, array $args): bool
    {
        if ($node->getEndLine() <= $node->getStartLine()) {
            return false;
        }

        if (\count($args) === 1) {
            return $args[0]->getEndLine() > $args[0]->getStartLine();
        }

        $firstLine = $args[0]->getStartLine();
        foreach ($args as $arg) {
            if ($arg->getStartLine() !== $firstLine) {
                return true;
            }
        }

        return $args[0]->getStartLine() !== $node->getStartLine();
    }
}
