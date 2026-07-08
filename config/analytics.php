<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Own Domains
    |--------------------------------------------------------------------------
    |
    | Hosts (as they'd appear in a Referer header) that point at your own
    | sites. Views arriving via these referrers are still counted normally,
    | but flagged in the "Auswertung" so they're not mistaken for organic
    | external traffic - e.g. old/alias domains redirecting here.
    |
    */

    'own_domains' => [
        'skratter.com',
        'www.skratter.com',
        'skratter.net',
        'www.skratter.net',
        'familie-wolf.berlin',
        'www.familie-wolf.berlin',
        'joshua-wolf.de',
        'www.joshua-wolf.de',
        'nudelwasser24.de',
    ],

];
