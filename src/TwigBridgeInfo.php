<?php

namespace Oasis\SlimVue;

class TwigBridgeInfo implements SlimVueBridgeInterface
{
    public function __construct(
        private array $data = [],
    ) {}

    public function add(string $key, mixed $value): void
    {
        $this->data[$key] = $this->getPlainValue($value);
    }

    public function getExecTwig(string $pageTwig): string
    {
        return preg_replace('#^slimvue/pages/#', 'slimvue/controllers/', $pageTwig);
    }

    public function render(): string
    {
        $result = \json_encode($this->data);
        if ($result === false) {
            throw new \InvalidArgumentException(\json_last_error_msg());
        }

        return $result;
    }

    private function getPlainValue(mixed $data): mixed
    {
        return match (true) {
            is_array($data) => array_map(fn(mixed $item): mixed => $this->getPlainValue($item), $data),
            $data instanceof \JsonSerializable => $data->jsonSerialize(),
            default => $data,
        };
    }
}
