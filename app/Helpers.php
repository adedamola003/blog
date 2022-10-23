<?php
/**
 * This file contain general helper functions for the application
 */


/**
 * generate slugs
 * @throws Exception
 */

function generateSlug(): string
{
    return date('his').rand(1000000000,9999999999).date('ymd');
}

