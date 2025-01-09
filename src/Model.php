<?php

namespace Athos\Foundation;

/**
* Model
* Interfaces for data models.
*
* @package  athos-foundation
* @author   Jannis Nikoy <info@mobles.nl>
* @license  MIT
* @link     https://github.com/jannisnikoy/athos-foundation
*/

interface Model {
    public function insert();
    public function update();
    public function delete();
}
?>
