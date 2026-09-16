<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    // ==========================================
    // INDEX
    // Mengambil semua pesan contact
    // ==========================================

    public function index()
    {
        $contacts = Contact::latest()->get();

        return response()->json([
            'message' => 'Data pesan kontak berhasil diambil.',
            'data' => $contacts,
        ]);
    }

    // ==========================================
    // STORE
    // Menyimpan pesan dari halaman kontak
    // ==========================================

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $contact = Contact::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'message' => $data['message'],
            'status' => 'baru',
        ]);

        return response()->json([
            'message' => 'Pesan berhasil dikirim. Terima kasih sudah menghubungi Cemilku.',
            'data' => $contact,
        ], 201);
    }

    // ==========================================
    // SHOW
    // Mengambil detail satu pesan
    // ==========================================

    public function show(Contact $contact)
    {
        return response()->json([
            'message' => 'Detail pesan berhasil diambil.',
            'data' => $contact,
        ]);
    }

    // ==========================================
    // UPDATE
    // Mengubah status pesan
    // ==========================================

    public function update(Request $request, Contact $contact)
    {
        $data = $request->validate([
            'status' => ['required', 'in:baru,dibaca,dibalas,selesai'],
        ]);

        $contact->update([
            'status' => $data['status'],
        ]);

        return response()->json([
            'message' => 'Status pesan berhasil diperbarui.',
            'data' => $contact,
        ]);
    }

    // ==========================================
    // DESTROY
    // Menghapus pesan
    // ==========================================

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return response()->json([
            'message' => 'Pesan berhasil dihapus.',
        ]);
    }
}
