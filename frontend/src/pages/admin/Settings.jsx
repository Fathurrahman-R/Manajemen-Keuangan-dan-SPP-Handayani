import React, { useState } from "react";

export default function Settings({ onSave }) {
  const [schoolName, setSchoolName] = useState("SMA Negeri 1 Contoh");
  const [phone, setPhone] = useState("(021) 1234567");
  const [address, setAddress] = useState("Jl. Pendidikan No. 123, Kota Contoh");

  function handleSave(e) {
    e.preventDefault();
    const payload = { schoolName, phone, address };
    if (onSave) onSave(payload);
    alert("Pengaturan disimpan (dummy): " + JSON.stringify(payload));
  }

  return (
    <div className="p-6">
      <h2 className="text-2xl font-semibold mb-4">Pengaturan</h2>
      <p className="text-sm text-gray-600 mb-6">Kelola pengaturan umum aplikasi SPP</p>

      <form onSubmit={handleSave} className="grid grid-cols-1 md:grid-cols-2 gap-6 bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">Nama Sekolah</label>
          <input value={schoolName} onChange={(e) => setSchoolName(e.target.value)} className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">Telepon Sekolah</label>
          <input value={phone} onChange={(e) => setPhone(e.target.value)} className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>

        <div className="md:col-span-2">
          <label className="block text-sm font-medium text-gray-700 mb-2">Alamat Sekolah</label>
          <textarea value={address} onChange={(e) => setAddress(e.target.value)} rows={3} className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>

        <div className="md:col-span-2 flex justify-end">
          <button type="submit" className="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  );
}