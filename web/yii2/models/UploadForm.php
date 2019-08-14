<?php

namespace app\models;

use yii\base\Model;
use yii\web\UploadedFile;

class UploadForm extends Model
{
    /**
     * @var UploadedFile[]
     */
    public $imageFiles;

    public function rules()
    {
        return [
            [['imageFiles'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png, jpg', 'maxFiles' => 0],
        ];
    }

    public function upload() : array
    {
        if ($this->validate()) {
            $filePaths = [];
            foreach ($this->imageFiles as $file) {
                $filePath = 'uploads/' . uniqid() . '.' . $file->extension;
                $isSuccess = $file->saveAs($filePath);
                if (!$isSuccess) {
                    throw new \RuntimeException('Can not save files');
                }
                $filePaths[] = '/' . $filePath;
            }
            return $filePaths;
        } else {
            return array();
        }
    }
}