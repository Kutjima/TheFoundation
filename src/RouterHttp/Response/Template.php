<?php

/**
 * 
 */
namespace TheFoundation\RouterHttp\Response;

/**
 * 
 */
class Template {

    /**
     * 
     */
    private $document = null;
    private $attributes = [];

    /**
     * 
     */
    final public function __construct(string $document, array $attributes = []) {
        $this->document = sprintf('%s/htdocs/%s', APPLICATION_PATH, $document);
        $this->attributes = $attributes;
    }

    /**
    *
    */
    public function &__get(string $name) {
        return $this->attributes[$name];
    }

    /**
    *
    */
    public function __set(string $name, $mix_value) {
        $this->attributes[$name] = $mix_value;
    }

    /**
    *
    */
    public function __toString(): string {
        ob_start();
            extract($this->attributes);

            if (preg_match('/\.phtml$/is', $this->document) && is_file($this->document))
                include $this->document;
            else
                echo eval('?>' . $this->document);
        
        return ob_get_clean();
    }

    /**
     * 
     */
    static function Form(string|array $json): Form {
        return new Form($json);
    }
}

/**
 * 
 */

class Form {

    /**
     * 
     */
    static $values = [];
    protected $properties = [];

    /**
     * 
     */
    public function __construct(string|array $filename) {
        if (is_string($filename))
            $this->properties = jump($filename);
        elseif (is_array($filename))
            $this->properties = $filename;
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
    public static function setValue(string $name, int|string|\Closure $value = null) {
        self::$values[$name] = $value;
    }

    /**
     * 
     */
    public function build(?object $context = null) {
        $html = '<form ' . self::flatten($this->properties['$attributes']) . '>';

        foreach ($this->properties['$elements'] as $name => $prop)
            $html .= static::design($prop['$type'], $name, $prop['$value'], $prop);

        $html .= '</form>';
        return $html;
    }

    /**
     * 
     */
    static public function flatten(array $attributes): string {
        return implode(' ', array_map(function($key, $value) {
            return sprintf('%s="%s"', htmlspecialchars($key), htmlspecialchars($value));
        }, array_keys($attributes), $attributes));
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

    /**
     * 
     */
    static public function design(string $type, string $name, string|array|null $value, array $prop): string {
        if (is_callable($value = static::$values[$name] ?? null))
            $value = $value();

        $html = '<div class="form-group">';

        if ($label = $prop['label'] ?? null)
            $html .= sprintf('<label class="form-label">%s</label>', $label);

        $html .= '<div class="form-input">';
        $attributes = [];

        switch ($type) {
            case 'html':
                break;
            case 'legend':
                break;
            case 'inline':
                $html .= '<div class="row">';

                foreach ($prop['$elements'] as $iname => $iprop) 
                    $html .= sprintf('<div class="col-%d">%s</div>', $iprop['colsize'] ?? 12, static::design($iprop['$type'], $iname, $iprop['$value'], $iprop));
                
                $html .= '</div>';
                break;
            case 'input':
                $attributes['name'] = $name;
                $attributes['value'] = $value;
                $attributes['type'] = 'text';
                $attributes['class'] = 'form-control';
                $attributes = array_replace($attributes, $prop['attributes'] ?? []);

                $html .= sprintf('<input %s />', static::flatten($attributes));
                break;
            case 'textarea':
                $attributes['name'] = $name;
                $attributes['class'] = 'form-control';
                $attributes = array_replace($attributes, $prop['attributes'] ?? []);

                $html .= sprintf('<textarea %s>%s</textarea>', static::flatten($attributes), $value);
                break;
            case 'select':
            case 'selectbox':
                $attributes['name'] = $name;
                $attributes['class'] = 'form-select';
                $attributes = array_replace($attributes, $prop['attributes'] ?? []);

                $html .= sprintf('<select %s>', static::flatten($attributes));

                foreach ((array) $prop['$options'] as $opt_value => $opt_label) {
                    $opt_attributes['value'] = $opt_value;

                    if (in_array($opt_value, (array) $value))
                        $opt_attributes['selected'] = 1;
                    
                    $html .= sprintf('<option %s>%s</label>', static::flatten($opt_attributes), $opt_label);
                }
                
                $html .= '</select>';
                break;
            case 'checkbox':
            case 'n-checkbox':
                foreach ((array) $prop['$options'] as $opt_value => $opt_label) {
                    $attributes['name'] = $name;
                    $attributes['value'] = $opt_value;
                    $attributes['type'] = 'checkbox';
                    $attributes['class'] = 'form-check-input';
                    $attributes = array_replace($attributes, $prop['attributes'] ?? []);

                    if (in_array($opt_value, (array) $value))
                        $attributes['checked'] = 1;

                    $html .= '<div class="form-check">';
                    $html .= '<label class="form-check-label">';
                    $html .= sprintf('<input %s /> %s', static::flatten($attributes), $opt_label);
                    $html .= '</label>';
                    $html .= '</div>';
                }
                break;
            case 'switch':
                $attributes['name'] = $name;
                $attributes['value'] = $value;
                $attributes['type'] = 'checkbox';
                $attributes['class'] = 'form-check-input';
                $attributes = array_replace($attributes, $prop['attributes'] ?? []);

                if ($value)
                    $attributes['checked'] = 1;

                $html .= '<div class="form-check form-switch">';
                $html .= '<label class="form-check-label">';
                $html .= sprintf('<input %s /> %s', static::flatten($attributes), $prop['$optlabel'] ?? null);
                $html .= '</label>';
                $html .= '</div>';
                break;
            case 'upload':
                $uqid = 'i-' . md5($name);
                $attributes['type'] = 'file';
                $attributes['class'] = 'form-control';
                $attributes['onchange'] = sprintf('return (function(event) {
                    if (!event.target.files.length)
                        return $("span#uploadmark-%s").html("[WILL NOT CHANGE]");

                    $("span#uploadmark-%s").html(event.target.files[0].name);
                })(event);', $uqid, $uqid);
                $attributes = array_replace($attributes, $prop['attributes'] ?? []);

                $html .= sprintf('<input %s />', static::flatten($attributes));
                $html .= '<div class="mt-1">';
                $html .= '<small class="help-text">';
                
                if ($value)
                    $html .= sprintf('<a href="%s" target="_blank">%s</a>', $value, basename($value));
                else
                    $html .= '[NO FILE PREVIOUSLY UPLOADED]';
                
                $html .= sprintf(' &rarr; <span id="uploadmark-%s">[WILL NOT CHANGE]</span></small>', $uqid);
                $html .= '</div>';
                break;
            case 'n-upload':
                $uqid = 'i-' . md5($name);
                $attributes['type'] = 'file';
                $attributes['class'] = 'form-control';
                $attributes['multiple'] = 1;
                $attributes['onchange'] = sprintf('return (function(event) {
                    if (!event.target.files.length)
                        return $("span#uploadmark-%s").html("[NO FILE SELECTED]");
                    
                    var l = [];
                    for (var i = 0; i < event.target.files.length; i ++)
                        l.push(event.target.files[i].name);

                    $("span#uploadmark-%s").html(l.join(", "));
                })(event);', $uqid, $uqid);
                $attributes = array_replace($attributes, $prop['attributes'] ?? []);

                $html .= sprintf('<input %s />', static::flatten($attributes));
                $html .= '<div class="mt-1">';
                $html .= '<small class="help-text">';
                $html .= sprintf('<span id="uploadmark-%s">[WILL NOT CHANGE]</span></small>', $uqid);
                $html .= '</div>';
                break;
            case 'upload-photo':
            case 'n-upload-photo':
                break;
            case 'n-choice':
                break;
            default:
                $html .= sprintf('<div class="form-unknown-control">FIELD_TYPE_ERROR: Get "%s" from fieldset "%s"</div>', $type, htmlspecialchars($name));
        }

        if ($helptext = $prop['helptext'] ?? null)
            $html .= sprintf('<small class="form-text text-muted">%s</small>', $helptext);
        
        $html .= '</div>';
        $html .= '</div>';

        return $html . PHP_EOL;
    }
}
?>