<?php

namespace App\Http\Controllers\Docs;

use App\Models\Docs\AdminSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminSettingController extends Controller
{
    /** Allowed logo / favicon extensions (includes SVG for crisp branding). */
    private const LOGO_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    public function index()
    {
        return view('docs.admin_setting.index');
    }

    public function overWriteEnvFile($type, $val)
    {
        $path = base_path('.env');
        if (!file_exists($path)) {
            return;
        }

        $val = '"' . str_replace('"', '\\"', trim((string) $val)) . '"';
        $contents = file_get_contents($path);

        if (preg_match('/^' . preg_quote($type, '/') . '=.*/m', $contents)) {
            $contents = preg_replace(
                '/^' . preg_quote($type, '/') . '=.*/m',
                $type . '=' . $val,
                $contents
            );
        } else {
            $contents = rtrim($contents) . "\n" . $type . '=' . $val . "\n";
        }

        file_put_contents($path, $contents);
    }

    public function create(Request $request)
    {
        $logoFields = ['login_logo', 'dashboard_logo', 'avatar', 'favicon_logo'];

        $rules = [
            'website_name' => 'nullable|string|max:120',
        ];
        foreach ($logoFields as $field) {
            $rules[$field] = 'nullable|file|max:4096';
        }

        $validated = $request->validate($rules);

        // Extra extension check — Laravel mimes is unreliable for SVG (text/xml / image/svg+xml).
        foreach ($logoFields as $field) {
            if ($request->hasFile($field)) {
                $ext = strtolower($request->file($field)->getClientOriginalExtension());
                if (!in_array($ext, self::LOGO_EXTENSIONS, true)) {
                    throw ValidationException::withMessages([
                        $field => 'The ' . str_replace('_', ' ', $field) . ' must be a JPG, PNG, GIF, WEBP, or SVG file.',
                    ]);
                }
            }
        }

        $destinationPath = public_path('backend/websitedata/');
        if (!File::isDirectory($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }

        // Brand display name → settings + .env (FieldLine white-label)
        if ($request->filled('website_name')) {
            $name = trim($request->input('website_name'));
            $this->upsertSetting('website_name', $name);
            $this->overWriteEnvFile('APP_NAME', $name);
            $this->overWriteEnvFile('BRAND_NAME', $name);
            $this->overWriteEnvFile('BRAND_COMPANY', $name);
        }

        $types = $request->input('types', $logoFields);

        foreach ($types as $type) {
            if (!is_string($type) || $type === 'website_name') {
                continue;
            }

            $business_settings = AdminSetting::where('type', $type)->first();

            if ($business_settings != null) {
                if ($request->hasFile($type)) {
                    $this->storeLogoFile($request, $type, $business_settings, $destinationPath);
                }
                $business_settings->save();
            } else {
                $business_settings = new AdminSetting;
                $business_settings->type = $type;

                if ($request->hasFile($type)) {
                    $this->storeLogoFile($request, $type, $business_settings, $destinationPath);
                } elseif ($request->filled($type)) {
                    $business_settings->value = $request->input($type);
                }

                $business_settings->save();
            }
        }

        try {
            \Illuminate\Support\Facades\Cache::forget('AdminSetting');
            Artisan::call('config:clear');
        } catch (\Throwable $e) {
            // ignore on restricted hosts
        }

        return back()->with('success', 'Settings have been updated successfully.');
    }

    private function upsertSetting(string $type, string $value): void
    {
        $row = AdminSetting::where('type', $type)->first() ?? new AdminSetting(['type' => $type]);
        $row->type = $type;
        $row->value = $value;
        $row->save();
    }

    private function storeLogoFile(Request $request, string $type, AdminSetting $setting, string $destinationPath): void
    {
        $file = $request->file($type);
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeBase = Str::slug($base) ?: 'logo';
        $file_name = time() . '-' . $safeBase . '.' . $ext;

        // Delete previous file on disk
        if (!empty($setting->value)) {
            $oldFile = public_path('backend/websitedata/' . $setting->value);
            if (File::exists($oldFile)) {
                File::delete($oldFile);
            }
        }

        $file->move($destinationPath, $file_name);
        $setting->value = $file_name;
    }
}
