<?php

class Image
{
    public function generateTextPng($width, $height, $string, $fileName)
    {
        $image = imagecreate($width, $height);
        imagecolorallocate($image, 255, 255, 255);
        $textColor = imagecolorallocate($image, 233, 14, 91);
        imagestring($image, 5, 2, 2, $string, $textColor);
        return imagepng($image, $fileName);
    }

    public function image2Base64String($file)
    {
        $base64Image = '';
        if (file_exists($file)) {
            $imageInfo = getimagesize($file);
            $imageData = fread(fopen($file, 'r'), filesize($file));
            $base64Image = 'data:' . $imageInfo['mime'] . ';base64,' . chunk_split(base64_encode($imageData));
        }
        return $base64Image;
    }

    public function base64Image2File($base64ImageContent, $fileName, $path)
    {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64ImageContent, $result)){
            $file = $fileName . '.' . $result[2];
            if(!file_exists($path)){
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
                mkdir($path, 0700);
            }
            if (file_put_contents($path . '\\' . $file, base64_decode(str_replace($result[1], '', $base64ImageContent)))){
                return true;
            }
        }
        return false;
    }
}