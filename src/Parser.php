<?php

namespace TextUI;

class Parser {

	public static function tokenize($input) {
		$token = <<<'REGEX'
		/
		(?<indent>		^[\h]+			)
		|
		(?<typedValue>	[\w]+[\:]+[\w]+	)
		|
		(?<value>		[\w]+			)
		|
		(?<quotedValue>	"[^"]*"			)
		|  
		(?<id>			\#[\w]+			)
		|
		(?<class>		\.[\w]+			)
		|
		(?<assign>		\=				)
		/mxi
		REGEX;
		$offset = 0;
		do {
	        $result = preg_match($token, $input, $matches, PREG_OFFSET_CAPTURE, $offset);
	        if ($result) {
	        	$keys = array_filter(array_keys($matches), function($key) use ($matches) {
	        		return !is_numeric($key) && $matches[$key][0];
	        	});
	        	$type   = current($keys);
	            $value  = $matches[$type][0];
	            $offset = $matches[$type][1];
	            $matchedToken = [
	            	'type'   => $type,
	            	'value'  => $value,
	            	'offset' => $offset
	            ];
	            +$offset += strlen($value);
	            yield $matchedToken;
	        } else if ( strlen(trim(substr($input, $offset))) ) {
	        	throw new LogicException("Unknown token at\n".substr($input, $offset)."\n");
	        }
	    } while($result);
	}

	public static parse($input) {
		$indent     = [];
		$tree       = [];
		$offset     = 0;
		$expect     = 'indent|value';
		$valueType  = 'element';
		$root       = new TextUI\Element();
		$lastIndent = '';

		$element    = $root;
		foreach ( call_user_func(self::tokenize, $input) as $token) {
			if (!preg_match("/^$expect$/", $token['type'])) {
				throw new \LogicException(
					'Parse error at '.$token['offset']
					.': expected '.$expect
					.', got '.$token['type']
					.': '.substr($input, 0, $token['offset'])
					.' --> '.substr($input, $token['offset']));
			}
			switch($token['type']) {
				case 'indent':
					$lastIndent = $token['value'];
					$expect     = 'value|indent';
					$valueType  = 'element';
				break;
				case 'quotedValue':
					$token['value'] = substr($token['value'], 0, -1); // remove quotes
					// FALLTHROUGH
				case 'value':
				case 'typedValue':
					switch($valueType) {
						case 'element':
							while(strlen($lastIndent)<strlen(last($indent))) {
								// find parent
								$element = $element->parent;
								array_pop($indent);
							}
							if (strlen($lastIndent)==strlen(last($indent))) {
								// sibling
								$element = $element->parent->newChild();
							} else {
								// child
								$element  = $element->newChild();
								$indent[] = $lastIndent;
							}
							$element->name = $token['value'];
							$expect = 'id|class|value|typeValue|quotedValue|indent';
						break;
						case 'attribute':
							if ($token['type'] == 'quotedValue') {
								$element->label = $token['value'];
								$expect = 'id|class|value|typeValue|quotedValue|indent';
							} else {
								$element->setAttribute($token['value']);
								$lastAttributeName = $token['value'];
								$expect = 'assign|id|class|value|typeValue|quotedValue|indent';
							}
						break;
						case 'value':
							$element->setAttribute($lastAttributeName, $token['value']);
							$expect = 'id|class|value|typeValue|quotedValue|indent';
						break;
					}
					$valueType = "attribute";
				break;
				case 'id':
					if ($element->getAttribute('id')) {
						throw new \LogicException('ID is set twice');
					}
					$element->setAttribute('id', substr($token['value'],1));
					$expect = 'class|value|typedValue|quotedValue|indent';
					$valueType = "attribute";
				break;
				case 'class':
					$element->setAttribute('class', $element->attributes->get('class')
						.' '.substr($token['value'],1));
					$expect = 'id|class|value|typedValue|quotedValue|indent';
					$valueType = "attribute";
				break;
				case 'assign':
					$expect = 'value|quotedValue|typedValue';
					$valueType = 'value';
				break;
			}
		}
		return $root;
	}

}