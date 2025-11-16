<?php

namespace App\Http\Controllers;

use App\Http\Requests\JenisTagihanRequest;
use App\Http\Resources\JenisTagihanResource;
use App\Models\JenisTagihan;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JenisTagihanController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $jt = JenisTagihan::all();

        if ($jt->isEmpty()) {
            throw new HttpResponseException(response([
                "errors"=>[
                    "message"=>[
                        'belum ada jenis tagihan.'
                    ]
                ]
            ],404));
        }

        return JenisTagihanResource::collection($jt);
    }

    public function create(JenisTagihanRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();

        $jt = new JenisTagihan($data);
        $jt->save();
        return (new  JenisTagihanResource($jt))->response()->setStatusCode(201);
    }

    public function get(string $id)
    {
        $user = Auth::user();
        $jt = JenisTagihan::query()->find($id);

        if(!$jt)
        {
            throw new HttpResponseException(response([
                "errors"=>[
                    "message"=>[
                        'jenis tagihan tidak ditemukan.'
                    ]
                ]
            ],404));
        }

        return (new JenisTagihanResource($jt))->response()->setStatusCode(200);
    }

    public function update(JenisTagihanRequest $request, string $id)
    {
        $user = Auth::user();
        $data = $request->validated();
        $jt = JenisTagihan::query()->find($id);
        if(!$jt)
        {
            throw new HttpResponseException(response([
                "errors"=>[
                    "message"=>[
                        'jenis tagihan tidak ditemukan.'
                    ]
                ]
            ],404));
        }

        $jt->update($data);
        return (new JenisTagihanResource($jt))->response()->setStatusCode(200);
    }

    public function delete(string $id)
    {
        $user = Auth::user();
        $jt = JenisTagihan::query()->find($id);
        if(!$jt)
        {
            throw new HttpResponseException(response([
                "errors"=>[
                    "message"=>[
                        'jenis tagihan tidak ditemukan.'
                    ]
                ]
            ],404));
        }
        $jt->delete();
        return response([
            "data"=>true
        ])->setStatusCode(200);
    }
}
