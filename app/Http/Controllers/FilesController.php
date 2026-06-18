<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FilesController extends Controller
{
    /**
     * return file or download from private storage
     *
     * @return mixed|BinaryFileResponse
     */
    public function getFile(?string $team = null, ?string $folder = null, ?string $filename = null, ?string $mime = null, ?string $disk = null)
    {
        $root = config('filesystems.disks.'.$disk.'.root');
        $folder = $folder ? Str::replace('@', '/', $folder).'/' : '';

        $path = $team.'/'.$folder.$filename;

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
