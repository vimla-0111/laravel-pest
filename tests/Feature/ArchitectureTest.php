<?php

arch()
    ->expect('App')
    // ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

// arch()
    // ->expect('App\*\Traits')
    // ->toBeTraits();
 
arch()->preset()->php();

arch('app')
    ->expect('App')
    ->not->toHaveFileSystemPermissions('0777');

