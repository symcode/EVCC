<?php

/**
 * Class EVCCRegister
 * Constants with EVCC register and value translations
 *
 * @version     0.1
 * @category    Symcon
 * @package     EVCC
 * @author      Hermann Dötsch <info@doetsch-hermann.de>
 * @link        https://github.com/symcode/EVCC
 *
 */
class EVCCRegister
{
    const value_addresses = [

        /**
         * Global
         */

        'mode' => [
            'name' => 'mode',
            'type' => 4, // string
            'scale' => 1,
            'mapping' => [
                0 => 'off',
                1 => 'pv',
                2 => 'minpv',
                3 => 'now'
            ]
        ]
    ];
}