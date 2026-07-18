<?php

namespace App\Http\Controllers;

use App\Http\Requests\BranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Illuminate\Http\Exceptions\HttpResponseException;

class BranchController extends Controller
{
    #[HeaderParameter('Authorization')]
    public function index()
    {
        $branches = Branch::query()->orderBy('location')->get();

        return BranchResource::collection($branches);
    }

    #[HeaderParameter('Authorization')]
    public function store(BranchRequest $request)
    {
        $data = $request->validated();
        $location = trim($data['location']);

        $exists = Branch::query()
            ->whereRaw('LOWER(location) = ?', [strtolower($location)])
            ->exists();

        if ($exists) {
            throw new HttpResponseException(response([
                'errors' => [
                    'location' => ['Nama cabang sudah ada.'],
                ],
            ], 400));
        }

        $branch = Branch::create([
            'location' => $location,
        ]);

        return (new BranchResource($branch))->response()->setStatusCode(201);
    }

    #[HeaderParameter('Authorization')]
    public function show(int $id)
    {
        $branch = Branch::find($id);

        if (! $branch) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => ['Cabang tidak ditemukan.'],
                ],
            ], 404));
        }

        return (new BranchResource($branch))->response()->setStatusCode(200);
    }

    #[HeaderParameter('Authorization')]
    public function update(BranchRequest $request, int $id)
    {
        $branch = Branch::find($id);

        if (! $branch) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => ['Cabang tidak ditemukan.'],
                ],
            ], 404));
        }

        $data = $request->validated();
        $location = trim($data['location']);

        $duplicate = Branch::query()
            ->whereRaw('LOWER(location) = ?', [strtolower($location)])
            ->where('id', '<>', $branch->id)
            ->exists();

        if ($duplicate) {
            throw new HttpResponseException(response([
                'errors' => [
                    'location' => ['Nama cabang sudah ada.'],
                ],
            ], 400));
        }

        $branch->update([
            'location' => $location,
        ]);

        return (new BranchResource($branch->fresh()))->response()->setStatusCode(200);
    }

    #[HeaderParameter('Authorization')]
    public function destroy(int $id)
    {
        $branch = Branch::find($id);

        if (! $branch) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => ['Cabang tidak ditemukan.'],
                ],
            ], 404));
        }

        if ($branch->users()->exists() || $branch->siswas()->exists() || $branch->kelas()->exists()) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => ['Cabang tidak dapat dihapus karena memiliki data terkait.'],
                ],
            ], 409));
        }

        $branch->delete();

        return response(['data' => true])->setStatusCode(200);
    }
}
