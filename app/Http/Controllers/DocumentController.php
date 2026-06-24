<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentTracking;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    public function me()
    {
        $user    = Auth::user();
        $profile = UserProfile::where('user_id', $user->id)->first();
        return response()->json($profile);
    }

 public function index()
{
    $documents = Document::with([
        'type',
        'submitter',
        'tracking.toUser',
        'tracking.fromUser',  // needed for timeline "forwarded by"
    ])
    ->orderBy('id', 'desc')
    ->get();

    return response()->json($documents);
}

    public function types()
    {
        return response()->json(DocumentType::where('is_active', true)->get());
    }

    public function users()
    {
        $users = UserProfile::select('user_id', 'first_name', 'last_name', 'division')
            ->orderBy('division')
            ->get();
        return response()->json($users);
    }

    // ── Create Document ───────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'            => 'nullable|string|max:255',
            'document_type_id' => 'required|exists:document_types,id',
            'description'      => 'nullable|string',
        ]);

        $user    = Auth::user();
        $profile = UserProfile::where('user_id', $user->id)->firstOrFail();

        // Auto-generate title from document type if not provided
        if (empty($validated['title'])) {
            $type = DocumentType::find($validated['document_type_id']);
            $validated['title'] = ($type ? $type->name : 'Document') . ' — ' . now()->format('M d, Y');
        }

        $doc = Document::create([
            'title'            => $validated['title'],
            'document_type_id' => $validated['document_type_id'],
            'description'      => $validated['description'] ?? null,
            'submitted_by'     => $profile->user_id,
            'origin_division'  => $profile->division,
            'status'           => 'pending',
        ]);

        return response()->json($doc->load(['type', 'submitter', 'tracking']), 201);
    }

    // ── Update Document ───────────────────────────────────────
    public function update(Request $request, $id)
    {
        $doc = Document::findOrFail($id);

        $validated = $request->validate([
            'title'            => 'nullable|string|max:255',
            'document_type_id' => 'required|exists:document_types,id',
            'description'      => 'nullable|string',
        ]);

        $doc->update($validated);
        return response()->json($doc);
    }

    // ── Delete Document ───────────────────────────────────────
    public function destroy($id)
    {
        Document::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    // ── Forward Document to next recipient ───────────────────
    public function submit(Request $request, $id)
    {
        $doc = Document::findOrFail($id);

        $validated = $request->validate([
            'to_user_id' => 'required|exists:tbl_user_profile,user_id',
            'remarks'    => 'nullable|string',
        ]);

        if ($doc->status === 'completed') {
            return response()->json(['message' => 'Document is already completed'], 422);
        }

        $currentProfile = UserProfile::where('user_id', Auth::id())->firstOrFail();
        $toProfile      = UserProfile::where('user_id', $validated['to_user_id'])->firstOrFail();
        $lastTracking   = $doc->tracking()->latest()->first();

        // Permission check: only the current holder can forward
        if (!$lastTracking && $doc->submitted_by != $currentProfile->user_id) {
            return response()->json(['message' => 'Only the document creator can initiate the workflow'], 403);
        }
        if ($lastTracking && $lastTracking->to_user_id != $currentProfile->user_id) {
            return response()->json(['message' => 'You are not the current document holder'], 403);
        }
        if ($lastTracking && $lastTracking->status !== 'received') {
            return response()->json(['message' => 'You must receive the document before forwarding it'], 422);
        }

        DB::beginTransaction();
        try {
            $tracking = DocumentTracking::create([
                'document_id'  => $doc->id,
                'from_user_id' => $currentProfile->user_id,
                'to_user_id'   => $validated['to_user_id'],
                'to_division'  => $toProfile->division,
                'status'       => 'pending',
                'remarks'      => $validated['remarks'] ?? null,
            ]);

            $doc->update(['status' => 'in_progress']);
            DB::commit();

            return response()->json(['success' => true, 'tracking' => $tracking]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to forward document'], 500);
        }
    }

    // ── Receive Document ──────────────────────────────────────
    public function receive(Request $request, $id)
    {
        $doc            = Document::findOrFail($id);
        $currentProfile = UserProfile::where('user_id', Auth::id())->firstOrFail();

        $pendingTracking = $doc->tracking()
            ->where('status', 'pending')
            ->where('to_user_id', $currentProfile->user_id)
            ->latest()
            ->first();

        if (!$pendingTracking) {
            return response()->json(['message' => 'No pending document for you to receive'], 403);
        }

        DB::beginTransaction();
        try {
            $pendingTracking->update([
                'status'      => 'received',
                'received_at' => now(),
            ]);

            // Status stays in_progress — the holder decides when to complete
            $doc->update(['status' => 'in_progress']);

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to receive document'], 500);
        }
    }

    // ── Complete Document (manual by current holder) ──────────
    public function complete(Request $request, $id)
    {
        $doc            = Document::findOrFail($id);
        $currentProfile = UserProfile::where('user_id', Auth::id())->firstOrFail();

        if ($doc->status === 'completed') {
            return response()->json(['message' => 'Document is already completed'], 422);
        }

        $lastTracking = $doc->tracking()->latest()->first();

        // Only the person who last received the document can complete it
        if (!$lastTracking
            || $lastTracking->to_user_id !== $currentProfile->user_id
            || $lastTracking->status !== 'received'
        ) {
            return response()->json(['message' => 'Only the current document holder can complete it'], 403);
        }

        $doc->update(['status' => 'completed']);

        return response()->json(['success' => true]);
    }

    // ── Document Types ────────────────────────────────────────
    public function storeType(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        // Auto-generate code from name if not provided
        if (empty($validated['code'])) {
            $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $validated['name']));
            $code = substr($base, 0, 10);
            $i    = 1;
            while (DocumentType::where('code', $code)->exists()) {
                $code = substr($base, 0, 8) . $i++;
            }
            $validated['code'] = $code;
        }

        $type = DocumentType::create($validated + ['is_active' => true]);
        return response()->json($type, 201);
    }

    public function updateType(Request $request, $id)
    {
        $type = DocumentType::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => ['nullable', 'string', 'max:50',
                              Rule::unique('document_types', 'code')->ignore($type->id)],
            'description' => 'nullable|string',
        ]);

        $type->update($validated);
        return response()->json($type);
    }
}