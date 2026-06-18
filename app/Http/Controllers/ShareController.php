<?php

namespace App\Http\Controllers;

use App\Libs\FileService;
use App\Models\FileInfo;
use App\Models\FileInfoPermission;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ShareController extends Controller
{
    /**
     * return file or download from private storage
     *
     * @return mixed|BinaryFileResponse
     */
    public function shareFile(string $hash)
    {
        $decoded = explode('|||', base64_decode($hash));

        if (Hash::check(Config::get('APP_KEY'), $decoded[0])) {
            // The hash match...
            $parameters = explode('|', $decoded[1]);

            $filePath = $parameters[0];
            $fileType = $parameters[1];
            $userId = $parameters[2];

            // get fileInfo
            $file = FileInfo::where('path', $filePath)->where('type', $fileType)->first();

            if ($file) {
                // generate array for url
                $pathArray = FileService::generatePrivateArray(collect($file->toArray()));
                $team = $pathArray['team'];
                $folder = $pathArray['folders'];
                $filename = $pathArray['filename'];
                $disk = $pathArray['disk'];
                $mime = 'download';

                $root = config('filesystems.disks.'.$disk.'.root');
                $folder = $folder ? Str::replace('@', '/', $folder).'/' : '';

                $path = $team.'/'.$folder.$filename;

                // get fileInfoPermissions
                $userSecondLevelpermissions = $file->fileInfoPermissions->where('user_id', $userId)->first();

                // check if public or private link
                if ($userSecondLevelpermissions && $userSecondLevelpermissions->share_is_public) {
                    // public share link
                    return $this->generateResponse($mime, $root, $path, $userSecondLevelpermissions, true);
                } else {
                    // check user logged
                    if (auth()->check() && (auth()->user()->getKey() == $userId || auth()->user()->hasRole(['super_admin']))) {
                        // download file
                        return $this->generateResponse($mime, $root, $path, $userSecondLevelpermissions, false);
                    } else {
                        Notification::make()
                            ->title(__('Share link not Authorized'))
                            ->danger()
                            ->send();

                        return redirect('admin/login');
                    }
                }
            } else {
                // link invalid
                return view('vendor.filament-browser.file-link-invalid');
            }
        }

        return view('vendor.filament-browser.file-link-invalid');
    }

    private function generateResponse(string $mime, string $root, string $path, FileInfoPermission $userSecondLevelpermissions, bool $isPublic)
    {
        // check for file expired
        if (FileService::isFileInfoExpired($userSecondLevelpermissions)) {
            if ($isPublic) {
                return view('vendor.filament-browser.file-public-expired', ['expiryDate' => $userSecondLevelpermissions->expiry_date]);
            } else {
                Notification::make()
                    ->title(__('Share link Expired'))
                    ->body(__('This link is expired in data: ').$userSecondLevelpermissions->expiry_date)
                    ->danger()
                    ->send();

                return redirect('admin/login');
            }
        }

        try {
            if ($mime !== 'download') {
                return response()->file($root.$path);
            } else {
                return response()->download($root.$path);
            }
        } catch (\Throwable $th) {
            return '#!';
        }
    }
}
