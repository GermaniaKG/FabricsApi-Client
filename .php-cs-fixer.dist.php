<?php
$header = <<<EOF
germania-kg/fabricsapi-client

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

$finder = PhpCsFixer\Finder::create()->in([
    __DIR__ . '/src',
    __DIR__ . '/tests'
]);

return (new PhpCsFixer\Config())
->setFinder($finder)
->setRules([
    '@PhpCsFixer' => true,
    'header_comment' => [
        'comment_type' => 'PHPDoc',
        'header' => $header,
        'location' => 'after_open',
        'separate' => 'both',
    ]
]);
