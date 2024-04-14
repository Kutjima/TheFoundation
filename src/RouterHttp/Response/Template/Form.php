<?php

/**
 * 
 */
namespace TheFoundation\RouterHttp\Response\Template;

/**
 * 
 */
class Form extends \ArrayObject {

    /**
     * 
     */
    protected $values = [];
    protected $attributes = [];

    /**
     * 
     */
    public function __construct(string $method = 'GET', string $action = '', array $attributes = []) {
        $this->attributes = array_replace($attributes, [
            'method' => $method,
            'action' => $action,
        ]);
    }

    /**
     * 
     */
    public function __tostring(): string {
        return $this->build();
    }

    /**
     * 
     */
    public function offsetSet(mixed $key, mixed $value) {
        if (!$value instanceof Form\Element)
            throw new \InvalidArgumentException('Value must be an instance of "Form\Element"');

        if ($value instanceof Form\PayloadElement)
            $this->values[$value->getName()] = $value->getValue();

        if ($value instanceof Form\Fieldset || $value instanceof Form\Inline) {
            foreach ($value->children() as $element)
                if ($element instanceof Form\PayloadElement)
                    $this->values[$element->getName()] = $element->getValue();
        }
        
        return parent::offsetSet($key, $value);
    }

    /**'
     * 
     */
    public function add(Form\Element $value): void {
        $this[] = $value;
    }

    /**
     * 
     */
    public function setValue(string $name, null|int|string|array|\Closure $value = null) {
        self::$values[$name] = $value;
    }

    /**
     * 
     */
    public function build() {
        $html = '<form ' . Form\Element::flatten($this->attributes) . '>';

        foreach ($this as $element)
            $html .= $element->build();

        $html .= '</form>';

        return $html;
    }

    /**
     * 
     */
    public static function sanitizeDataPosted(array $data): array {
        foreach($data as $k => $v) {
            if (is_array($data[$k]))
                $data[$k] = self::sanitizeDataPosted($data[$k]);
            else if (is_string($data[$k]) && (($data[$k] = trim($data[$k]))) == '')
                $data[$k] = null;
    
            if (is_numeric($data[$k]) || is_int($data[$k]))
                $data[$k] = (int) $data[$k];
        }
    
        return $data;
    }
}


/**
 * 
 */
namespace TheFoundation\RouterHttp\Response\Template\Form;


/**
 * 
 */
abstract class Element {

    /**
     * 
     */
    protected string $label;
    protected string $helptext;
    protected array $attributes = [];

    /**
     * 
     */
    public function __construct(string $label = '', string $helptext = '', array $attributes = []) {
        $this->label = $label;
        $this->helptext = $helptext;
        $this->attributes = array_replace($this->attributes, $attributes);
    }

    /**
     * 
     */
    public function __toString(): string {
        return $this->build();
    }

    /**
     * 
     */
    static public function flatten(array $attributes): string {
        return implode(' ', array_map(function($key, $value) {
            if (!is_null($value))
                return sprintf('%s="%s"', htmlspecialchars($key), htmlspecialchars($value));

            return null;
        }, array_keys($attributes), $attributes));
    }

    /**
     * 
     */
    public function build(): string {
        throw new \Exception('Not implemented');
    }
};


/**
 * 
 */
class Blank extends Element {

    /**
     * 
     */
    protected string|Element $html = '';

    /**
     * 
     */
    public function __construct(string|Element $html = '', array $attributes = []) {
        $this->attributes = array_replace($attributes, $this->attributes);
    }

    /**
     * 
     */
    public function build(): string {
        return '<div ' . self::flatten($this->attributes) . '>' . $this->html . '</div>';
    }
};

/**
 * 
 */
class Text extends Element {

    /**
     * 
     */
    protected string $text = '';

    /**
     * 
     */
    public function __construct(string $text, array $attributes = []) {
        $this->text = $text;
        $this->attributes = array_replace($this->attributes, $attributes);
    }

    /**
     * 
     */
    public function build(): string {
        $this->attributes['class'] = 'form-text';

        return '<span ' . self::flatten($this->attributes) . '>' . $this->text . '</span>';
    }
};


/**
 * 
 */
class Fieldset extends Element {

    /**
     * 
     */
    protected string $legend = '';
    protected string $description = '';
    protected array $elements = [];

    /**
     * 
     */
    public function __construct(string $legend = '', string $description = '', array $attributes = []) {
        $this->legend = $legend;
        $this->description = $description;
        $this->attributes = $attributes;
    }

    /**
     * 
     */
    public function add(Element $element) {
        $this->elements[] = $element;
    }

    /**
     * 
     */
    public function children(): array {
        return $this->elements;
    }

    /**
     * 
     */
    public function build(): string {
        $html = '<fieldset ' . self::flatten($this->attributes) . '>';

        if (!empty($this->legend))
            $html .= '<legend>' . $this->legend . '</legend>';

        if (!empty($this->description))
            $html .= '<p class="text-muted">' . $this->description . '</p>';
        
        foreach ($this->elements as $element)
            $html .= $element->build();

        $html .= '</fieldset>';

        return $html;
    }
};


/**
 * 
 */
class Inline extends Element {

    /**
     * 
     */
    protected array $elements = [];

    /**
     * 
     */
    public function __construct(string $label = '', string $helptext = '', array $attributes = []) {
        $this->label = $label;
        $this->helptext = $helptext;
        $this->attributes = $attributes;
    }

    /**
     * 
     */
    public function add(Element $element, string $colsize = 'col-auto') {
        $this->elements[] = [$element, $colsize];
    }

    /**
     * 
     */
    public function children(): array {
        return array_map(fn($e) => $e[0], $this->elements);
    }

    /**
     * 
     */
    public function build(): string {
        $html = '<div class="form-group">';

        if (!empty($this->label))
            $html .= '<label class="form-label">' . $this->label . '</label>';

        if (!empty($this->helptext))
            $html .= '<p class="text-muted">' . $this->helptext . '</p>';

        $html .= '<div class="row align-items-center">';
        
        foreach ($this->elements as $element)
            $html .= '<div class="' . $element[1] . '">' . $element[0]->build() . '</div>';

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
};


/**
 * 
 */
abstract class Payload extends Element {

    /**
     * 
     */
    protected string $name;
    protected null|int|string|array|\Closure $value = null;
    protected array $options = [];

    /**
     * 
     */
    public function __construct(string $name, null|int|string|array|\Closure $value = null, string $label = '', string $helptext = '', array $options = [], array $attributes = []) {
        $this->name = $name;
        $this->value = $value;
        $this->options = $options;
        $this->label = $label;
        $this->helptext = $helptext;
        $this->attributes = array_replace($this->attributes, $attributes);
    }

    /**
     * 
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * 
     */
    public function getValue(): null|int|string|array|\Closure {
        return $this->value;
    }

    /**
     * 
     */
    public function build(): string {
        $value = $this->value;
        $attributes = $this->attributes;
        $attributes['name'] = $this->name;
        $attributes['value'] = $value;
        $attributes['class'] = 'form-control';

        $html = '<div class="form-group">';

        if (!empty($this->label))
            $html .= '<label class="form-label">' . $this->label . '</label>';

        $html .= '<input ' . self::flatten($attributes) . '/>';

        if (!empty($this->helptext))
            $html .= '<span class="form-text">' . $this->helptext . '</span>';

        $html .= '</div>';

        return $html;
    }
}


/**
 * 
 */
class Input extends Payload {

    /**
     * 
     */
    protected array $attributes = ['type' => 'text'];
};


/**
 * 
 */
class Radio extends Input {}


/**
 * 
 */
class Checkbox extends Input {

    /**
     * 
     */
    protected bool $is_switch = false;

    /**
     * 
     */
    public function build(): string {
        $value = $this->value;
        $attributes = $this->attributes;
        $attributes['type'] = 'checkbox';
        $attributes['name'] = $this->name;
        $attributes['class'] = 'form-check-input';
        
        $html = '<div class="form-group">';

        if (!empty($this->label))
            $html .= '<label class="form-label">' . $this->label . '</label>';

        foreach ($this->options as $opt_value => $opt_label) {
            $attributes['value'] = $opt_value;

            if (in_array($opt_value, (array) $value))
                $attributes['checked'] = 1;

            $html .= '<div class="form-check ' . ($this->is_switch ? 'form-switch' : null) . '">';
            $html .= '<label class="form-check-label">';
            $html .= '<input ' . self::flatten($attributes) . ' /> ' . $opt_label;
            $html .= '</label>';
            $html .= '</div>';
        }

        if (!empty($this->helptext))
            $html .= '<span class="form-text">' . $this->helptext . '</span>';

        $html .= '</div>';

        return $html;
    }
};


/**
 * 
 */
class Switchbox extends Checkbox {

    /**
     * 
     */
    protected bool $is_switch = true;
};


/**
 * 
 */
class File extends Input {

    /**
     * 
     */
    public function build(): string {
        $uqid = 'i-' . md5($this->name);
        $value = $this->value;
        $attributes = $this->attributes;
        $attributes['name'] = $this->name;
        $attributes['value'] = $value;
        $attributes['type'] = 'file';
        $attributes['class'] = 'form-control';
        $attributes['onchange'] = 'return (function(event) {
            if (!event.target.files.length)
                return $("span#uploadmark-' . $uqid . '").html("[NO FILE SELECTED]");
            
            var l = [];
            for (var i = 0; i < event.target.files.length; i ++)
                l.push(event.target.files[i].name);

            $("span#uploadmark-' . $uqid . '").html(l.join(", "));
        })(event);';
        
        $html = '<div class="form-group">';

        if (!empty($this->label))
            $html .= '<label class="form-label">' . $this->label . '</label>';

        $html .= '<input ' . self::flatten($attributes) . ' />';
        $html .= '<div class="mt-1">';
        $html .= '<small class="help-text">';
        
        if ($value)
            $html .= '<a href="' . $value . '" target="_blank">' . basename($value) . '</a>';
        else
            $html .= '[NO FILE PREVIOUSLY UPLOADED]';
        
        $html .= ' &rarr; <span id="uploadmark-' . $uqid . '">[WILL NOT CHANGE]</span></small>';
        $html .= '</div>';

        if (!empty($this->helptext))
            $html .= '<span class="form-text">' . $this->helptext . '</span>';

        $html .= '</div>';

        return $html;
    }
};


/**
 * 
 */
class FilePreview extends Input {};


/**
 * 
 */
class Textarea extends Payload {
    
    /**
     * 
     */
    public function build(): string {
        $attributes = $this->attributes;
        $attributes['name'] = $this->name;
        $attributes['class'] = 'form-control';

        $html = '<div class="form-group">';

        if (!empty($this->label))
            $html .= '<label class="form-label">' . $this->label . '</label>';
        
        $html .= '<textarea ' . self::flatten($attributes) . '>' . $this->value . '</textarea>';

        if (!empty($this->helptext))
            $html .= '<span class="form-text">' . $this->helptext . '</span>';

        $html .= '</div>';

        return $html;
    }
};


/**
 * 
 */
class Selectbox extends Payload {

    /**
     * 
     */
    public function build(): string {
        $value = $this->value;
        $attributes = $this->attributes;
        $attributes['name'] = $this->name;
        $attributes['class'] = 'form-select';

        $html = '<div class="form-group">';

        if (!empty($this->label))
            $html .= '<label class="form-label">' . $this->label . '</label>';
        
        $html .= '<select ' . self::flatten($attributes) . '>';
        
        foreach ($this->options as $opt_value => $opt_label) {
            $opt_attributes['value'] = $opt_value;

            if (in_array($opt_value, (array) $value))
                $opt_attributes['selected'] = 1;
            
            $html .= '<option ' . self::flatten($opt_attributes) . ' >' . $opt_label . '</label>';
        }

        $html .= '</select>';

        if (!empty($this->helptext))
            $html .= '<span class="form-text">' . $this->helptext . '</span>';

        $html .= '</div>';

        return $html;
    }
};

/**
 * 
 */
class Button extends Element {};
?>