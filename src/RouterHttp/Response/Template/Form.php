<?php

/**
 * 
 */
namespace TheFoundation\RouterHttp\Response\Template;

/**
 * 
 */
class Form {

    /**
     * 
     */
    protected $values = [];
    protected $elements = [];
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

    /**'
     * 
     */
    public function add(Form\Element $element): self {
        if (!$element instanceof Form\Element)
            throw new \InvalidArgumentException('Value must be an instance of "Form\Element"');
        
        $name = random_bytes(20);

        if ($element instanceof Form\Payload)
            $this->values[$name = $element->getName()] = $element->getValue();
        else if ($element instanceof Form\Fieldset || $element instanceof Form\Inline) {            
            foreach ($element->children() as $child_element)
                if ($child_element instanceof Form\Payload)
                    $this->values[$name = $child_element->getName()] = $child_element->getValue();
        }
        
        $this->elements[$name] = $element;

        return $this;
    }

    /**
     * 
     */
    public function setValue(string $name, null|int|string|array|\Closure $value = null) {
        $this->values[$name] = $value;
    }

    /**
     * 
     */
    public function build() {
        $html = '<form ' . Form\Element::flatten($this->attributes) . '>';

        foreach ($this->elements as $name => $element)
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
class Listitem extends Payload {
    
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
class Image extends Input {

    /**
     * 
     */
    public function build(): string {
        $uqid = 'i-' . md5($this->name);
        $no_pic = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $value = $this->value;
        $attributes = $this->attributes;
        $attributes['name'] = $this->name;
        $attributes['value'] = $value;
        $attributes['type'] = 'file';
        $attributes['class'] = 'form-control';
        $attributes['accept'] = 'image/*';
        $attributes['multiple'] = null;
        $attributes['onchange'] = 'return (function(event) {
            const [file] = event.target.files;
            const $input = $("img#uploadmark-' . $uqid . '");

            if (file) {
                $input.attr("src", window.URL.createObjectURL(file));
                $input.on("onload", function() {
                    window.URL.revokeObjectURL($input.attr("src"));
                });

                return true;
            }

            return $input.attr("src", "' . $no_pic . '");
        })(event);';
        
        $html = '<div class="form-group">';

        if (!empty($this->label))
            $html .= '<label class="form-label">' . $this->label . '</label>';

        $html .= '<div class="mb-3">';
        $html .= '<img id="uploadmark-' . $uqid . '" src="' . ($value ?: $no_pic) . '" class="img-fluid border border-2 p-1" />';
        $html .= '</div>';
        $html .= '<input ' . self::flatten($attributes) . ' />';

        if (!empty($this->helptext))
            $html .= '<span class="form-text">' . $this->helptext . '</span>';

        $html .= '</div>';

        return $html;
    }
}


/**
 * 
 */
class Video extends Input {
    
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
        $attributes['accept'] = 'video/*';
        $attributes['multiple'] = null;
        $attributes['onchange'] = 'return (function(event) {
            const [file] = event.target.files;
            const $input = $("video#uploadmark-' . $uqid . '");

            if (file) {
                $input.attr("src", window.URL.createObjectURL(file));
                $input.on("onload", function() {
                    window.URL.revokeObjectURL($input.attr("src"));
                });

                return true;
            }

            return $input.attr("src", "[NO FILE SELECTED]");
        })(event);';
        
        $html = '<div class="form-group">';

        if (!empty($this->label))
            $html .= '<label class="form-label">' . $this->label . '</label>';

        $html .= '<div class="mb-3">';
        $html .= '<video id="uploadmark-' . $uqid . '" src="' . ($value ?: '#') . '" class="img-fluid border border-2 p-1" controls="controls">';
        $html .= 'Your browser does not support the video tag.';
        $html .= '</video>';
        $html .= '</div>';
        $html .= '<input ' . self::flatten($attributes) . ' />';

        if (!empty($this->helptext))
            $html .= '<span class="form-text">' . $this->helptext . '</span>';

        $html .= '</div>';

        return $html;
    }
};


/**
 * 
 */
class Audio extends Input {};


/**
 * 
 */
class Youtube extends Input {};


/**
 * 
 */
class GoogleMaps extends Input {};


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
class Button extends Element {

    /**
     * 
     */
    protected string $text = '';
    protected array $attributes = [
        'type' => 'submit',
        'class' => 'btn btn-primary w-100',
    ];

    /**
     * 
     */
    public function __construct(string $text = 'Submit', string $label = '', string $helptext = '', array $attributes = []) {
        $this->text = $text;
        $this->label = $label;
        $this->helptext = $helptext;
        $this->attributes = array_replace($this->attributes, $attributes);
    }

    /**
     * 
     */
    public function build(): string {
        $attributes = $this->attributes;

        $html = '<div class="form-group">';

        if (!empty($this->label))
            $html .= '<label class="form-label">' . $this->label . '</label>';
        
        $html .= '<div>';
        $html .= '<button ' . self::flatten($attributes) . '>';
        $html .= $this->text;
        $html .= '</button>';
        $html .= '</div>';

        if (!empty($this->helptext))
            $html .= '<span class="form-text">' . $this->helptext . '</span>';

        $html .= '</div>';

        return $html;
    }
};
?>