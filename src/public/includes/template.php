<?php

class Template 
{
    protected $filename;
    protected $data;

    public function __construct($filename)
    {
        if (is_file($filename))
            $this->filename = $filename;
        else
            throw new Exception("File not found");
        $this->data = array();
     }
    
    public function __get($name)
    {
        if (array_key_exists($name, $this->data))
        {
            return $this->data[$name];
        }
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __toString()
    {
        ob_start();
        $cwd = getcwd();
        chdir(dirname($this->filename));
        include basename($this->filename);
        chdir($cwd);
        return ob_get_clean();
    }
}