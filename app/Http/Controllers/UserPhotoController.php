<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContainerRequest;
use App\Models\UserPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class UserPhotoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ContainerRequest $request)
    {
        $user = $request->user();
        $file = $request->validated()['file'];
        Storage::makeDirectory('tmp');
        $path = $file->store('tmp');
        $fullPath = Storage::path($path);

        $image = ImageManager::gd()->read($file);
        $image->cover(128, 128, 'center')->save($fullPath);

        $fmFile = new File($fullPath);

        $photo = null;
        if ($user?->image_id) {
            $photo = UserPhoto::where('id', '==', $user->image_id)->first();
        }
        if (!$photo) {
            $photo = UserPhoto::where('user_id', '==', $user->id)->first();
        }
        if (!$photo) {
            $photo = new UserPhoto();
        }
        $photo->user_id = $user->id;

        $photo->photo = $fmFile;
        $photo->save();

        if (!empty($photo->id)) {
            UserPhoto::where('user_id', '==', $user->id)
                ->whereNot('id', $photo->id)
                ->delete();
        }

        $this->deleteUpFile($path);

        return redirect()->back()
            ->with('success', __('Photo uploaded.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function deleteUpFile($path)
    {
        Storage::delete($path);
    }
}
