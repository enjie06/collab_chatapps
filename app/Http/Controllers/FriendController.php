<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Friendship;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // teman = accepted (dua arah)
        $friends  = $user->friends();

        // masuk (pending yg kamu terima)
        $incoming = $user->receivedRequestsPending()->get();

        // keluar: tampilkan BOTH pending & rejected
        $outgoing = Friendship::where('requester_id', $user->id)
                    ->whereIn('status', ['pending', 'rejected'])
                    ->with('receiver')
                    ->get();

        return view('friends.index', compact('friends', 'incoming', 'outgoing'));
    }

    public function sendRequest(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $receiver = User::where('email', $request->email)->first();
        if (!$receiver) return back()->with('error', 'User tidak ditemukan.');
        if ($receiver->id == Auth::id()) return back()->with('error', 'Tidak bisa menambahkan diri sendiri.');

        // cari relasi existing dua arah
        $existing = Friendship::between(Auth::id(), $receiver->id)->first();

        // blok jika masih pending/accepted
        if ($existing && in_array($existing->status, ['pending','accepted'])) {
            return back()->with('error', 'Sudah ada hubungan pertemanan.');
        }

        // kalau ada yang 'rejected' / 'removed' — hapus supaya bisa buat pending baru
        if ($existing && in_array($existing->status, ['rejected','removed'])) {
            $existing->delete();
        }

        Friendship::create([
            'requester_id' => Auth::id(),
            'receiver_id'  => $receiver->id,
            'status'       => 'pending',
        ]);

        return back()->with('success', 'Permintaan teman terkirim.');
    }

    public function accept($id)
    {
        $f = Friendship::findOrFail($id);
        // hanya penerima yang boleh terima
        abort_if($f->receiver_id !== Auth::id(), 403);
        $f->update(['status' => 'accepted']);
        return back()->with('success', 'Permintaan teman diterima.');
    }

    public function reject($id)
    {
        $f = Friendship::findOrFail($id);

        // Ubah status jadi rejected (tidak hapus)
        $f->update(['status' => 'rejected']);

        return back()->with('success', 'Permintaan teman ditolak.');
    }

    public function remove($id)
    {
        // Hapus atau tandai removed
        Friendship::between(Auth::id(), $id)
            ->update(['status' => 'removed']);

        return back()->with('success', 'Teman dihapus.');
    }

    // opsional: bersihkan jejak permintaan ditolak dari daftar "terkirim"
    public function clearRejected($friendshipId)
    {
        $f = Friendship::findOrFail($friendshipId);
        abort_if($f->requester_id !== Auth::id(), 403);
        if ($f->status === 'rejected') $f->delete();
        return back()->with('success', 'Notifikasi penolakan dibersihkan.');
    }

    public function block($userId)
    {
        $f = Friendship::between(auth()->id(), $userId)->firstOrFail();

        $f->update([
            'is_blocked' => true,
            'blocked_by' => auth()->id()
        ]);

        return back()->with('success', 'User diblokir.');
    }

    public function unblock($userId)
    {
        $f = Friendship::between(auth()->id(), $userId)->firstOrFail();

        // hanya yang memblokir yang boleh membuka blokir
        if ($f->blocked_by !== auth()->id()) {
            abort(403, 'Kamu tidak memiliki izin untuk membuka blokir ini.');
        }

        $f->update([
            'is_blocked' => false,
            'blocked_by' => null
        ]);

        return back()->with('success', 'User di-unblock.');
    }
}
