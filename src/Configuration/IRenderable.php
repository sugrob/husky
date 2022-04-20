<?php

namespace Husky\Configuration;

interface IRenderable
{
	public function render(): string;
}