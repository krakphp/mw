<?php

namespace Krak\Mw;

use Countable,
    InvalidArgumentException,
    SplMinHeap;

class MwStack implements Countable
{
    private $name;
    private $ctx;
    private $link_class;
    private $entries;
    private $heap;
    private $name_map;

    public function __construct($name, Context $ctx = null, $link_class = Link::class) {
        $this->name = $name;
        $this->ctx = $ctx;
        $this->link_class = $link_class;
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

    /** alias of push to allow for replacing named middleware */
    public function on($name, $mw, $sort = 0) {
        return $this->insertEntry(stackEntry($mw, $sort, $name), 'array_push');
    }

    /** insert a middleware before the given middleware */
    public function before($name, $mw, $mw_name = null) {
        if (!array_key_exists($name, $this->name_map)) {
            throw new InvalidArgumentException(sprintf('Middleware %s does not exist', $name));
        }

        $sort = $this->name_map[$name];
        $idx = $this->findEntryByName($this->entries[$sort], $name);
        return $this->insertEntry(stackEntry($mw, $sort, $mw_name), function(&$array, $entry) use ($idx) {
            array_splice($array, $idx, 0, [$entry]);
        });
    }
    /** insert a middleware after the given middleware  */
    public function after($name, $mw, $mw_name = null) {
        if (!array_key_exists($name, $this->name_map)) {
            throw new InvalidArgumentException(sprintf('Middleware %s does not exist', $name));
        }

        $sort = $this->name_map[$name];
        $idx = $this->findEntryByName($this->entries[$sort], $name);
        return $this->insertEntry(stackEntry($mw, $sort, $mw_name), function(&$array, $entry) use ($idx) {
            array_splice($array, $idx + 1, 0, [$entry]);
        });
    }

    private function replaceEntry($entry) {
        list($mw, $sort, $name) = $entry;
        $sort = $this->name_map[$name];
        $idx = $this->findEntryByName($this->entries[$sort], $name);
        $this->entries[$sort][$idx] = $entry;
        return $this;
    }

    private function insertEntry($entry, $insert) {
        list($mw, $sort, $name) = $entry;
        if ($name) {
            if (array_key_exists($name, $this->name_map)) {
                return $this->replaceEntry($entry);
            } else {
                $this->name_map[$name] = $sort;
            }
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

        return compose($this->normalize(), $this->ctx, $last, $this->link_class);
    }

    public function withContext(Context $ctx) {
        $stack = clone $this;
        $stack->ctx = $ctx;
        return $stack;
    }
    public function withLinkClass($link_class) {
        $stack = clone $this;
        $stack->link_class = $link_class;
        return $stack;
    }
    public function withEntries($entries) {
        return static::createFromEntries($this->name, $entries, $this->ctx, $this->link_class);
    }

    public function getEntries() {
        foreach ($this->entries as $entries) {
            foreach ($entries as $entry) {
                yield $entry;
            }
        }
    }

    public static function createFromEntries($name, $entries, Context $ctx = null, $link_class = Link::class) {
        $stack = new static($name, $ctx, $link_class);
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
