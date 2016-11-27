<?php

namespace Krak\Mw;

use Countable,
    InvalidArgumentException,
    SplMinHeap;

class MwStack implements Countable
{
    private $name;
    private $entries;
    private $heap;
    private $name_map;

    public function __construct($name) {
        $this->name = $name;
        $this->entries = [];
        $this->heap = new SplMinHeap();
        $this->name_map = [];
    }

    public function getName() {
        return $this->name;
    }

    public function count() {
        return count($this->entries);
    }

    public function push($mw, $sort = 0, $name = null) {
        return $this->insertEntry(stackEntry($mw, $sort, $name), 'array_push');
    }
    public function unshift($mw, $sort = 0, $name = null) {
        return $this->insertEntry(stackEntry($mw, $sort, $name), 'array_unshift');
    }

    /** insert a middleware before the given middleware */
    public function before($name, $mw, $mw_name = null) {
        if (!array_key_exists($name, $this->name_map)) {
            throw new InvalidArgumentException(sprintf('Middleware %s does not exist', $name));
        }

        $sort = $this->name_map[$name];
        return $this->unshift($mw, $sort, $mw_name);
    }
    /** insert a middleware after the given middleware  */
    public function after($name, $mw, $mw_name = null) {
        if (!array_key_exists($name, $this->name_map)) {
            throw new InvalidArgumentException(sprintf('Middleware %s does not exist', $name));
        }

        $sort = $this->name_map[$name];
        return $this->push($mw, $sort, $mw_name);
    }

    private function insertEntry($entry, $insert) {
        list($mw, $sort, $name) = $entry;
        if ($name) {
            // if we are pushing a named middleware, remove the old one so that
            // we don't have any duplicates
            $this->remove($name);
            $this->name_map[$name] = $sort;
        }

        if (!isset($this->entries[$sort])) {
            $this->entries[$sort] = [];
            $this->heap->insert($sort);
        }

        $insert($this->entries[$sort], $entry);
        return $this;
    }

    public function shift($sort = 0) {
        return $this->removeEntry($sort, 'array_shift');
    }

    public function pop($sort = 0) {
        return $this->removeEntry($sort, 'array_pop');
    }

    public function remove($name) {
        if (!array_key_exists($name, $this->name_map)) {
            return;
        }

        $sort = $this->name_map[$name];
        $index = $this->findEntryByName($this->entries[$sort], $name);
        unset($this->name_map[$name]);
        return $this->removeEntry($sort, function(&$entries) use ($index) {
            $entry = $entries[$index];
            unset($entries[$index]);
            return $entry;
        });
    }

    private function removeEntry($sort, $remove) {
        if (!isset($this->entries[$sort])) {
            return;
        }

        $entries = $this->entries[$sort];
        $entry = $remove($entries);

        $this->updateEntries($entries, $sort);

        return $entry;
    }

    /** normalizes the stack into an array of middleware to be used
        with mw\compose. */
    public function normalize() {
        $heap = new SplMinHeap();
        $mws = [];
        foreach ($this->heap as $sort) {
            $heap->insert($sort);
            $entries = $this->entries[$sort];
            foreach ($entries as $entry) {
                $mws[] = $entry[0];
            }
        }

        $this->heap = $heap;

        return $mws;
    }

    /** allows the stack to be used once... as a middleware */
    public function __invoke(...$params) {
        $mw = group($this->normalize());
        return $mw(...$params);
    }

    public function compose($last = null) {
        if (!$this->count()) {
            throw new \RuntimeException(sprintf('Middleware stack "%s" is empty. You cannot compose an empty middleware stack.', $this->getName()));
        }
        $last = $last ?: function() {
            throw new \RuntimeException(sprintf('Middleware stack "%s" was not able to return a response. No middleware in the stack returned a response.', $this->getName()));
        };

        return compose($this->normalize(), $last);
    }

    public function getEntries() {
        foreach ($this->entries as $entries) {
            foreach ($entries as $entry) {
                yield $entry;
            }
        }
    }

    public static function createFromEntries($name, $entries) {
        $stack = new self($name);
        foreach ($entries as $entry) {
            $stack->insertEntry($entry, 'array_push');
        }
        return $stack;
    }

    private function updateEntries($entries, $sort) {
        if (!$entries) {
            unset($this->entries[$sort]);
            $this->heap = _filterHeap($this->heap, function($val) use ($sort) {
                return $val !== $sort;
            });
        } else {
            $this->entries[$sort] = $entries;
        }
    }

    private function findEntryByName($entries, $name) {
        foreach ($entries as $key => $entry) {
            if ($entry[2] == $name) {
                return $key;
            }
        }
    }
}
