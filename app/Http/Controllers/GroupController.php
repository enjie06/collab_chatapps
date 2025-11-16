<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{

    // Buat grup
    public function createGroup()
    {
        $friends = auth()->user()->friends(); // daftar teman untuk dipilih
        return view('chat.create-group', compact('friends'));
    }

    // Lihat grup
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'member_ids' => 'required|array',
        ]);

        // 1️⃣ Buat percakapan grup
        $conversation = Conversation::create([
            'name' => $request->title,
            'type' => 'group',
            'avatar' => null,   // nanti bisa update foto grup
        ]);

        // 2️⃣ Tambahkan pembuat grup
        $conversation->users()->attach(Auth::id(), ['role' => 'admin']);

        // 3️⃣ Tambahkan anggota lain
        foreach ($request->member_ids as $memberId) {
            $conversation->users()->attach($memberId, ['role' => 'member']);
        }

        return redirect()->route('chat.index')->with('success', 'Grup berhasil dibuat!');
    }

    // Halaman info grup
    public function info($id)
    {
        $group = Conversation::with('users')
            ->where('type', 'group')
            ->findOrFail($id);

        $me = Auth::id();

        // role user
        $pivot = $group->users()->where('user_id', $me)->first()->pivot;
        $isAdmin = $pivot->role === 'admin';

        // Hanya anggota aktif (belum keluar)
        $members = $group->users()->whereNull('conversation_user.deleted_at')->get();

        return view('chat.group-info', [
            'group'   => $group,
            'members' => $members,
            'isAdmin' => $isAdmin,
        ]);
    }

    // Update nama grup
    public function updateName(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:255']);

        $group = Conversation::findOrFail($id);

        $this->authorizeAdmin($group);

        $group->update(['name' => $request->name]);

        return back()->with('success', 'Nama grup berhasil diubah.');
    }

    // Update foto grup
    public function updatePhoto(Request $request, $id)
    {
        $request->validate(['avatar' => 'required|image|max:2048']);

        $group = Conversation::findOrFail($id);

        $this->authorizeAdmin($group);

        $path = $request->file('avatar')->store('group_avatars', 'public');

        $group->update(['avatar' => $path]);

        return back()->with('success', 'Foto grup berhasil diperbarui.');
    }

    // Tambah anggota
    public function addMember(Request $request, $id)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);

        $group = Conversation::findOrFail($id);

        $this->authorizeAdmin($group);

        if (!$group->users->contains($request->user_id)) {
            $group->users()->attach($request->user_id, ['role' => 'member']);
        }

        return back()->with('success', 'Anggota baru berhasil ditambahkan.');
    }

    // Hapus anggota
    public function removeMember($id, $memberId)
    {
        $group = Conversation::findOrFail($id);

        $this->authorizeAdmin($group);

        $group->users()->detach($memberId);

        return back()->with('success', 'Anggota berhasil dihapus.');
    }

    // Promote admin
    public function promote($id, $memberId)
    {
        $group = Conversation::findOrFail($id);
        $this->authorizeAdmin($group);

        $group->users()->updateExistingPivot($memberId, ['role' => 'admin']);

        return back()->with('success', 'Pengguna dipromosikan menjadi admin.');
    }

    // Demote admin
    public function demote($id, $memberId)
    {
        $group = Conversation::findOrFail($id);
        $this->authorizeAdmin($group);

        $group->users()->updateExistingPivot($memberId, ['role' => 'member']);

        return back()->with('success', 'Admin diturunkan menjadi member.');
    }

    // Leave group
    public function leave($id)
    {
        $group = Conversation::where('type', 'group')->findOrFail($id);
        $me = Auth::id();

        // Admin tidak boleh leave kalau hanya 1 admin tersisa
        $admins = $group->users()->wherePivot('role', 'admin')->count();
        $myPivot = $group->users()->where('user_id', $me)->first()->pivot;
        $myRole  = $myPivot->role;

        if ($myRole === 'admin' && $admins <= 1) {
            // Cukup pakai flash error, jangan tulis ke tabel messages lagi
            return redirect()
                ->route('chat.show', $group->id)
                ->with('error_admin_leave', '⚠️ Kamu adalah admin terakhir di grup ini, jadi kamu tidak bisa keluar.');
        }

        // Tandai user keluar (bukan hapus relasi)
        $group->users()->updateExistingPivot($me, [
            'deleted_at' => now(),
        ]);

        return redirect()->route('chat.index')->with('success', 'Kamu telah keluar dari grup.');
    }

    // Delete group
    public function delete($id)
    {
        $group = Conversation::findOrFail($id);

        $this->authorizeAdmin($group);

        $group->delete();

        return redirect()->route('chat.index')->with('success', 'Grup berhasil dihapus.');
    }

    // Helper: cek admin
    private function authorizeAdmin($group)
    {
        $role = $group->users()
            ->where('user_id', Auth::id())
            ->first()
            ->pivot
            ->role;

        abort_if($role !== 'admin', 403, "Hanya admin yang boleh melakukan aksi ini.");
    }
}
