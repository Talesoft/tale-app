<?php

namespace Tale\App;

use Tale\ConfigurableTrait;

class Environment
{
    use ConfigurableTrait;

    const DEFAULT_FILE_NAME = 'env.json';

    public function __construct(array $options = null)
    {

        $this->defineOptions([
            'path' => getcwd(),
            'environmentFileName' => self::DEFAULT_FILE_NAME,
            'paths' => []
        ], $options);

        $path = $this->getOption('path');

        if (is_dir($path))
            $path = rtrim($path, '/\\').'/'.$this->getOption('environmentFileName');

        if (file_exists($path))
            $this->loadOptions($path);
    }
}