<?php

namespace Oasis\SlimVue;

interface SlimVueBridgeInterface
{
    public function getExecTwig(string $pageTwig): string;

    public function add(string $key, mixed $value): void;

    public function render(): string;
}
