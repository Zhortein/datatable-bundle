<?php

declare(strict_types=1);

use FriendsOfTwig\Twigcs\Config\Config;
use FriendsOfTwig\Twigcs\Finder\TemplateFinder;
use FriendsOfTwig\Twigcs\Ruleset\Official;

$finder = TemplateFinder::create()
    ->in(__DIR__ . '/templates')
;

return Config::create()
    ->setName('zhortein/datatable-bundle')
    ->setSeverity('warning')
    ->setRuleset(Official::class)
    ->setFinder($finder)
    ;