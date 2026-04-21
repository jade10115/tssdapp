<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SignatoryController extends Controller
{
    public function index()
    {
        return Signatory::all();
    }

    public function update(Request $request, $id)
    {
        $signatory = Signatory::findOrFail($id);
        $signatory->update($request->only('name','designation'));
        return response()->json(['message' => 'Updated']);
    }
}
