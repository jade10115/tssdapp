<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index()
    {
        return Position::orderBy('position')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'position' => 'required|string|max:150|unique:tbl_position,position',
        ]);

        $p = Position::create([
            'position' => $request->position,
            'is_active' => 1,
        ]);

        return response()->json(['message' => 'Position created.', 'data' => $p], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'position' => 'required|string|max:150|unique:tbl_position,position,' . $id,
        ]);

        $p = Position::findOrFail($id);
        $p->position = $request->position;
        $p->save();

        return response()->json(['message' => 'Position updated.']);
    }

    public function destroy($id)
    {
        $p = Position::findOrFail($id);
        $p->delete();

        return response()->json(['message' => 'Position deleted.']);
    }
}
