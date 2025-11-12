<?php

namespace App\CustomFunction;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CustomFunction
{
    public static function fileUpload($file, $oldFileName = null, $folderName = null)
    {
        $random_no = time() . rand(1, 100) . Str::random(2);
        $name = $random_no . '.' . $file->getClientOriginalExtension();

        if(File::isDirectory(url('public/uploads').'/'.$folderName)){

        }else{
            File::makeDirectory(url('public/uploads').'/'.$folderName, 0777, true, true);
        }

        if ($file->move('public/uploads/' . $folderName.'/', $name)) {
            if ($oldFileName != null) {
                self::removeFile($oldFileName, $folderName);
            }

            $originalExtension = $file->getClientOriginalExtension();

            $destinationPath = 'public/uploads/' . $folderName.'/';

            return $name;
        }
        return null;
    }


    public static function removeFile($fileName = null, $folderName = null)
    {
        $file ='public/uploads/' . $folderName . '/' . $fileName;

        if (file_exists($file)) {
            unlink($file);
            return true;
        }
        return false;
    }
}
