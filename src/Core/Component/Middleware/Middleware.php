<?php
namespace FwSwoole\Middleware;

use Ububs\Core\Component\Factory;

abstract class Middleware extends Factory
{

	abstract public function handle();
}
