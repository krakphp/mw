<?php

namespace Krak\Mw;

use InvalidArgumentException;

class Stack
{
    private $entries;
    private $names;

    public function __construct(array $entries = []) {
        $this->entries = [];
        $this->names = [];

        $this->fill($entries);
    }

    public function fill($entries) {
        foreach ($entries as $entry) {
            $this->push($entry);
        }
        return $this;
    }

    public function push($mw, $sort = 0, $name = null) {
        $this->ensureEntryStack($sort);
        if ($this->replaceEntry($mw, $name)) {
            return $this;
        }
        array_push($this->entries[$sort], [$mw, $name]);
        if ($name) {
            $this->names[$name] = [$sort, count($this->entries[$sort]) - 1];
        }
        return $this;
    }
    public function pop($sort = 0) {
        if (!isset($this->entries[$sort])) {
            return $this;
        }
        list($mw, $name) = array_pop($this->entries[$sort]);
        unset($this->names[$name]);
        return $this;
    }

    public function unshift($mw, $sort = 0, $name = null) {
        $this->ensureEntryStack($sort);
        if ($this->replaceEntry($mw, $name)) {
            return $this;
        }
        array_unshift($this->entries[$sort], [$mw, $name]);
        if ($name) {
            $this->names[$name] = [$sort, 0];
        }
        return $this;
    }
    public function shift($sort = 0) {
        if (!isset($this->entries[$sort])) {
            return $this;
        }
        list($mw, $name) = array_shift($this->entries[$sort]);
        unset($this->names[$name]);
        return $this;
    }

    public function after($target, $mw, $name = null) {
        if (!isset($this->names[$target])) {
            throw new \LogicException("Cannot insert entry after '$target' because it's not in the stack");
        }
        list($sort, $i) = $this->names[$target];
        return $this->push($mw, $sort, $name);
    }
    public function before($target, $mw, $name = null) {
        if (!isset($this->names[$target])) {
            throw new \LogicException("Cannot insert entry before '$target' because it's not in the stack");
        }
        list($sort, $i) = $this->names[$target];
        return $this->unshift($mw, $sort, $name);
    }

    public function on($name, $mw, $sort = 0) {
        return $this->push($mw, $sort, $name);
    }
    public function remove($name) {
        if (!isset($this->names[$name])) {
            return $this;
        }
        list($sort, $i) = $this->names[$name];
        array_splice($this->entries[$sort], $i, 1);
        return $this;
    }

    public function toArray() {
        ksort($this->entries);
        $iter = function($entries) {
            foreach ($entries as $entry_stack) {
                foreach ($entry_stack as list($mw, $name)) {
                    yield $mw;
                }
            }
        };
        return iterator_to_array($iter($this->entries));
    }

    public function __invoke(...$params) {
        $mw = group($this->toArray());
        return $mw(...$params);
    }

    private function ensureEntryStack($sort) {
        if (!isset($this->entries[$sort])) {
            $this->entries[$sort] = [];
        }
    }

    private function replaceEntry($mw, $name) {
        if (!isset($this->names[$name])) {
            return false;
        }

        list($sort, $i) = $this->names[$name];
        $this->entries[$sort][$i] = [$mw, $name];
        return true;
    }
}
