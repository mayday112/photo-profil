<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;
use Intervention\Image\ImageManager;
// use Intervention\Image\Drivers\Imagick\Driver;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PhotosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->ajax()) {
            $photos = Photo::all(['id', 'user_id', 'photo_path']);

            return DataTables::of($photos)
                ->editColumn('photo_path', function ($row) {
                    return '<a href="'. route('photos.show', $row->id).'" target="_blank"><img src="' . $row->photo_path. '" width=100 /></a>';
                })
                ->addColumn('action', function ($photo) {
                    return '<form action="' . route('photos.destroy', $photo->id) . '" method="POST">
                    ' . method_field('delete') . csrf_field() . '
                    <a href="' . route('photos.edit', $photo->id) . '" class="btn btn-warning">Edit</a>|
                    <button type="submit" class="btn btn-danger" onclick=" return confirm(\'Apakah anda yakin?\')">Hapus</button>
                </form>';
                })
                ->rawColumns(['photo_path', 'action'])
                ->make();
        }
        return view('index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|unique:'. Photo::class,
            'photo_file' => 'required|image|mimes:jpg,jpeg,png|max:5120'
        ]);

        $file = $request->file('photo_file');
        if ($file) {
            $imageName = $this->savePhotos($file);

            $validated['photo_path'] = Storage::disk('public')->url('photos/' . $imageName);
        } else {
            return redirect()->route('photos.index');
        }

        $image = Photo::create($validated);

        return redirect()->route('photos.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $photo = Photo::find($id);

        return redirect($photo->photo_path);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $photo = Photo::find($id);
        if(!$photo){
            return redirect()->route('photos.index');
        }

        return view('edit', ['photo'=> $photo]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'user_id' => ['required', Rule::unique(Photo::class)->ignore($id)],
            'photo_file' => 'image|mimes:jpg,jpeg,png|max:5120'
        ]);

        $photo = Photo::find($id);
        if(!$photo) return redirect()->route('photos.index');
        $file = $request->file('photo_file');

        if ($file) {
            $this->deletePhoto($photo);

            $imageName = $this->savePhotos($file);
            $validated['photo_path'] =  Storage::disk('public')->url('photos/' . $imageName);
        } else {
            $validated['photo_path'] = $photo->photo_path;
        }

        $photo->update($validated);

        return redirect()->route('photos.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $photo = Photo::find($id);

        if ($photo) {
            $this->deletePhoto($photo);
            $photo->delete();
        }
        return redirect()->route('photos.index');
    }

    private function deletePhoto(Photo $photo) {
        $photo_path = $photo->photo_path;
        $parts = explode("/", $photo_path);
        // Ambil bagian yang dibutuhkan (indeks array mungkin berbeda tergantung struktur URL)
        $photo_path = $parts[4] . "/" . $parts[5]; // Sesuaikan indeksnya

        if(Storage::disk('public')->exists((string) $photo_path)){
            Storage::disk('public')->delete((string) $photo_path);
        }
    }

    private function savePhotos(UploadedFile $file){
        $imageName = Str::random(5) .$file->getClientOriginalName();
        $imagePath = public_path() . '/storage/photos/' . $imageName;
        $imageManager = new ImageManager(new Driver());
        $image = $imageManager->read($file->getRealPath());
        $image->cover(300, 300);
        $image->save($imagePath);

        return $imageName;
    }
}
