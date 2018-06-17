<?php

namespace SimplePhpDocumentStore\Store;

use RuntimeException;

class ArrayPathGenerator
{
    public function generate(array $array)
    {
        return $this->iterate($array, '');
    }

    private function iterate(array $array,
                             $path)
    {
        foreach ($array as $key => $value) {
            $newPath = trim($path . '.' . (is_int($key) ? '' : $key), '.');
            if (is_array($value)) {
                foreach ($this->iterate($value, $newPath) as $innerKey => $innerValue) {
                    yield $innerKey => $innerValue;
                }
            } elseif (is_scalar($value)) {
                yield $newPath => $value;
            } else {
                throw new RuntimeException('Unsupported type document under the path ' . $newPath);
            }
        }
    }
}