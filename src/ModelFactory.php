<?php

namespace Athos\Foundation;

/**
* ModelFactory
* Factory for data models.
*
* @package  athos-foundation
* @author   Jannis Nikoy <info@mobles.nl>
* @license  MIT
* @link     https://github.com/jannisnikoy/athos-foundation
*/

interface ModelFactory {
    public static function getAll();
    public static function get($id);
}
?>
