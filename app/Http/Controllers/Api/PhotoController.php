<?php

namespace App\Http\Controllers\Api;

use App\Models\Photo;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Intervention\Image\ImageManager;
use App\Http\Resources\PhotoResource;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
// use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\Drivers\Gd\Driver;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PhotoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $photo = Photo::all(['id', 'user_id', 'photo_path']);

        return new PhotoResource(true, 'List data Photo', $photo);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'unique:photos,user_id'],
            'photo_file' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120']
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $file = $request->file('photo_file');
        if ($file) {
            $imageName = $this->savePhoto($file);
            $request['photo_path'] = Storage::disk('public')->url('photos/' . $imageName);
        } else {
            return response()->json(['message' => 'foto gagal diupload']);
        }

        $photo = Photo::create([
            'user_id' => $request->user_id,
            'photo_path' => $request->photo_path
        ]);

        return new PhotoResource(true, "Foto berhasil ditambahkan", $photo);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {

        $photo = Photo::where('user_id', $id)->first(['id', 'user_id', 'photo_path']);
        if ($photo) return new PhotoResource(false, "Data tidak ditemukan", null);

        $photo_path = $photo->photo_path;

        return new PhotoResource(true, "Data anda", $photo_path);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $validator = Validator::make($request->all(), [
            'user_id' => [Rule::unique(Photo::class)->ignore($id, 'user_id')],
            'photo_file' => ['image', 'mimes:jpg,jpeg,png', 'max:5120']
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $photo = Photo::where('user_id', $id)->get()[0];
        if (!$photo) return new PhotoResource(false, "Data tidak ditemukan", null);

        $data = $request->all();
        $file = $request->file('photo_file');
        if ($file) {
            $this->deletePhoto($photo);
            $imageName = $this->savePhoto($file);
            $data['photo_path'] = Storage::disk('public')->url('photos/' . $imageName);
        } else {
            $data['photo_path'] = $photo->photo_path;
        }

        $photo->update($data);

        return new PhotoResource(true, 'Sukses mengubah foto profil', $photo);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $photo = Photo::where('user_id', $id)->get()[0];

        if ($photo) {
            $this->deletePhoto($photo);
            $photo->delete();
        }
        return response()->json([
            'message' => 'Sukses menghapus'
        ]);
    }

    public function storePhoto(Request $request, string $phone)
    {
        $validator = Validator::make($request->all(), [
            'photo_file' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048']
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $photo = Photo::where('user_id', $phone)->first();
        if (!$photo) { //jika foto sebelumnya tidak terdaftar
            $file = $request->file('photo_file');
            if ($file) {
                $imageName = $this->savePhoto($file);
                $request['photo_path'] = Storage::disk('public')->url('photos/' . $imageName);
            } else {
                return response()->json(['message' => 'foto gagal diupload']);
            }

            $photo = Photo::create([
                'user_id' => $phone,
                'photo_path' => $request->photo_path
            ]);

            return new PhotoResource(true, 'Sukses menambahkan foto profil', $photo);
        } else { //jika foto sebelumnya terdaftar namun ingin diubah fotonya
            $data = $request->all();
            $file = $request->file('photo_file');
            if ($file) {
                $this->deletePhoto($photo);
                $imageName = $this->savePhoto($file);
                $data['photo_path'] = Storage::disk('public')->url('photos/' . $imageName);
            } else {
                $data['photo_path'] = $photo->photo_path;
            }

            $photo->update($data);

            return new PhotoResource(true, 'Sukses mengubah foto profil', $photo);
        }
    }

    private function deletePhoto(Photo $photo)
    {
        $photo_path = $photo->photo_path;
        $parts = explode("/", $photo_path);
        // Ambil bagian yang dibutuhkan (indeks array mungkin berbeda tergantung struktur URL)
        $photo_path = $parts[4] . "/" . $parts[5]; // Sesuaikan indeksnya

        if (Storage::disk('public')->exists((string) $photo_path)) {
            Storage::disk('public')->delete((string) $photo_path);
        }
    }

    private function savePhoto(UploadedFile $file)
    {
        $imageName = Str::random(5) . $file->getClientOriginalName();
        $imagePath = public_path() . '/storage/photos/' . $imageName;
        $imageManager = new ImageManager(new Driver());
        $image = $imageManager->read($file->getRealPath());
        $image->cover(300, 300);
        $image->save($imagePath);

        return $imageName;
    }
}
