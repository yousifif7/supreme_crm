<?php

namespace App\Http\Controllers\Docs;

use App\Models\Docs\AdminSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\Controller;

class AdminSettingController extends Controller
{
    public function index()
    {
        return view('docs.admin_setting.index');
    }


    public function overWriteEnvFile($type, $val)
    {

            $path = base_path('.env');
            if (file_exists($path)) {
                $val = '"'.trim($val).'"';
                if(is_numeric(strpos(file_get_contents($path), $type)) && strpos(file_get_contents($path), $type) >= 0){
                    file_put_contents($path, str_replace(
                        $type.'="'.env($type).'"', $type.'='.$val, file_get_contents($path)
                    ));
                }
                else{
                    file_put_contents($path, file_get_contents($path)."\r\n".$type.'='.$val);
                }
            }
    }

    public function create(Request $request)
    {
        foreach ($request->types as $key => $type) {
      
            if ($type == 'website_nameen') {
                $this->overWriteEnvFile('APP_NAME', $request[$type]);
            }
    
            $lang = null;
            if (gettype($type) == 'array') {
                $lang = array_key_first($type);
                $type = $type[$lang];
                $business_settings = AdminSetting::where('type', $type)->where('lang', $lang)->first();
            } else {
                $business_settings = AdminSetting::where('type', $type)->first();
            }
    
            if ($business_settings != null) {
                if (gettype($request[$type . $lang]) == 'array') {
                    $business_settings->value = json_encode($request[$type . $lang]);
                } else {
                    if ($request->hasFile($type . $lang)) {
    
                        // Delete old file if it exists
                        if (!empty($request[$type . '_old' . $lang]) && file_exists(public_path('backend/websitedata/' . $request[$type . '_old' . $lang]))) {
                            unlink(public_path('backend/websitedata/' . $request[$type . '_old' . $lang]));
                        }
    
                        // Handle new file upload
                        $file = $request->file($type . $lang);
                        $destinationPath = public_path('backend/websitedata/');
                        
                        // Remove spaces and replace them with "-"
                        $file_name = time() . '-' . str_replace(' ', '-', $file->getClientOriginalName());
                        
                        $file->move($destinationPath, $file_name);
    
                        // Check if there is an old file and delete it
                        if ($business_settings->value != null) {
                            $oldFile = public_path("backend/websitedata/{$business_settings->value}");
    
                            if (File::exists($oldFile)) {
                                unlink($oldFile);
                            }
                        }
    
                        $business_settings->value = $file_name;
                    } else if ($type != 'header_logo') {
                        $business_settings->value = $request[$type . $lang];
                    }
                }
                $business_settings->lang = $lang;
                $business_settings->save();
            } else {
                $business_settings = new AdminSetting;
                $business_settings->type = $type;
    
                if (gettype($request[$type . $lang]) == 'array') {
                    $business_settings->value = json_encode($request[$type . $lang]);
                } else {
                    if ($request->hasFile($type . $lang)) {
                        $file = $request->file($type . $lang);
                        $destinationPath = public_path('backend/websitedata/');
                        
                        // Remove spaces and replace them with "-"
                        $file_name = time() . '-' . str_replace(' ', '-', $file->getClientOriginalName());
    
                        $file->move($destinationPath, $file_name);
                        $business_settings->value = $file_name;
                    } else {
                        $business_settings->value = $request[$type . $lang];
                    }
                }
                $business_settings->lang = $lang;
                $business_settings->save();
            }
        }
        return back()->with('success', 'Data has been updated successfully');
    }
    


}
