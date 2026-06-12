<?php

declare(strict_types=1);

/**
 * Course rule checker for named functions and methods.
 *
 * It requires parameter types and return types where PHP allows them.
 * Constructors and destructors are excluded because PHP forbids return types there.
 */
$scanDirectories = ['app', 'routes', 'tests'];
$violations = [];

foreach ($scanDirectories as $scanDirectory) {
    if (! is_dir($scanDirectory)) {
        continue;
    }

    $directoryIterator = new RecursiveDirectoryIterator(
        $scanDirectory,
        FilesystemIterator::SKIP_DOTS,
    );
    $fileIterator = new RecursiveIteratorIterator($directoryIterator);

    foreach ($fileIterator as $fileInfo) {
        if (! $fileInfo instanceof SplFileInfo || $fileInfo->getExtension() !== 'php') {
            continue;
        }

        checkFile($fileInfo->getPathname(), $violations);
    }
}

if ($violations !== []) {
    fwrite(STDERR, "Native type declaration violations:\n");

    foreach ($violations as $violation) {
        fwrite(STDERR, "- {$violation}\n");
    }

    exit(1);
}

echo "Native parameter and return type checks passed.\n";

/**
 * @param  list<string>  $violations
 */
function checkFile(string $filePath, array &$violations): void
{
    $sourceCode = file_get_contents($filePath);

    if ($sourceCode === false) {
        $violations[] = "{$filePath}: cannot read file.";

        return;
    }

    if (! str_starts_with($sourceCode, '<?php')) {
        $violations[] = "{$filePath}: file must start with <?php.";
    }

    if (! str_contains($sourceCode, 'declare(strict_types=1);')) {
        $violations[] = "{$filePath}: strict_types declaration is missing.";
    }

    if (preg_match('/<\?(?!php|=)/i', $sourceCode) === 1) {
        $violations[] = "{$filePath}: short PHP opening tag is forbidden.";
    }

    if (preg_match('/\b(?:echo|print)\s+[\'"][^\'"]*</i', $sourceCode) === 1) {
        $violations[] = "{$filePath}: do not output HTML markup with echo or print.";
    }

    $tokens = token_get_all($sourceCode);
    $tokenCount = count($tokens);
    $allowedSingleLetterVariables = ['$i', '$j', '$k'];
    $ignoredVariables = [
        '$this',
        '$_GET',
        '$_POST',
        '$_REQUEST',
        '$_SERVER',
        '$_SESSION',
        '$_COOKIE',
        '$_FILES',
        '$_ENV',
        '$GLOBALS',
    ];

    for ($tokenIndex = 0; $tokenIndex < $tokenCount; $tokenIndex++) {
        $token = $tokens[$tokenIndex];

        if (is_array($token) && $token[0] === T_VARIABLE) {
            $variableName = $token[1];

            if (
                ! in_array($variableName, $ignoredVariables, true)
                && ! in_array($variableName, $allowedSingleLetterVariables, true)
                && preg_match('/^\$[a-z][a-zA-Z0-9]*$/', $variableName) !== 1
            ) {
                $violations[] = "{$filePath}:{$token[2]} variable {$variableName} must use meaningful camelCase.";
            }
        }

        if (! is_array($token) || $token[0] !== T_FUNCTION) {
            continue;
        }

        $functionLine = $token[2];
        $nameIndex = nextMeaningfulTokenIndex($tokens, $tokenIndex + 1);

        if ($nameIndex === null) {
            continue;
        }

        if (tokenText($tokens[$nameIndex]) === '&') {
            $nameIndex = nextMeaningfulTokenIndex($tokens, $nameIndex + 1);
        }

        if ($nameIndex === null || ! is_array($tokens[$nameIndex]) || $tokens[$nameIndex][0] !== T_STRING) {
            // Anonymous functions are intentionally excluded from this course-specific check.
            continue;
        }

        $functionName = $tokens[$nameIndex][1];
        $openParenthesisIndex = findTokenText($tokens, '(', $nameIndex + 1);

        if ($openParenthesisIndex === null) {
            continue;
        }

        $closeParenthesisIndex = findMatchingParenthesis($tokens, $openParenthesisIndex);

        if ($closeParenthesisIndex === null) {
            $violations[] = "{$filePath}:{$functionLine} cannot parse {$functionName}().";

            continue;
        }

        checkParameters(
            $tokens,
            $openParenthesisIndex + 1,
            $closeParenthesisIndex - 1,
            $filePath,
            $functionLine,
            $functionName,
            $violations,
        );

        if (! in_array(strtolower($functionName), ['__construct', '__destruct'], true)) {
            $returnTypeIndex = nextMeaningfulTokenIndex($tokens, $closeParenthesisIndex + 1);

            if ($returnTypeIndex === null || tokenText($tokens[$returnTypeIndex]) !== ':') {
                $violations[] = "{$filePath}:{$functionLine} {$functionName}() has no return type.";
            }
        }
    }
}

/**
 * @param  array<int, array{int, string, int}|string>  $tokens
 * @param  list<string>  $violations
 */
function checkParameters(
    array $tokens,
    int $startIndex,
    int $endIndex,
    string $filePath,
    int $functionLine,
    string $functionName,
    array &$violations,
): void {
    $parameterStartIndex = $startIndex;
    $nestingLevel = 0;

    for ($tokenIndex = $startIndex; $tokenIndex <= $endIndex + 1; $tokenIndex++) {
        $tokenText = $tokenIndex <= $endIndex ? tokenText($tokens[$tokenIndex]) : ',';

        if (in_array($tokenText, ['(', '[', '{'], true)) {
            $nestingLevel++;
        } elseif (in_array($tokenText, [')', ']', '}'], true)) {
            $nestingLevel--;
        }

        if ($tokenText !== ',' || $nestingLevel !== 0) {
            continue;
        }

        $parameterTokens = array_slice(
            $tokens,
            $parameterStartIndex,
            $tokenIndex - $parameterStartIndex,
            true,
        );
        $parameterStartIndex = $tokenIndex + 1;
        $variableIndex = null;

        foreach ($parameterTokens as $currentIndex => $parameterToken) {
            if (is_array($parameterToken) && $parameterToken[0] === T_VARIABLE) {
                $variableIndex = $currentIndex;
                break;
            }
        }

        if ($variableIndex === null) {
            continue;
        }

        $hasType = false;

        foreach ($parameterTokens as $currentIndex => $parameterToken) {
            if ($currentIndex >= $variableIndex) {
                break;
            }

            if (is_array($parameterToken)) {
                if (in_array($parameterToken[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }

                if (defined('T_ATTRIBUTE') && $parameterToken[0] === T_ATTRIBUTE) {
                    continue;
                }

                if (in_array($parameterToken[0], [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_READONLY], true)) {
                    continue;
                }

                $hasType = true;
                break;
            }

            if (! in_array($parameterToken, ['&', '.', '?', '[', ']'], true)) {
                $hasType = true;
                break;
            }
        }

        if (! $hasType) {
            $variableName = is_array($tokens[$variableIndex]) ? $tokens[$variableIndex][1] : '$parameter';
            $violations[] = "{$filePath}:{$functionLine} {$functionName}() parameter {$variableName} has no type.";
        }
    }
}

/**
 * @param  array<int, array{int, string, int}|string>  $tokens
 */
function nextMeaningfulTokenIndex(array $tokens, int $startIndex): ?int
{
    $tokenCount = count($tokens);

    for ($tokenIndex = $startIndex; $tokenIndex < $tokenCount; $tokenIndex++) {
        $token = $tokens[$tokenIndex];

        if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }

        return $tokenIndex;
    }

    return null;
}

/**
 * @param  array<int, array{int, string, int}|string>  $tokens
 */
function findTokenText(array $tokens, string $expectedText, int $startIndex): ?int
{
    $tokenCount = count($tokens);

    for ($tokenIndex = $startIndex; $tokenIndex < $tokenCount; $tokenIndex++) {
        if (tokenText($tokens[$tokenIndex]) === $expectedText) {
            return $tokenIndex;
        }
    }

    return null;
}

/**
 * @param  array<int, array{int, string, int}|string>  $tokens
 */
function findMatchingParenthesis(array $tokens, int $openParenthesisIndex): ?int
{
    $nestingLevel = 0;
    $tokenCount = count($tokens);

    for ($tokenIndex = $openParenthesisIndex; $tokenIndex < $tokenCount; $tokenIndex++) {
        $currentText = tokenText($tokens[$tokenIndex]);

        if ($currentText === '(') {
            $nestingLevel++;
        } elseif ($currentText === ')') {
            $nestingLevel--;

            if ($nestingLevel === 0) {
                return $tokenIndex;
            }
        }
    }

    return null;
}

/**
 * @param  array{int, string, int}|string  $token
 */
function tokenText(array|string $token): string
{
    return is_array($token) ? $token[1] : $token;
}
