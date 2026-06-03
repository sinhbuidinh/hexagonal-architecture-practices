<?php

declare(strict_types=1);

namespace HexagonPractise\Tools\PhpCsFixer;

abstract class AbstractFixer extends \PhpCsFixer\AbstractFixer
{
    public function getName(): string
    {
        return 'HexagonPractise/'.parent::getName();
    }
}
