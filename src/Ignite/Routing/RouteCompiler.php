<?php

namespace Ignite\Routing;

class RouteCompiler implements RouteCompilerInterface
{
    public function compile(Route $route): CompiledRoute
    {
        $pattern = $route->getPattern();
        $length = strlen($pattern);
        $tokens = [];
        $variables = [];
        $position = 0;

        preg_match_all('#.\{([\w\d_]+)\}#', $pattern, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($matches as $match) {
            if ($text = substr($pattern, $position, $match[0][1] - $position)) {
                $tokens[] = array('text', $text);
            }

            $separators = array($pattern[$position]);
            $position = (int)$match[0][1] + strlen($match[0][1]);
            $variable = $match[1][0];

            if ($requirement = $route->getRequirement($variable)) {
                $regularExpression = $requirement;
            } else {
                if ($position !== $length) {
                    $separators[] = $pattern[$position];
                }

                $regularExpression = sprintf('[^%s]+?', preg_quote(implode('', array_unique($seperators)), '#'));
            }

            $tokens[] = array('variable', $match[0][0][0], $regularExpression, $variable);

            if (in_array($variable, $variables)) {
                throw new \LogicException(sprintf('Route pattern "%s" cannot reference variable name "%s" more than once.', $route->getPattern(), $variable));
            }

            $variables[] = $variable;
        }

        if ($position < $length) {
            $tokens[] = array('text', substr($pattern, $position));
        }

        $firstOptional = INF;

        for ($i = count($tokens) - 1; $i >= 0; $i--) {
            $token = $tokens[$i];
            if ('variable' === $token[0] && $route->hasDefault($token[3])) {
                $firstOptional = $i;
            } else {
                break;
            }
        }

        $regularExpression = '';
        for ($i = 0, $numberOfTokens = count($tokens); $i < $numberOfTokens; $i++) {
            $regularExpression .= $this->computeRegularExpression($tokens, $i, $firstOptional);
        }

        return new CompiledRoute(
            $route,
            'text' === $tokens[0][0] ? $tokens[0][1] : '',
            sprintf("#^%s$#s", $regularExpression),
            array_reverse($tokens),
            $variables
        );
    }

    public function computeRegularExpression(array $tokens, $index, $firstOptional): string
    {
        $token = $tokens[$index];

        if ('text' === $token[0]) {
            return preg_quote($token[1], '#');
        } else {
            if ($index === 0 && $firstOptional === 0 && count($tokens) === 1) {
                return sprintf('%s(?P<%s>%s)?', preg_quote($token[1], '#'), $token[3], $token[2]);
            } else {
                $numberOfTokens = count($tokens);
                $regularExpression = sprintf('%s(?P<%s>%s)', preg_quote($token[1], '#'), $token[3], $token[2]);

                if ($index >= $firstOptional) {
                    $regularExpression = "(?:$regularExpression";
                    if ($numberOfTokens - 1 == $index) {
                        $regularExpression .= str_repeat(")?", $numberOfTokens - $firstOptional);
                    }
                }

                return $regularExpression;
            }
        }
    }
}