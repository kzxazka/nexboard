<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Note;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class NoteController extends Controller
{
    public function index(Request $request)
    {
        $notes = Note::where('user_id', Auth::id())
            ->orderBy('updated_at', 'desc')
            ->get();
            
        return Inertia::render('Notes/Index', [
            'notes' => $notes,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $note = Note::create([
            'user_id' => Auth::id(),
            'project_id' => $request->project_id,
            'title' => $request->title,
            'content' => '',
            'icon' => '📝'
        ]);

        return redirect()->route('notes.show', $note->id);
    }

    public function show(Note $note)
    {
        if ($note->user_id !== Auth::id()) {
            abort(403);
        }

        return Inertia::render('Notes/Show', [
            'note' => $note,
            'notes' => Note::where('user_id', Auth::id())->orderBy('updated_at', 'desc')->get()
        ]);
    }

    public function update(Request $request, Note $note)
    {
        if ($note->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'string|max:255',
            'content' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'cover_image' => 'nullable|string',
        ]);

        $note->update($validated);

        return redirect()->back();
    }

    public function destroy(Note $note)
    {
        if ($note->user_id !== Auth::id()) {
            abort(403);
        }

        $note->delete();

        return redirect()->route('notes.index');
    }
}
