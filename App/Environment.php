<?php

namespace Tale\App;

use Tale\ConfigurableTrait;

class Environment
{
    use ConfigurableTrait;

    const DEFAULT_NAME = 'env';

    public function __construct(array $options = null)
    {

        $this->defineOptions([
            'path' => getcwd(),
            'environment' => self::DEFAULT_NAME,
            'paths' => []
        ], $options);

        $path = $this->options['path'];

        if (is_dir($path))
            $path = rtrim($path, '/\\').'/'.$this->options['environment'];

        $this->loadOptions($path, true);
    }
}