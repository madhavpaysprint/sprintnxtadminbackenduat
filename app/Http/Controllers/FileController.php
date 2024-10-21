<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Traits\CommonTrait;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;

class FileController extends Controller
{
    use CommonTrait;

    #Upload file
    public function uploadFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "file" => 'required',
        ]);
        if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
        }

        $filepath = base_path(). '/uploads/'.date('Y-m-d');
        $file = $request->file;
        $extension = $request->file('file')->extension();
        $name = md5(date('Ymd'). '_' .time()).'.'.$extension;

        $image_path = $filepath . $name;

        #check if request has old file then delete it
        if ($request->has('old_file')) {
            if (File::exists($request->old_file)) {
                File::delete($request->old_file);
            }
        }
        // $file->move($filepath, $name);
        $file->storeAs('uploads/'.date('Y-m-d'),$name);

        $fileurl = env('BASEURL'). 'uploads/'.date('Y-m-d').'/'.$name;
        $res = [
            'status' => true,
            'message' => "File uploaded",
            'responsecode' => 1,
            'fileurl' => $fileurl,
        ];
        return response()->json($res);
    }

    public function normal_file_upload(Request $request){
        $validated = Validator::make($request->all(), [
            'file' => 'required|mimes:jpeg,png,jpg,gif|max:2048', 
            'domain_url' => 'required',
        ]);
        if ($validated->fails()) {
            $message = $this->validationResponse($validated->errors());
            return $this->response('validatorerrors', $message);
        } 
        $uploadpath  = $request->domain_url."/".date("Y-m-d")."/uploads";
        $file = $request->file('file');
        $path = $file->store($uploadpath, 's3'); 
        $getimage = self::getuploadImage($path);
        return $this->response('success', ['message' => "File Uploaded Successfully",'path' => $getimage]); 
    } 
    public function display(Request $request){
        $filename = $request->get('filepath'); 
        $url = Storage::disk('s3')->temporaryUrl($filename, '+59 minutes'); 
        return   $url;
    }

    public function getuploadImage($filename){ 
        $url = Storage::disk('s3')->temporaryUrl($filename, '+59 minutes'); 
        return   $url;
    }
}