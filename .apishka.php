<?php

return array(
    'easy-extend' => array(
        'finder' => function($finder)
        {
            $finder
                ->in(__DIR__ . DIRECTORY_SEPARATOR . 'source')
                ->files()
                ->name('*.php')
            ;

            return $finder;
        }
    ),
);
