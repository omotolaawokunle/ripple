<?php

namespace Ripple;

class FileSystem
{
    protected $file;
    private $max_filesize, $dirName, $accepted, $extension, $filesize, $source, $filename, $error = false, $errorMessage;
    public $filepath;

    public function __construct($file, $dirName = null, $accepted = [])
    {
        $this->file = $file;
        if ($file and $this->file['size'] != 0) {
            if (!in_array('uploads', explode('/', $dirName))) {
                $this->dirName = 'uploads/' . $dirName;
            } else {
                $this->dirName = $dirName;
            }
            $name = pathinfo($this->file['name']);
            $this->filename = takeRandom($this->file['name']);
            $this->filesize = $this->file['size'];
            $this->source = $this->file['tmp_name'];
            if (isset($name['extension'])) $this->extension = strtolower($name['extension']);
            $this->filepath = $this->dirName . '/' . $this->filename . '.' . $this->extension;
            $this->max_filesize = 10000000;
            $this->validateFile();
        }
    }
    /**
     * 
     * @param bool $extension default: true Return with extension
     * 
     * @return string
     */
    public function getFileName($extension = true)
    {
        if ($extension) {
            return $this->filename . '.' . $this->extension;
        } else {
            return $this->filename;
        }
    }

    public function upload()
    {
        if ($this->error) {
            return false;
        }
        if (move_uploaded_file($this->source, $this->filepath)) {
            return $this;
        } else {
            $this->error = true;
            $this->errorMessage = 'upload-error';
            return false;
        }
    }

    private function validateFile()
    {
        if ($this->filesize > $this->max_filesize) {
            $this->error = true;
            $this->errorMessage = 'upload-file-size-error';
        }

        if (!empty($this->accepted)) {
            if (!in_array($this->extension, $this->accepted)) {
                $this->error = true;
                $this->errorMessage = 'file-extension-error';
            }
        }
    }

    public function getMessage()
    {
        return $this->errorMessage;
    }

    public function setMaxFileSize(int $size)
    {
        $this->max_filesize = $size;
    }
}
