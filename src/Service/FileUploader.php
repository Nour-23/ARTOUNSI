<?php
// src/Service/FileUploader.php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class FileUploader
{
    private $targetDirectory;

    public function __construct(string $targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
    }

    public function upload(UploadedFile $file)
    {
        $filename = uniqid().'.'.$file->guessExtension();

        try {
            $file->move($this->getTargetDirectory(), $filename);
        } catch (IOExceptionInterface $exception) {
            // Gérer l'erreur d'upload si nécessaire
            throw new \Exception('Erreur lors du téléchargement de la photo.');
        }

        return $filename;
    }

    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }
}
