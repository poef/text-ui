<?php

namespace TextUI;

class Element {

	public $name = '';
	public $parent = null;
	public $children = [];
	public $attributes = [];
	public $label = '';

	public function newChild() {
		$child = new Element();
		$child->parent = $this;
		$this->children[] = $child;
		return $child;
	}

	public function setAttribute($name, $value=null) {
		$this->attributes[$name] = $value;
	}

	public function getAttribute($name) {
		return $this->attributes[$name];
	}
}